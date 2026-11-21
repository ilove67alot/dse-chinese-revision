<?php
declare(strict_types=1);

require_once __DIR__ . '/data.php';

function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function query_param(string $name, string $default = ''): string
{
    $value = $_GET[$name] ?? $_POST[$name] ?? $default;
    return is_string($value) ? trim($value) : $default;
}

/** Escapes $sentence and wraps the first occurrence of $word in <mark>. */
function highlight(string $sentence, string $word): string
{
    $safe = h($sentence);
    $needle = h($word);
    if ($needle === '' || ($pos = mb_strpos($safe, $needle)) === false) {
        return $safe;
    }
    return mb_substr($safe, 0, $pos) . '<mark>' . $needle . '</mark>' . mb_substr($safe, $pos + mb_strlen($needle));
}

function fanwen_select(string $selected, string $allLabel = '全部範文'): string
{
    $html = '<select name="fanwen" id="fanwen"><option value="">' . h($allLabel) . '</option>';
    foreach (fanwen_titles() as $code => $title) {
        $html .= '<option value="' . h($code) . '"' . ($code === $selected ? ' selected' : '') . '>' . h($title) . '</option>';
    }
    return $html . '</select>';
}

function category_select(string $selected): string
{
    $html = '<select name="category" id="category"><option value="">全部類別</option>';
    foreach (VOCAB_CATEGORIES as $cat) {
        $html .= '<option value="' . h($cat) . '"' . ($cat === $selected ? ' selected' : '') . '>' . h($cat) . '</option>';
    }
    return $html . '</select>';
}

function render_header(string $title, string $active = ''): void
{
    $nav = [
        'home' => ['/', '主頁'],
        'fanwen' => ['/fanwen.php', '範文'],
        'flashcards' => ['/flashcards.php', '字詞卡'],
        'quiz' => ['/quiz.php', '字詞測驗'],
        'practice' => ['/practice.php', '範文練習'],
        'writing' => ['/writing.php', '寫作題目'],
    ];
    $pageTitle = $active === 'home' ? $title : $title . '｜DSE 中文溫習站';
    ?>
<!DOCTYPE html>
<html lang="zh-Hant-HK">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($pageTitle) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;500;700&family=Noto+Serif+TC:wght@500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/style.css">
  <link rel="icon" href="/assets/favicon.svg" type="image/svg+xml">
</head>
<body>
  <header class="site-header">
    <div class="wrap header-inner">
      <a class="brand" href="/"><span class="seal">文</span><span>DSE 中文溫習站</span></a>
      <button class="nav-toggle" type="button" aria-controls="site-nav" aria-expanded="false">選單</button>
      <nav id="site-nav" class="site-nav">
        <?php foreach ($nav as $key => [$href, $label]): ?>
          <a href="<?= $href ?>"<?= $key === $active ? ' aria-current="page"' : '' ?>><?= h($label) ?></a>
        <?php endforeach; ?>
      </nav>
    </div>
  </header>
  <main class="wrap">
<?php
}

function render_footer(): void
{
    $mode = data_mode();
    ?>
  </main>
  <footer class="site-footer">
    <div class="wrap">
      <p>HKDSE 中國語文 十二篇指定文言經典精選篇章溫習站</p>
      <?php if ($mode === 'local'): ?>
        <p class="mode-note">示範模式：未連接資料庫，正使用內置題庫，測驗成績不會儲存。</p>
      <?php elseif ($mode === 'fallback'): ?>
        <p class="mode-note">暫時未能連接資料庫，正使用內置題庫，測驗成績不會儲存。</p>
      <?php endif; ?>
    </div>
  </footer>
  <script src="/assets/app.js" defer></script>
</body>
</html>
<?php
}
