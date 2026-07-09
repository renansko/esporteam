---
name: implement
description: "Implement work from a PRD, issue, spec, or user request end-to-end: gather context, make focused code changes, validate, review, and commit."
disable-model-invocation: true
---

# Implement

Implement the work the user points at: PRD, issue number, issue URL, spec path, or direct request. Own the full loop: understand -> change -> validate -> review -> commit.

## Process

### 1. Gather context

If the user passed an issue reference, fetch the full issue and comments using `docs/agents/issue-tracker.md`. If the user passed a PRD/spec path, read it completely.

Before editing, read repo guidance that applies:

- `CONTEXT.md` for product language.
- `CODEBASE-DESIGN.md` for module design.
- Relevant `docs/adr/` entries.
- Nearest service `CLAUDE.md`.
- Relevant `app/brain/` page before editing models, services, or public functions.

Ask a question only when required behavior is missing and guessing would risk building the wrong thing. Otherwise make a reasonable assumption and state it.

### 2. Establish baseline

Check current branch and dirty worktree. Do not overwrite or revert user changes.

Identify validation commands from package scripts, composer scripts, CI config, or local docs. Prefer targeted commands while iterating, then broader commands near the end.

### 3. Slice the work

Break the work into the smallest vertical slices that can be verified independently. If useful, do a prefactor first to make the change easy, but keep it scoped to the requested work.

### 4. Implement

Use `/tdd` where practical, at pre-agreed seams. For bug fixes and behavior changes, prefer:

1. Failing targeted test or reproduction.
2. Minimal implementation.
3. Refactor only after green.

Respect existing patterns over new abstractions. Keep controllers thin, validation in request objects where the repo expects it, behavior in services/use cases, and resources focused on response shape.

### 5. Validate continuously

Run typechecking regularly.

Run targeted test files regularly.

Run lint/format commands if the touched area normally uses them.

Run the full relevant test suite once at the end. If full-suite cost is excessive or environment blocks it, run the broadest practical substitute and report the gap.

### 6. Review

Once implementation is green, use `/code-review` against the correct fixed point. Fix material findings, then rerun the relevant validation.

### 7. Commit

Inspect `git diff` and `git status` before staging. Stage only files changed for this task.

Commit to the current branch after validation and review. Include the issue number in the commit message when one exists.

Do not commit if required validation is failing unless the user explicitly approves committing with known failures.

### 8. Report

Final response includes:

- What changed.
- Tests/checks run and results.
- Commit hash.
- Any known gaps or follow-up work.
