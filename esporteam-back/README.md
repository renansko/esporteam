# Cola Aí

Backend do app mobile Cola Aí: descoberta local de pessoas, professores, aulas e sessoes esportivas proximas.

MVP em construção — ver [PRD.md](PRD.md) e [issues/](issues/).

## Stack

- Laravel 13 + PHP 8.3
- PostgreSQL 17 (via Docker)
- JWT RS256 via `esporteam-auth`
- `app/brain` como memoria tecnica do backend

## Setup local

```bash
# 1. dependências
composer install
npm install

# 2. variáveis de ambiente
cp .env.example .env
php artisan key:generate

# 3. subir Postgres (porta 5433 para não conflitar com instalações locais)
docker compose up -d

# 4. migrar
php artisan migrate

# 5. rodar
php artisan serve            # http://127.0.0.1:8000
```

Healthcheck: `GET /api/health` → `{"status":"ok"}`.

## Estrutura

- `app/`, `routes/`, `database/` — backend Laravel
- `docker-compose.yml` — Postgres local na porta `5433`
- `issues/` — backlog do MVP em arquivos Markdown
- `PRD.md` — visão e escopo
- `app/brain/` — memoria de arquitetura, produto e contratos importantes
