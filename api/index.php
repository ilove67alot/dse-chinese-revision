<?php
declare(strict_types=1);
require_once __DIR__ . '/_lib/layout.php';

$fanwen = get_fanwen_list();
$vocabCount = count(get_all_vocab());

render_header('DSE 中文溫習站', 'home');
?>
<section class="hero">
  <p class="eyebrow">HKDSE 中國語文</p>
  <h1>十二篇範文，一站溫習</h1>
  <p class="lead">原文、段落大意、主旨及寫作手法，配合 <?= $vocabCount ?> 個重點字詞的字詞卡和測驗、範文練習題，以及歷屆卷二寫作題目。</p>
  <div class="hero-actions">
    <a class="btn btn-primary" href="/quiz.php">開始字詞測驗</a>
    <a class="btn" href="/fanwen.php">瀏覽範文</a>
  </div>
</section>

<section class="feature-grid">
  <a class="feature" href="/fanwen.php">
    <h2>範文筆記</h2>
    <p>原文、段落大意、主旨、寫作手法及溫習重點。</p>
  </a>
  <a class="feature" href="/flashcards.php">
    <h2>字詞卡</h2>
    <p>按範文或類別（通假字、古今異義、詞類活用、一詞多義）翻卡溫習。</p>
  </a>
  <a class="feature" href="/quiz.php">
    <h2>字詞測驗</h2>
    <p>隨機抽題的選擇題，提交後即時顯示答案及解說。</p>
  </a>
  <a class="feature" href="/practice.php">
    <h2>範文練習</h2>
    <p>仿 DSE 題型的選擇題及短答題，附參考答案。</p>
  </a>
  <a class="feature" href="/writing.php">
    <h2>寫作題目</h2>
    <p>歷屆卷二題目，可按年份及實用寫作／命題寫作篩選。</p>
  </a>
</section>

<section>
  <h2 class="section-title">十二篇範文</h2>
  <div class="fanwen-grid">
    <?php foreach ($fanwen as $item): ?>
      <a class="fanwen-card" href="/fanwen.php?code=<?= h($item['code']) ?>">
        <span class="fanwen-no"><?= (int) $item['sort_order'] ?></span>
        <strong><?= h($item['title']) ?></strong>
        <span class="muted"><?= h($item['dynasty']) ?>・<?= h($item['author']) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php
render_footer();
