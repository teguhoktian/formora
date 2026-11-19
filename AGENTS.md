# Instructions for Codex

1. Read `docs/IMPLEMENTATION_PLAN.md` and `docs/PROGRESS.md` before work.
2. Work on only one approved stage at a time.
3. Inspect `git status` before editing. Never discard user changes.
4. Follow the selected stack: Laravel, PHP 8.3, PostgreSQL, Livewire, Blade,
   Alpine, Tailwind, Vite, and SortableJS.
5. Keep the application a modular monolith. No Next.js, Supabase, Vercel,
   Redis, Docker, microservices, or Filament end-user dashboard unless approved.
6. Enforce workspace isolation and server-side authorization for every
   business operation.
7. Never log raw personal/sensitive submission values.
8. Add/update automated tests for behavior and security boundaries.
9. Run relevant tests and report exact commands/results.
10. Update `docs/PROGRESS.md` after each approved stage.
11. Do not proceed to the next stage without user approval.
12. Do not run destructive database or Git commands.
13. Keep responses concise; inspect only relevant files and avoid repeating
    repository context.

## Efficient AI Agent Workflow

1. Read `AGENTS.md`, `docs/PROGRESS.md`, and only the relevant sections of `docs/IMPLEMENTATION_PLAN.md` before working.
2. Do not repeatedly read entire documentation files when the relevant context is already available.
3. Inspect only files directly related to the current task.
4. Do not repeat audits or checks that have already passed unless changes could invalidate their results.
5. Work in small, logically grouped batches to minimize unnecessary tool calls and token usage.
6. Prefer concise command output. Avoid printing entire files, database contents, or verbose logs.
7. Do not create additional planning documents unless they provide lasting operational value.
8. Reuse existing scripts, documentation, and test results where valid.
9. Report only: actions performed, results, failures, risks, and decisions required.
10. Never skip security checks, backups, tests, or approval gates merely to save tokens.
11. Never make destructive changes without explicit authorization.
12. Update `docs/PROGRESS.md` once per completed batch, not after every minor action.
