# DSE 中文溫習站

這是 HKDSE 中文科十二篇範文的溫習網站，用 PHP 寫成，資料存放在 Supabase。

| 頁面 | 網址 | 內容 |
| --- | --- | --- |
| 首頁 | `/` | 功能入口、十二篇範文 |
| 範文 | `/fanwen`, `/fanwen?code=yueyang` | 原文、段落大意、主旨、寫作手法、溫習重點、重點字詞 |
| 字詞卡 | `/flashcards` | 706 張字詞卡，可按範文及類別（通假字／古今異義／詞類活用／一詞多義）篩選，「已掌握」標記存在學生自己的瀏覽器 |
| 字詞測驗 | `/quiz` | 隨機多項選擇題（10／20／30 題），交卷後即時評分 |
| 範文練習 | `/practice` | 每篇範文 5 題原創 DSE 模式題目（選擇題及短答），交卷後顯示答案及解說 |
| 寫作題目 | `/writing` | DSE 卷二歷屆寫作題目，實用寫作（甲部）與命題寫作分開，可按年份及題型篩選 |

如果 Supabase 未設定或連線失敗，網站會改用 `data/` 內的 JSON 檔案顯示內容，頁尾會出現提示，此時不會記錄成績。

## 部署（Vercel + Supabase）

### 1. 建立資料庫

最簡單的方法是在 Supabase dashboard 打開 **SQL Editor**：

1. 貼上 `supabase/migrations/20261121000000_init_schema.sql` 的全部內容，然後按 Run。
2. 貼上 `supabase/seed.sql` 的全部內容，然後按 Run。

兩個檔案都可以重複執行，不會產生重複資料。

如果你在 Supabase 用了 GitHub integration（Project Settings → Integrations → GitHub），請把 "Supabase directory" 設成 `dse-chinese-revision/supabase`（如果本資料夾就是 repo 根目錄，則設成 `supabase`）。這樣 migration 會在合併到 production branch 時自動執行。不過 Supabase 只會為 preview branch 自動執行 `seed.sql`，所以 production 仍然要在 SQL Editor 手動執行一次 `seed.sql`。

### 2. 安全設定

- 所有資料表都開啟了 Row Level Security。
- 內容資料表（`fanwen`、`vocab`、`practice_questions`、`writing_questions`）只供公開讀取，不能寫入。
- `writing_questions.source_url` 及 `verification` 是內部欄位，公開 API 讀不到。
- `quiz_attempts` 只可新增，不可讀取；分數和題數都有範圍檢查。
- 網站只需要 **anon / publishable key**，不需要也不應該使用 service_role / secret key。

### 3. 部署到 Vercel

1. 在 Vercel 匯入此 GitHub repo。如果本資料夾不是 repo 根目錄，請把 **Root Directory** 設成 `dse-chinese-revision`。
2. Framework Preset 選 **Other**，Build Command 和 Output Directory 留空。
3. 在 **Environment Variables** 加入以下兩個變數（數值在 Supabase → Project Settings → API）：
   - `SUPABASE_URL`，例如 `https://abcd1234.supabase.co`
   - `SUPABASE_ANON_KEY`，即 anon 或 publishable key
   如果你用 Vercel 的 Supabase integration，它會自動加入的 `NEXT_PUBLIC_SUPABASE_URL` 和 `NEXT_PUBLIC_SUPABASE_ANON_KEY` 也可以直接使用。
4. 按 Deploy。PHP 由 `vercel.json` 指定的社群 runtime [`vercel-php`](https://github.com/vercel-community/php) 執行，不需要其他設定。

### 4. 查看學生成績

在 Supabase → Table Editor → `quiz_attempts` 可以看到每次交卷的時間、類型、範文、分數及學生自填的暱稱。

## 本機測試

需要 PHP 8.1 或以上，並啟用 curl 擴充。

```bash
cd dse-chinese-revision
php -S localhost:8000 router.php                 # 只用 data/ 的 JSON
# 或連接 Supabase：
SUPABASE_URL=https://xxxx.supabase.co SUPABASE_ANON_KEY=xxxx php -S localhost:8000 router.php
```

## 修改內容

內容都存放在 `data/` 內：

- `data/fanwen/*.json`：範文
- `data/vocab.json`：字詞
- `data/practice.json`：練習題
- `data/writing.json`：寫作題目

修改後執行 `php scripts/build_seed.php` 重新產生 `supabase/seed.sql`，再到 SQL Editor 執行一次。

## 檔案結構

```
api/            每個頁面一個 PHP 檔（Vercel serverless function）
api/_lib/       共用程式：Supabase REST、資料讀取、版面
assets/         style.css、app.js
data/           內容 JSON（Supabase 不可用時的後備資料）
scripts/        build_seed.php
supabase/       config.toml、migrations/、seed.sql
router.php      本機 php -S 用的路由
vercel.json     Vercel 設定
```
