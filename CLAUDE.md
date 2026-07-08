# Esporteam

Before changing code in this workspace, read:

1. `CONTEXT.md` for the product language.
2. `CODEBASE-DESIGN.md` for module and seam design.
3. The nearest service `CLAUDE.md` for local Laravel conventions.
4. The relevant `app/brain/` page before editing models, services, or public functions.

The product language is centered on sport discovery and participation. In discovery code, use Perfil Esportivo / `SportProfile` as the social identity; `User` belongs to authentication.

Keep controllers thin, put use-case behaviour behind service interfaces, use FormRequests for HTTP validation, and keep Resources limited to response shape.

## Local runtime

This workspace is normally run through `esporteam-docker/docker-compose.yml`. Before starting any local server, check the compose runtime first.

- Frontend: `esporteam-front` runs Vite inside Docker and owns `http://127.0.0.1:5173`.
- Backend API: `esporteam-back` is published on `http://127.0.0.1:8000`.
- Auth API: `esporteam-auth` is published on `http://127.0.0.1:8001`.
- Workspace API: `esporteam-workspace` is published on `http://127.0.0.1:8002`.

Do not start a second host `npm run dev` server for `esporteam-front` just because port `5173` is busy. In this project, that usually means the Docker frontend is already running. Use the existing Docker service for browser review and HTTP smoke checks, or run commands inside the service with `docker compose exec` from `esporteam-docker`.

In this WSL workspace, if plain `docker compose ps` cannot reach `/var/run/docker.sock`, check the same compose stack through Docker Desktop's CLI at `/mnt/c/Program Files/Docker/Docker/resources/bin/docker.exe` before assuming the app is stopped.

## Agent skills

### Issue tracker

Issues and PRDs are tracked in GitHub Issues for this repo. See `docs/agents/issue-tracker.md`.

### Triage labels

The repo uses the default mattpocock/skills triage label vocabulary. See `docs/agents/triage-labels.md`.

### Domain docs

This is a single-context repo using root `CONTEXT.md` and root `docs/adr/`. See `docs/agents/domain.md`.
