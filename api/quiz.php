<?php
declare(strict_types=1);
require_once __DIR__ . '/_lib/layout.php';

const QUIZ_SIZES = [10, 20, 30];

$fanwen = query_param('fanwen');
$category = query_param('category');
$size = (int) query_param('size', '10');
$titles = fanwen_titles();
if (!isset($titles[$fanwen])) {
    $fanwen = '';
}
if (!in_array($category, VOCAB_CATEGORIES, true)) {
    $category = '';
}
if (!in_array($size, QUIZ_SIZES, true)) {
    $size = 10;
}

$allVocab = get_all_vocab();
$byId = array_column($allVocab, null, 'id');

/** Picks three wrong definitions, preferring the same 範文 so options look plausible. */
function build_options(array $entry, array $allVocab): array
{
    $correct = $entry['definition'];
    $sameFanwen = [];
    $others = [];
    foreach ($allVocab as $v) {
        $d = $v['definition'];
        if ($d === $correct || mb_strpos($d, $correct) !== false || mb_strpos($correct, $d) !== false) {
            continue;
        }
        if ($v['fanwen_code'] === $entry['fanwen_code']) {
            $sameFanwen[$d] = true;
        } else {
            $others[$d] = true;
        }
    }
    $pool = array_keys($sameFanwen);
    shuffle($pool);
    $wrong = array_slice($pool, 0, 3);
    if (count($wrong) < 3) {
        $rest = array_keys($others);
        shuffle($rest);
        $wrong = array_merge($wrong, array_slice($rest, 0, 3 - count($wrong)));
    }
    $options = array_merge([$correct], $wrong);
    shuffle($options);
    return $options;
}

$results = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ids = array_values(array_filter((array) ($_POST['ids'] ?? []), fn($id) => is_string($id) && isset($byId[$id])));
    $answers = (array) ($_POST['answer'] ?? []);
    $results = [];
    $score = 0;
    foreach (array_slice($ids, 0, max(QUIZ_SIZES)) as $id) {
        $entry = $byId[$id];
        $chosen = is_string($answers[$id] ?? null) ? $answers[$id] : '';
        $isCorrect = $chosen === $entry['definition'];
        $score += $isCorrect ? 1 : 0;
        $results[] = ['entry' => $entry, 'chosen' => $chosen, 'correct' => $isCorrect];
    }
    $saved = save_attempt('vocab', $fanwen, $category, $score, count($results), query_param('nickname'));
}

render_header('字詞測驗', 'quiz');
?>
<h1 class="page-title">字詞測驗</h1>

<?php if ($results !== null): ?>
  <?php $total = count($results); ?>
  <section class="score-card">
    <p class="score"><strong><?= $score ?></strong> / <?= $total ?></p>
    <p><?= $total && $score === $total ? '全對，做得好！' : '看看下面的解說，再試一次吧。' ?></p>
    <?php if ($saved): ?><p class="muted small">成績已記錄。</p><?php endif; ?>
    <div class="hero-actions">
      <a class="btn btn-primary" href="/quiz.php?<?= h(http_build_query(['fanwen' => $fanwen, 'category' => $category, 'size' => $size])) ?>">再做一次</a>
      <a class="btn" href="/flashcards.php?<?= h(http_build_query(['fanwen' => $fanwen, 'category' => $category])) ?>">用字詞卡溫習</a>
    </div>
  </section>
  <ol class="result-list">
    <?php foreach ($results as $r): $v = $r['entry']; ?>
      <li class="result <?= $r['correct'] ? 'is-correct' : 'is-wrong' ?>">
        <p class="q-sentence"><?= highlight((string) $v['sentence'], $v['word']) ?> <span class="muted small">（<?= h($titles[$v['fanwen_code']] ?? '') ?>）</span></p>
        <p>「<?= h($v['word']) ?>」：<strong><?= h($v['definition']) ?></strong><?= $v['tongjia'] ? '（通「' . h($v['tongjia']) . '」）' : '' ?> <span class="muted small"><?= h($v['pos']) ?></span></p>
        <?php if (!$r['correct']): ?>
          <p class="your-answer">你的答案：<?= $r['chosen'] !== '' ? h($r['chosen']) : '（未作答）' ?></p>
        <?php endif; ?>
        <?php if ($v['note']): ?><p class="muted small"><?= h($v['note']) ?></p><?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ol>
<?php else: ?>
  <?php
  $pool = filter_vocab($allVocab, $fanwen, $category);
  shuffle($pool);
  $questions = array_slice($pool, 0, $size);
  ?>
  <form class="filters" method="get" action="/quiz.php">
    <label>範文 <?= fanwen_select($fanwen) ?></label>
    <label>類別 <?= category_select($category) ?></label>
    <label>題數
      <select name="size"><?php foreach (QUIZ_SIZES as $n): ?><option value="<?= $n ?>"<?= $n === $size ? ' selected' : '' ?>><?= $n ?> 題</option><?php endforeach; ?></select>
    </label>
    <button class="btn" type="submit">重新出題</button>
  </form>

  <?php if (!$questions): ?>
    <p class="empty">沒有符合條件的字詞，請改選其他範文或類別。</p>
  <?php else: ?>
    <form method="post" action="/quiz.php" class="quiz-form">
      <input type="hidden" name="fanwen" value="<?= h($fanwen) ?>">
      <input type="hidden" name="category" value="<?= h($category) ?>">
      <input type="hidden" name="size" value="<?= $size ?>">
      <ol class="question-list">
        <?php foreach ($questions as $i => $v): ?>
          <li class="question">
            <input type="hidden" name="ids[]" value="<?= h($v['id']) ?>">
            <p class="q-sentence"><?= highlight((string) $v['sentence'], $v['word']) ?> <span class="muted small">（<?= h($titles[$v['fanwen_code']] ?? '') ?>）</span></p>
            <p class="q-prompt">句中「<?= h($v['word']) ?>」的意思是：</p>
            <div class="options">
              <?php foreach (build_options($v, $allVocab) as $opt): ?>
                <label class="option"><input type="radio" name="answer[<?= h($v['id']) ?>]" value="<?= h($opt) ?>"> <span><?= h($opt) ?></span></label>
              <?php endforeach; ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ol>
      <div class="submit-row">
        <label>姓名或班別（選填）<input type="text" name="nickname" maxlength="40" autocomplete="nickname"></label>
        <button class="btn btn-primary" type="submit">提交答案</button>
      </div>
    </form>
  <?php endif; ?>
<?php endif; ?>
<?php
render_footer();
