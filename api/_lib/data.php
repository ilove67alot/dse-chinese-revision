<?php
declare(strict_types=1);

require_once __DIR__ . '/supabase.php';

const DATA_DIR = __DIR__ . '/../../data';
const VOCAB_CATEGORIES = ['通假字', '古今異義', '詞類活用', '一詞多義'];
const FANWEN_SUMMARY_COLUMNS = 'code,sort_order,title,author,dynasty,genre';
const WRITING_PUBLIC_COLUMNS = 'id,year,paper,part,question_no,type,genre,prompt,has_material,has_image';

/** 'supabase', 'local' (not configured) or 'fallback' (configured but unreachable). */
function data_mode(?string $set = null): string
{
    static $mode = null;
    if ($set !== null) {
        $mode = $set;
    }
    return $mode ?? (supabase_enabled() ? 'supabase' : 'local');
}

/**
 * Reads from Supabase when configured; otherwise (or if Supabase is unreachable) from the
 * bundled JSON in data/, so the site keeps working for students.
 */
function load_data(string $cacheKey, callable $fromSupabase, callable $fromLocal): mixed
{
    static $cache = [];
    if (array_key_exists($cacheKey, $cache)) {
        return $cache[$cacheKey];
    }
    if (supabase_enabled() && data_mode() !== 'fallback') {
        try {
            return $cache[$cacheKey] = $fromSupabase();
        } catch (Throwable $e) {
            error_log('[supabase] ' . $e->getMessage());
            data_mode('fallback');
        }
    }
    return $cache[$cacheKey] = $fromLocal();
}

function read_json(string $relativePath): array
{
    return json_decode((string) file_get_contents(DATA_DIR . '/' . $relativePath), true, 512, JSON_THROW_ON_ERROR);
}

function local_fanwen(): array
{
    $items = [];
    foreach (glob(DATA_DIR . '/fanwen/*.json') as $file) {
        $items[] = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
    }
    usort($items, fn($a, $b) => $a['sort_order'] <=> $b['sort_order']);
    return $items;
}

function get_fanwen_list(): array
{
    return load_data(
        'fanwen_list',
        fn() => supabase_request('GET', 'fanwen', ['select' => FANWEN_SUMMARY_COLUMNS, 'order' => 'sort_order.asc']),
        fn() => local_fanwen()
    );
}

function get_fanwen(string $code): ?array
{
    return load_data(
        'fanwen_' . $code,
        fn() => supabase_request('GET', 'fanwen', ['select' => '*', 'code' => 'eq.' . $code])[0] ?? null,
        function () use ($code) {
            foreach (local_fanwen() as $item) {
                if ($item['code'] === $code) {
                    return $item;
                }
            }
            return null;
        }
    );
}

function fanwen_titles(): array
{
    $titles = [];
    foreach (get_fanwen_list() as $item) {
        $titles[$item['code']] = $item['title'];
    }
    return $titles;
}

function get_all_vocab(): array
{
    $vocab = load_data(
        'vocab',
        fn() => supabase_select_all('vocab', 'id,fanwen_code,piece,word,sentence,definition,pos,categories,tongjia,note', 'id.asc'),
        fn() => read_json('vocab.json')
    );
    $order = array_flip(array_keys(fanwen_titles()));
    usort($vocab, fn($a, $b) => [$order[$a['fanwen_code']] ?? 99, $a['id']] <=> [$order[$b['fanwen_code']] ?? 99, $b['id']]);
    return $vocab;
}

function filter_vocab(array $vocab, string $fanwen, string $category): array
{
    return array_values(array_filter($vocab, function ($v) use ($fanwen, $category) {
        if ($fanwen !== '' && $v['fanwen_code'] !== $fanwen) {
            return false;
        }
        return $category === '' || in_array($category, $v['categories'] ?? [], true);
    }));
}

function get_practice_questions(string $code): array
{
    return load_data(
        'practice_' . $code,
        fn() => supabase_request('GET', 'practice_questions', [
            'select' => '*',
            'fanwen_code' => 'eq.' . $code,
            'order' => 'sort_order.asc',
        ]),
        fn() => array_values(array_filter(read_json('practice.json'), fn($q) => $q['fanwen_code'] === $code))
    );
}

function get_writing_questions(): array
{
    // Newest exam first, then 練習卷, then 樣本試卷.
    $paperRank = fn(array $q) => [$q['year'] === null ? 1 : 0, -(int) $q['year'], $q['paper'] === '樣本試卷' ? 1 : 0, (int) $q['question_no']];
    $questions = load_data(
        'writing',
        fn() => supabase_request('GET', 'writing_questions', [
            'select' => WRITING_PUBLIC_COLUMNS,
            'order' => 'year.desc.nullslast,paper.asc,question_no.asc',
        ]),
        function () {
            $public = array_flip(explode(',', WRITING_PUBLIC_COLUMNS));
            return array_map(
                fn($q) => array_intersect_key($q + ['has_material' => false, 'has_image' => false], $public),
                read_json('writing.json')
            );
        }
    );
    usort($questions, fn($a, $b) => $paperRank($a) <=> $paperRank($b));
    return $questions;
}

function save_attempt(string $kind, ?string $fanwen, ?string $category, int $score, int $total, string $nickname): bool
{
    if (!supabase_enabled() || data_mode() === 'fallback' || $total < 1) {
        return false;
    }
    try {
        supabase_request('POST', 'quiz_attempts', [], [
            'kind' => $kind,
            'fanwen_code' => $fanwen ?: null,
            'category' => $category ?: null,
            'score' => $score,
            'total' => $total,
            'nickname' => $nickname !== '' ? mb_substr($nickname, 0, 40) : null,
        ], ['Prefer: return=minimal']);
        return true;
    } catch (Throwable $e) {
        error_log('[supabase] ' . $e->getMessage());
        return false;
    }
}
