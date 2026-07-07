# Bruno Collection — esporteam-back

Collection de requests da API do `esporteam-back` (e dos serviços vizinhos que ele consome) versionada em git.

## Como usar

1. Instale o [Bruno](https://www.usebruno.com/) (desktop).
2. *Open Collection* → aponte para esta pasta (`bruno/`).
3. Selecione o environment **local** no canto superior direito.
4. Rode `auth/Login` (ou `auth/Register`) → o token cai automaticamente em `vars:secret → jwtToken` via `script:post-response`.
5. Rode `auth/Workspace Select` → sobrescreve `jwtToken` com o token escopado de workspace.
6. Agora todos os requests autenticados funcionam.

## Fluxo de auth (cadeia)

```
auth/Register OR auth/Login              → jwtToken (sem workspace)
workspaces/List OR workspaces/Create     → descobre workspace_id
auth/Workspace Select                    → jwtToken (com workspace claim)
me/Get Me                                → user + workspace
ideas/List Ideas / Create Idea           → endpoints do Esporteam
roadmap/* + roadmap/cluster/*            → roadmap e clustering com IA
```

## Estrutura

- `bruno.json` — config da collection
- `environments/` — variáveis (`baseUrl`, `authServiceUrl`, `workspaceServiceUrl`, segredos)
- `health.bru` — health check do Esporteam (sem auth)
- `auth/` — endpoints de identidade no `esporteam-auth` (register, login, workspace/select)
- `workspaces/` — endpoints de workspace no `esporteam-workspace` (list, create)
- `me/` — `/api/me` do Esporteam
- `connections/` — convites, interesses e bloqueios entre perfis esportivos
- `reports/` — denuncias para moderacao
- `ideas/` — CRUD de ideias no Esporteam
- `roadmap/` — listagem e drilldown de RoadmapItems
- `roadmap/cluster/` — dispatch e auditoria de runs de clustering com IA

## Convenções

- URLs sempre usam `{{baseUrl}}` (Esporteam), `{{authServiceUrl}}` (esporteam-auth) ou `{{workspaceServiceUrl}}` (esporteam-workspace) — nunca hardcode host/porta.
- Segredos (`jwtToken`, etc.) vão em `vars:secret` — Bruno persiste localmente fora do `.bru` versionado.
- Um arquivo `.bru` por endpoint; agrupar por recurso (`auth/`, `workspaces/`, `ideas/`, `me/`, ...).
- Ao adicionar/alterar um endpoint em `routes/api.php` (Esporteam) ou nos serviços upstream consumidos pelo Esporteam, atualizar a collection no mesmo PR.
- Requests que devolvem um JWT (`auth/Login`, `auth/Register`, `auth/Workspace Select`) salvam o token em `jwtToken` via `script:post-response` — não precisa colar manual.
