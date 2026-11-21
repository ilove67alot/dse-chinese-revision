<?php
declare(strict_types=1);
require_once __DIR__ . '/_lib/layout.php';

$fanwen = query_param('fanwen');
$category = query_param('category');
if (!in_array($category, VOCAB_CATEGORIES, true)) {
    $category = '';
}

$titles = fanwen_titles();
if (!isset($titles[$fanwen])) {
    $fanwen = '';
}
const CARDS_PER_PAGE = 48;

$matched = filter_vocab(get_all_vocab(), $fanwen, $category);
$total = count($matched);
$pages = max(1, (int) ceil($total / CARDS_PER_PAGE));
$page = min($pages, max(1, (int) query_param('page')));
$cards = array_slice($matched, ($page - 1) * CARDS_PER_PAGE, CARDS_PER_PAGE);

$pageUrl = fn(int $n) => '/flashcards.php?' . http_build_query(array_filter([
    'fanwen' => $fanwen, 'category' => $category, 'page' => $n > 1 ? $n : null,
]));
$pager = function () use ($page, $pages, $pageUrl): string {
    if ($pages < 2) {
        return '';
    }
    $html = '<nav class="pager" aria-label="分頁">';
    $html .= $page > 1 ? '<a class="btn btn-small" href="' . h($pageUrl($page - 1)) . '">上一頁</a>' : '';
    $html .= '<span class="muted">第 ' . $page . ' ／ ' . $pages . ' 頁</span>';
    $html .= $page < $pages ? '<a class="btn btn-small" href="' . h($pageUrl($page + 1)) . '">下一頁</a>' : '';
    return $html . '</nav>';
};

render_header('字詞卡', 'flashcards');
?>
<h1 class="page-title">字詞卡</h1>
<p class="lead">點擊卡片翻轉查看解釋。標記「已掌握」的卡片會記錄在這部裝置上。</p>

<form class="filters" method="get" action="/flashcards.php">
  <label>範文 <?= fanwen_select($fanwen) ?></label>
  <label>類別 <?= category_select($category) ?></label>
  <button class="btn btn-primary" type="submit">篩選</button>
</form>

<p class="muted small">共 <?= $total ?> 張字詞卡<?= $pages > 1 ? '，每頁 ' . CARDS_PER_PAGE . ' 張' : '' ?>。</p>

<div class="deck-toolbar" data-deck-toolbar hidden>
  <span>本頁顯示 <strong data-deck-count><?= count($cards) ?></strong> 張</span>
  <button class="btn btn-small" type="button" data-deck-shuffle>隨機排序</button>
  <label class="check"><input type="checkbox" data-deck-hide-known> 隱藏已掌握</label>
</div>

<?php if (!$cards): ?>
  <p class="empty">沒有符合條件的字詞。</p>
<?php else: ?>
  <div class="card-grid" data-deck>
    <?php foreach ($cards as $v): ?>
      <article class="flashcard" data-card-id="<?= h($v['id']) ?>">
        <button class="flashcard-inner" type="button" aria-label="翻轉字詞卡：<?= h($v['word']) ?>">
          <span class="flashcard-face flashcard-front">
            <span class="card-source"><?= h($titles[$v['fanwen_code']] ?? '') ?><?= $v['piece'] && $v['piece'] !== ($titles[$v['fanwen_code']] ?? '') ? '・' . h($v['piece']) : '' ?></span>
            <span class="card-word"><?= h($v['word']) ?></span>
            <span class="card-sentence"><?= highlight((string) $v['sentence'], $v['word']) ?></span>
          </span>
          <span class="flashcard-face flashcard-back">
            <span class="card-definition"><?= h($v['definition']) ?></span>
            <?php if ($v['tongjia']): ?><span class="card-extra">通「<?= h($v['tongjia']) ?>」</span><?php endif; ?>
            <span class="card-extra"><?= h($v['pos']) ?></span>
            <?php if ($v['note']): ?><span class="card-note"><?= h($v['note']) ?></span><?php endif; ?>
            <span class="card-tags"><?php foreach (array_intersect($v['categories'] ?? [], VOCAB_CATEGORIES) as $c): ?><span class="tag tag-<?= h($c) ?>"><?= h($c) ?></span><?php endforeach; ?></span>
          </span>
        </button>
        <label class="known-toggle"><input type="checkbox" data-known> 已掌握</label>
      </article>
    <?php endforeach; ?>
  </div>
  <?= $pager() ?>
<?php endif; ?>
<?php
render_footer();
