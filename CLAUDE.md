# Esporteam

Before changing code in this workspace, read:

1. `CONTEXT.md` for the product language.
2. `CODEBASE-DESIGN.md` for module and seam design.
3. The nearest service `CLAUDE.md` for local Laravel conventions.
4. The relevant `app/brain/` page before editing models, services, or public functions.

The product language is centered on sport discovery and participation. In discovery code, use Perfil Esportivo / `SportProfile` as the social identity; `User` belongs to authentication.

Keep controllers thin, put use-case behaviour behind service interfaces, use FormRequests for HTTP validation, and keep Resources limited to response shape.

## Agent skills

### Issue tracker

Issues and PRDs are tracked in GitHub Issues for this repo. See `docs/agents/issue-tracker.md`.

### Triage labels

The repo uses the default mattpocock/skills triage label vocabulary. See `docs/agents/triage-labels.md`.

### Domain docs

This is a single-context repo using root `CONTEXT.md` and root `docs/adr/`. See `docs/agents/domain.md`.
