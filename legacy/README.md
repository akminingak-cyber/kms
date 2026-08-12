# Quarantined code

Nothing in here is part of the platform. It is kept so that removing it stays a decision someone
takes deliberately rather than one that happens by accident.

## `bolt-starter/`

The repository arrived containing a generated Vite/React starter at its root — the upload in commit
`056cd8f`. The inspection report recorded it as findings **R3** and **R14**, and
[`ARCHITECTURE.md` OQ-17](../ARCHITECTURE.md#13-open-questions) asked what should happen to it.

Three facts about it, all verifiable:

- **It does not build.** `index.html` loads `/src/main.tsx`, and there is no `src/` directory. There
  never was one in this repository's history.
- **It declares `@supabase/supabase-js`.** Supabase is a hosted Postgres and auth product. That
  implies a data and identity direction which conflicts with the one this platform actually took —
  self-managed PostgreSQL and our own authentication.
- **It is not KMS TV.** Its `index.html` describes "Enterprise Technology Infrastructure &
  Security" and carries Open Graph images pointing at the scaffolding tool that produced it.

It was moved here in Phase 4 rather than deleted, because Phase 4 needed the repository root to
become a real workspace and could not leave a second, broken front-end root sitting in it. Moving is
reversible and visible; deleting is the product owner's call, and **OQ-17 stays open until they make
it**.

If the answer is "delete it", `git rm -r legacy/` is the whole change. If the answer is "that was
our marketing site", the files are here and its history is intact.
