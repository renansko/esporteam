# Esporteam

Monorepo padrao para Esporteam com Vue no frontend, Laravel no backend e ambiente Docker separado.

## Projetos

- `esporteam-front`: Vue + Vite. Use composables para estado/efeitos reutilizaveis e services para acesso HTTP.
- `esporteam-back`: Laravel API principal. Controllers recebem requests, services concentram regra de negocio, models representam persistencia.
- `esporteam-auth`: Laravel auth service copiado da base anterior e renomeado para Esporteam.
- `esporteam-workspace`: Laravel workspace service copiado da base anterior e renomeado para Esporteam.
- `esporteam-docker`: Docker Compose, Makefile, setup e postgres-init.

## Estrutura padrao

Frontend:

```text
src/
  components/
  composables/
  features/
  services/
  stores/
  views/
```

Backend Laravel:

```text
app/
  Http/
    Controllers/
    Requests/
    Resources/
  Models/
  Services/
```

## Docker

```bash
cd esporteam-docker
make setup
```

Servicos locais:

- Back: `http://127.0.0.1:8000`
- Auth: `http://127.0.0.1:8001`
- Workspace: `http://127.0.0.1:8002`
- Front: `http://127.0.0.1:5173`
- Postgres: `localhost:5433`
