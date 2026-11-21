<?php
declare(strict_types=1);
require_once __DIR__ . '/_lib/layout.php';

const OPTION_LETTERS = ['A', 'B', 'C', 'D', 'E', 'F'];

$code = query_param('code');
$titles = fanwen_titles();

if (!isset($titles[$code])) {
    render_header('範文練習', 'practice');
    ?>
    <h1 class="page-title">範文練習</h1>
    <p class="lead">仿 DSE 題型的原創練習：選擇題提交後自動批改，短答題可對照參考答案自行評分。</p>
    <div class="fanwen-grid">
      <?php foreach (get_fanwen_list() as $item): ?>
        <a class="fanwen-card" href="/practice.php?code=<?= h($item['code']) ?>">
          <span class="fanwen-no"><?= (int) $item['sort_order'] ?></span>
          <strong><?= h($item['title']) ?></strong>
          <span class="muted"><?= h($item['author']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
    <?php
    render_footer();
    return;
}

$questions = get_practice_questions($code);
$submitted = $_SERVER['REQUEST_METHOD'] === 'POST';
$answers = $submitted ? (array) ($_POST['answer'] ?? []) : [];
$mcTotal = 0;
$mcScore = 0;
$saved = false;
if ($submitted) {
    foreach ($questions as $q) {
        if ($q['qtype'] === 'mc') {
            $mcTotal++;
            $mcScore += (($answers[$q['id']] ?? '') === $q['answer']) ? 1 : 0;
        }
    }
    $saved = save_attempt('practice', $code, null, $mcScore, $mcTotal, query_param('nickname'));
}

render_header('範文練習：' . $titles[$code], 'practice');
?>
<p class="breadcrumb"><a href="/practice.php">範文練習</a> ／ <?= h($titles[$code]) ?></p>
<h1 class="page-title"><?= h($titles[$code]) ?>　練習</h1>
<p class="lead">先作答，再按「提交」查看答案及評分參考。<a href="/fanwen.php?code=<?= h($code) ?>">溫習範文筆記</a></p>

<?php if ($submitted): ?>
  <section class="score-card">
    <p class="score">選擇題：<strong><?= $mcScore ?></strong> / <?= $mcTotal ?></p>
    <p>短答題請對照參考答案自行評分。</p>
    <?php if ($saved): ?><p class="muted small">成績已記錄。</p><?php endif; ?>
    <div class="hero-actions"><a class="btn btn-primary" href="/practice.php?code=<?= h($code) ?>">重新作答</a></div>
  </section>
<?php endif; ?>

<?php if (!$questions): ?>
  <p class="empty">這篇範文暫時未有練習題。</p>
<?php else: ?>
<form method="post" action="/practice.php?code=<?= h($code) ?>" class="quiz-form">
  <ol class="question-list">
    <?php foreach ($questions as $q):
        $given = is_string($answers[$q['id']] ?? null) ? $answers[$q['id']] : '';
        $isMc = $q['qtype'] === 'mc';
        $state = $submitted && $isMc ? ($given === $q['answer'] ? ' is-correct' : ' is-wrong') : '';
    ?>
      <li class="question<?= $state ?>">
        <p class="q-meta"><span class="tag"><?= $isMc ? '選擇題' : '短答題' ?></span> <span class="muted small"><?= (int) $q['marks'] ?> 分</span></p>
        <p class="q-prompt"><?= h($q['prompt']) ?></p>
        <?php if ($isMc): ?>
          <div class="options">
            <?php foreach ($q['options'] as $i => $opt): $letter = OPTION_LETTERS[$i]; ?>
              <label class="option<?= $submitted && $letter === $q['answer'] ? ' is-answer' : '' ?>">
                <input type="radio" name="answer[<?= h($q['id']) ?>]" value="<?= $letter ?>"<?= $given === $letter ? ' checked' : '' ?><?= $submitted ? ' disabled' : '' ?>>
                <span><?= $letter ?>. <?= h($opt) ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <textarea name="answer[<?= h($q['id']) ?>]" rows="4" maxlength="2000" placeholder="在此作答"<?= $submitted ? ' readonly' : '' ?>><?= h($given) ?></textarea>
        <?php endif; ?>

        <?php if ($submitted): ?>
          <div class="answer-box">
            <?php if ($isMc): ?>
              <p><strong>答案：<?= h($q['answer']) ?></strong><?= $given !== '' && $given !== $q['answer'] ? '　（你選了 ' . h($given) . '）' : ($given === '' ? '　（未作答）' : '') ?></p>
            <?php else: ?>
              <p><strong>參考答案：</strong><?= h($q['answer']) ?></p>
            <?php endif; ?>
            <?php if (!empty($q['explanation'])): ?><p class="muted"><?= h($q['explanation']) ?></p><?php endif; ?>
          </div>
        <?php endif; ?>
      </li>
    <?php endforeach; ?>
  </ol>
  <?php if (!$submitted): ?>
    <div class="submit-row">
      <label>姓名或班別（選填）<input type="text" name="nickname" maxlength="40" autocomplete="nickname"></label>
      <button class="btn btn-primary" type="submit">提交</button>
    </div>
  <?php endif; ?>
</form>
<?php endif; ?>
<?php
render_footer();
