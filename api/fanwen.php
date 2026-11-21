<?php
declare(strict_types=1);
require_once __DIR__ . '/_lib/layout.php';

$code = query_param('code');

if ($code === '') {
    $list = get_fanwen_list();
    render_header('範文', 'fanwen');
    ?>
    <h1 class="page-title">十二篇範文</h1>
    <p class="lead">選擇一篇範文，查看原文、段落大意、主旨及寫作手法。</p>
    <div class="fanwen-grid">
      <?php foreach ($list as $item): ?>
        <a class="fanwen-card" href="/fanwen.php?code=<?= h($item['code']) ?>">
          <span class="fanwen-no"><?= (int) $item['sort_order'] ?></span>
          <strong><?= h($item['title']) ?></strong>
          <span class="muted"><?= h($item['dynasty']) ?>・<?= h($item['author']) ?>・<?= h($item['genre']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
    <?php
    render_footer();
    return;
}

$item = get_fanwen($code);
if ($item === null) {
    http_response_code(404);
    render_header('找不到範文', 'fanwen');
    echo '<h1 class="page-title">找不到這篇範文</h1><p><a href="/fanwen.php">返回範文列表</a></p>';
    render_footer();
    return;
}

$keyVocab = array_values(array_filter(
    filter_vocab(get_all_vocab(), $code, ''),
    fn($v) => array_intersect($v['categories'] ?? [], VOCAB_CATEGORIES)
));

render_header($item['title'], 'fanwen');
?>
<p class="breadcrumb"><a href="/fanwen.php">範文</a> ／ <?= h($item['title']) ?></p>
<header class="fanwen-head">
  <h1 class="page-title"><?= h($item['title']) ?></h1>
  <p class="meta">
    <span class="tag"><?= h($item['dynasty']) ?></span>
    <span class="tag"><?= h($item['author']) ?></span>
    <span class="tag"><?= h($item['genre']) ?></span>
    <span class="tag"><?= h($item['source']) ?></span>
  </p>
  <p class="lead"><?= h($item['intro']) ?></p>
  <div class="hero-actions">
    <a class="btn btn-primary" href="/flashcards.php?fanwen=<?= h($code) ?>">字詞卡</a>
    <a class="btn" href="/quiz.php?fanwen=<?= h($code) ?>">字詞測驗</a>
    <a class="btn" href="/practice.php?code=<?= h($code) ?>">範文練習</a>
  </div>
</header>

<nav class="toc" aria-label="本頁內容">
  <a href="#text">原文</a><a href="#outline">段落大意</a><a href="#theme">主旨</a><a href="#techniques">寫作手法</a><a href="#tips">溫習重點</a><a href="#vocab">重點字詞</a>
</nav>

<section id="text" class="panel">
  <h2>原文</h2>
  <div class="classical">
    <?php foreach ($item['sections'] as $section): ?>
      <?php if (!empty($section['heading'])): ?><h3><?= h($section['heading']) ?></h3><?php endif; ?>
      <?php foreach ($section['paragraphs'] as $p): ?><p><?= h($p) ?></p><?php endforeach; ?>
    <?php endforeach; ?>
  </div>
</section>

<section id="outline" class="panel">
  <h2>段落大意</h2>
  <dl class="outline">
    <?php foreach ($item['outline'] as $row): ?>
      <dt><?= h($row['label']) ?></dt><dd><?= h($row['summary']) ?></dd>
    <?php endforeach; ?>
  </dl>
</section>

<section id="theme" class="panel">
  <h2>主旨</h2>
  <p><?= h($item['theme']) ?></p>
</section>

<section id="techniques" class="panel">
  <h2>寫作手法</h2>
  <div class="technique-list">
    <?php foreach ($item['techniques'] as $t): ?>
      <article class="technique"><h3><?= h($t['name']) ?></h3><p><?= h($t['detail']) ?></p></article>
    <?php endforeach; ?>
  </div>
</section>

<section id="tips" class="panel">
  <h2>溫習重點</h2>
  <ul class="tips"><?php foreach ($item['tips'] as $tip): ?><li><?= h($tip) ?></li><?php endforeach; ?></ul>
</section>

<section id="vocab" class="panel">
  <h2>重點字詞 <span class="muted small">（通假字、古今異義、詞類活用、一詞多義）</span></h2>
  <?php if (!$keyVocab): ?>
    <p class="muted">暫無資料。</p>
  <?php else: ?>
    <div class="table-scroll">
      <table class="vocab-table">
        <thead><tr><th>字詞</th><th>例句</th><th>解釋</th><th>類別</th></tr></thead>
        <tbody>
          <?php foreach ($keyVocab as $v): ?>
            <tr>
              <td class="word"><?= h($v['word']) ?></td>
              <td><?= highlight((string) $v['sentence'], $v['word']) ?></td>
              <td><?= h($v['definition']) ?><?= $v['tongjia'] ? '（通「' . h($v['tongjia']) . '」）' : '' ?></td>
              <td><?php foreach (array_intersect($v['categories'], VOCAB_CATEGORIES) as $c): ?><span class="tag tag-<?= h($c) ?>"><?= h($c) ?></span><?php endforeach; ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
<?php
render_footer();
