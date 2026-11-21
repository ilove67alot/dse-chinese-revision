-- DSE 中文溫習站: content tables (public read-only) and quiz attempts (insert-only).

create table if not exists public.fanwen (
  code        text primary key,
  sort_order  int  not null,
  title       text not null,
  author      text,
  dynasty     text,
  source      text,
  genre       text,
  intro       text,
  sections    jsonb not null default '[]'::jsonb,
  outline     jsonb not null default '[]'::jsonb,
  theme       text,
  techniques  jsonb not null default '[]'::jsonb,
  tips        jsonb not null default '[]'::jsonb
);

create table if not exists public.vocab (
  id          text primary key,
  fanwen_code text not null references public.fanwen (code) on delete cascade,
  piece       text,
  word        text not null,
  sentence    text,
  definition  text not null,
  pos         text,
  categories  text[] not null default '{}',
  tongjia     text,
  note        text
);
create index if not exists vocab_fanwen_code_idx on public.vocab (fanwen_code);
create index if not exists vocab_categories_idx on public.vocab using gin (categories);

create table if not exists public.practice_questions (
  id          text primary key,
  fanwen_code text not null references public.fanwen (code) on delete cascade,
  sort_order  int  not null default 0,
  qtype       text not null check (qtype in ('mc', 'short')),
  prompt      text not null,
  options     jsonb,
  answer      text not null,
  explanation text,
  marks       int  not null default 1
);
create index if not exists practice_questions_fanwen_code_idx on public.practice_questions (fanwen_code);

create table if not exists public.writing_questions (
  id           text primary key,
  year         int,
  paper        text not null,
  part         text check (part in ('甲部', '乙部')),
  question_no  int  not null,
  type         text not null,
  genre        text,
  prompt       text not null,
  has_material boolean not null default false,
  has_image    boolean not null default false,
  source_url   text,
  verification text
);
comment on column public.writing_questions.source_url is 'Internal reference only; not readable through the public API.';
comment on column public.writing_questions.verification is 'Internal note on how the wording was checked; not readable through the public API.';

create table if not exists public.quiz_attempts (
  id          bigint generated always as identity primary key,
  created_at  timestamptz not null default now(),
  kind        text not null check (kind in ('vocab', 'practice')),
  fanwen_code text,
  category    text,
  score       int  not null,
  total       int  not null,
  nickname    text check (char_length(nickname) <= 40),
  check (total between 1 and 100 and score between 0 and total)
);

-- Row Level Security: on for every table.
alter table public.fanwen             enable row level security;
alter table public.vocab              enable row level security;
alter table public.practice_questions enable row level security;
alter table public.writing_questions  enable row level security;
alter table public.quiz_attempts      enable row level security;

-- Start from no table privileges for the API roles, then grant only what the site needs.
revoke all on public.fanwen, public.vocab, public.practice_questions, public.writing_questions, public.quiz_attempts
  from anon, authenticated;

grant select on public.fanwen, public.vocab, public.practice_questions to anon, authenticated;
-- writing_questions: everything except the internal source_url / verification columns.
grant select (id, year, paper, part, question_no, type, genre, prompt, has_material, has_image)
  on public.writing_questions to anon, authenticated;
-- quiz_attempts: insert only; nobody can read attempts through the public API.
grant insert (kind, fanwen_code, category, score, total, nickname) on public.quiz_attempts to anon, authenticated;

drop policy if exists "Public read" on public.fanwen;
create policy "Public read" on public.fanwen for select to anon, authenticated using (true);
drop policy if exists "Public read" on public.vocab;
create policy "Public read" on public.vocab for select to anon, authenticated using (true);
drop policy if exists "Public read" on public.practice_questions;
create policy "Public read" on public.practice_questions for select to anon, authenticated using (true);
drop policy if exists "Public read" on public.writing_questions;
create policy "Public read" on public.writing_questions for select to anon, authenticated using (true);
drop policy if exists "Students can submit attempts" on public.quiz_attempts;
create policy "Students can submit attempts" on public.quiz_attempts for insert to anon, authenticated with check (true);
