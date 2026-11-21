<?php
declare(strict_types=1);
require_once __DIR__ . '/_lib/layout.php';

const WRITING_TYPES = ['實用寫作', '命題寫作', '短文寫作'];

$all = get_writing_questions();
$papers = array_values(array_unique(array_column($all, 'paper')));

$paper = query_param('year');
$type = query_param('type');
if (!in_array($paper, $papers, true)) {
    $paper = '';
}
if (!in_array($type, WRITING_TYPES, true)) {
    $type = '';
}

$matches = array_values(array_filter($all, fn($q) =>
    ($paper === '' || $q['paper'] === $paper) && ($type === '' || $q['type'] === $type)));
$practical = array_values(array_filter($matches, fn($q) => $q['type'] === '實用寫作'));
$others = array_values(array_filter($matches, fn($q) => $q['type'] !== '實用寫作'));

function paper_label(array $q): string
{
    $label = $q['year'] ? $q['paper'] . ' 年' : $q['paper'];
    if ($q['part']) {
        $label .= '・' . $q['part'];
    }
    return $label . '・第 ' . (int) $q['question_no'] . ' 題';
}

function render_writing_list(array $questions): void
{
    echo '<ol class="writing-list">';
    foreach ($questions as $q) {
        echo '<li class="writing-item"><p class="q-meta"><strong>' . h(paper_label($q)) . '</strong> ';
        echo '<span class="tag tag-type">' . h($q['type']) . '</span>';
        if (!empty($q['genre'])) {
            echo ' <span class="tag">' . h($q['genre']) . '</span>';
        }
        echo '</p><p class="writing-prompt">' . nl2br(h($q['prompt'])) . '</p>';
        if (!empty($q['has_material'])) {
            echo '<p class="muted small">原題另附閱讀資料，此處只列寫作任務。</p>';
        }
        if (!empty($q['has_image'])) {
            echo '<p class="muted small">原題附圖片，此處未有收錄。</p>';
        }
        echo '</li>';
    }
    echo '</ol>';
}

$tabs = ['' => '全部', '實用寫作' => '實用寫作（甲部）', '命題寫作' => '命題寫作', '短文寫作' => '短文寫作'];

render_header('卷二寫作題目', 'writing');
?>
<h1 class="page-title">歷屆卷二寫作題目</h1>
<p class="lead">2024 年起，卷二分甲部「實用寫作」（必答）及乙部「命題寫作」（三選一）；2023 年或以前只設命題寫作。</p>

<nav class="tabs" aria-label="題型">
  <?php foreach ($tabs as $value => $label): ?>
    <a href="/writing.php?<?= h(http_build_query(array_filter(['type' => $value, 'year' => $paper]))) ?>"<?= $value === $type ? ' aria-current="page"' : '' ?>><?= h($label) ?></a>
  <?php endforeach; ?>
</nav>

<form class="filters" method="get" action="/writing.php">
  <?php if ($type !== ''): ?><input type="hidden" name="type" value="<?= h($type) ?>"><?php endif; ?>
  <label>年份
    <select name="year">
      <option value="">全部年份</option>
      <?php foreach ($papers as $p): ?><option value="<?= h($p) ?>"<?= $p === $paper ? ' selected' : '' ?>><?= h(ctype_digit($p) ? $p . ' 年' : $p) ?></option><?php endforeach; ?>
    </select>
  </label>
  <button class="btn btn-primary" type="submit">篩選</button>
  <span class="muted small">共 <?= count($matches) ?> 題</span>
</form>

<?php if (!$matches): ?>
  <p class="empty">沒有符合條件的題目。<?= $type === '實用寫作' ? '實用寫作自 2024 年起才納入卷二。' : '' ?></p>
<?php endif; ?>

<?php if ($practical): ?>
  <section class="panel">
    <h2>實用寫作（甲部）</h2>
    <?php render_writing_list($practical); ?>
  </section>
<?php endif; ?>

<?php if ($others): ?>
  <section class="panel">
    <h2><?= $type === '' ? '命題寫作及其他題目' : h($type) ?></h2>
    <?php render_writing_list($others); ?>
  </section>
<?php endif; ?>
<?php
render_footer();
