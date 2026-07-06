# esporteam-docker

Ambiente unificado de desenvolvimento do Esporteam e dos microsserviços esporteam que ele consome. Um único `make setup` clona os repos irmãos, gera chaves JWT, prepara `.env`s e sobe todos os containers.

## Serviços

| Container             | Repo                          | URL local              |
|-----------------------|-------------------------------|------------------------|
| `esporteam-postgres`       | (Postgres 17)                 | `localhost:5433`       |
| `esporteam-auth`          | `Esporteam-Tech/esporteam-auth`         | `http://127.0.0.1:8001` |
| `esporteam-workspace`     | `Esporteam-Tech/esporteam-workspace`    | `http://127.0.0.1:8002` |
| `esporteam-back`       | `Esporteam-Tech/esporteam-back`      | `http://127.0.0.1:8000` |
| `esporteam-front` | `Esporteam-Tech/esporteam-front`| `http://127.0.0.1:5173` |

Cada serviço é um repositório separado; este repo só carrega `docker-compose.yml`, os Dockerfiles de dev em `dockerfiles/`, o `postgres-init/` e o `setup.sh`.

## Setup (primeira vez)

Pré-requisitos: `docker`, `docker compose`, `gh` autenticado (`gh auth status`), `openssl`, `make`.

```bash
git clone git@github.com:Esporteam-Tech/esporteam-docker.git esporteam-docker
cd chama
make setup
```

O `make setup` (= `./setup.sh`):

1. Clona os 4 repos irmãos via `gh repo clone Esporteam-Tech/...` se ainda não existirem
2. Gera um keypair RS256 (se não existir) e distribui a chave pública para auth/workspace/esporteam
3. Cria `.env` a partir de `.env.example` em cada serviço e ajusta `DB_*` para o Postgres do compose
4. `docker compose up -d --build`

## Comandos do dia-a-dia

```bash
make up         # docker compose up -d
make down       # docker compose down
make logs       # tail dos logs
make ps         # status
make rebuild    # up --build
make keys       # regerar e redistribuir keypair JWT
make clean      # down -v (apaga dados!)
```

## Estrutura

```
chama/
├── docker-compose.yml
├── Makefile
├── setup.sh
├── postgres-init/           # cria DBs esporteam_auth, esporteam_workspace, esporteam_back
├── dockerfiles/             # imagens dev para serviços sem Dockerfile próprio
│   ├── esporteam.Dockerfile
│   └── front.Dockerfile
├── esporteam-auth/              # clonado por setup.sh — gitignored aqui
├── esporteam-workspace/         # idem
├── esporteam-back/           # idem
└── esporteam-front/     # idem
```

## Bancos

Postgres 17 com `esporteam_auth`, `esporteam_workspace` e `esporteam_back` criados automaticamente pelo `postgres-init/`. Usuário `esporteam` / senha `secret`. Para conectar de fora do compose, use porta `5433`.

## JWT

`esporteam-auth` assina tokens RS256 com `esporteam-auth/keys/private.pem` (gerado pelo setup). `esporteam-workspace` e `esporteam-back` validam com `keys/public.pem`. Para rotacionar:

```bash
make keys
make rebuild
```
