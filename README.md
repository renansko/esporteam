# Cola Aí

Monorepo padrao para Cola Aí com Vue no frontend, Laravel no backend e ambiente Docker separado.

Marca publica: **Cola Aí**. Os nomes tecnicos `esporteam-*` permanecem enquanto a infraestrutura nao for renomeada.

## Projetos

- `esporteam-front`: Vue + Vite. Use composables para estado/efeitos reutilizaveis e services para acesso HTTP.
- `esporteam-back`: Laravel API principal. Controllers recebem requests, services concentram regra de negocio, models representam persistencia.
- `esporteam-auth`: Laravel auth service.
- `esporteam-workspace`: Laravel workspace service.
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
