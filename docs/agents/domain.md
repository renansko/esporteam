# Domain Docs

How the engineering skills should consume this repo's domain documentation when exploring the codebase.

## Before exploring, read these

- `CONTEXT.md` at the repo root for product language.
- `CODEBASE-DESIGN.md` at the repo root for module and seam design.
- `docs/adr/` for architectural decisions that touch the area being changed.
- The nearest service `CLAUDE.md` for local conventions.

If any of these files do not exist, proceed silently.

## File structure

This is a single-context repo:

```text
/
├── CONTEXT.md
├── CODEBASE-DESIGN.md
├── docs/adr/
└── esporteam-front/
```

## Use the glossary's vocabulary

When output names a domain concept, use the terms defined in `CONTEXT.md`, including Perfil Esportivo, Entusiasta, Professor, Organizador, Sessao Esportiva, Modalidade, Descoberta, Conexao, Grupo Esportivo and Disponibilidade.

## Flag ADR conflicts

If output contradicts an existing ADR, surface it explicitly rather than silently overriding it.
