# Codebase Design

This file defines how Cola Aí code should be shaped. It complements `CONTEXT.md`: the context file names the product concepts; this file decides where behaviour should live and what interfaces should stay small.

## Design Goal

Build deep modules: a small interface that hides a meaningful amount of behaviour. Callers should learn few methods and few parameters, while domain rules, persistence details, remote calls, and edge cases stay local to the module that owns them.

## Documentation Order

When changing code, read these in order:

1. `CONTEXT.md` for domain language.
2. `CODEBASE-DESIGN.md` for module design rules.
3. The nearest service `CLAUDE.md` for local conventions.
4. `app/brain/` pages for entity, service, and function-level details.

`CONTEXT.md` must stay implementation-free. `CODEBASE-DESIGN.md` may mention Laravel structure, seams, adapters, tests, and module ownership.

## System Shape

Cola Aí is organized as owned Laravel services:

**esporteam-auth** owns authentication identity, user records, global permissions, JWT issuance, two-factor authentication, impersonation, and audit logging.

**esporteam-workspace** owns administrative workspaces, members, invites, workspace status, and workspace administration.

**esporteam-back** owns sport discovery and participation: Perfil Esportivo, Modalidade, Pratica do Perfil, Professor, Aluno, Conexao, Grupo Esportivo, Disponibilidade, ideas, roadmap, and clustering.

Do not let `User` become the social/domain identity inside sport discovery. `User` belongs to auth. Discovery modules should work with `SportProfile`/Perfil Esportivo and reference auth users only by ID when needed.

## Laravel Module Roles

**Controllers**:
Thin HTTP adapters. They read validated requests, call one module interface, and return resources/responses. They must not contain domain branching, multi-step workflows, permission rules, or persistence orchestration.

**FormRequests**:
HTTP input validation and authorization that depends only on the request shape and authenticated caller. They should not contain workflow rules that require domain state transitions.

**Services**:
Primary application modules. A service method should represent a use case or cohesive domain capability, not a CRUD pass-through. If deleting the service would only move one Eloquent call into a controller, the service is too shallow.

**Models**:
Persistence-backed domain records plus local invariants and relationships. Models may protect small local rules, but workflows that involve multiple records, remote services, transactions, or policy decisions belong in services.

**Resources**:
HTTP output shape adapters. They must not decide domain state, permissions, matching, ranking, or workflow outcomes.

**Jobs and Events**:
Asynchronous adapters around use cases. A job should call a service interface instead of reimplementing the workflow.

**Clients**:
Adapters for remote services. They translate transport concerns into a small local interface and should be fail-safe only when the caller's use case explicitly allows degraded behaviour.

## Seam Rules

Use a seam when behaviour genuinely varies across it.

For owned remote services, define a local port at the seam and provide at least two adapters when useful: an HTTP adapter for production and an in-memory or fake adapter for tests. Examples: auth user lookup, audit logging, workspace lookup.

For database-backed modules, prefer testing through the service interface with the Laravel test database. Do not introduce repository seams unless there is a real second adapter or a strong testability reason.

For true external services, inject a port and test with a mock or fake adapter. Keep vendor SDK details out of use-case services.

One production adapter with no test adapter is usually just extra indirection. Avoid it unless it gives immediate leverage.

## Core Domain Modules

**Perfil Esportivo module**:
Owns creating and updating the sport identity, profile visibility, approximate location, practices, and availability. Its interface should let callers express intent such as "update profile", "replace practices", and "replace availability" without exposing persistence choreography.

**Professor module**:
Owns the professional layer on top of a Perfil Esportivo, including credentials, pricing, service radius, verification state, and explicit student relationships. Do not treat every enthusiast who wants to learn as an Aluno; Aluno requires a teacher relationship.

**Conexao module**:
Owns social relationships between sport profiles, including friendship requests and blocks. Blocking must remove conflicting friendship state and must be respected by discovery.

**Grupo Esportivo module**:
Owns group creation, membership, roles, status, capacity, and management permissions. Group management rules should stay behind the group module interface.

**Descoberta module**:
Should become the place for finding compatible profiles, teachers, groups, or opportunities. Ranking rules belong here, not in controllers or resources. The deterministic path must work without AI; AI may improve ranking or explanations later.

## Tests

Test through the module interface. For Laravel services, this usually means feature tests at the HTTP edge for contracts and unit/integration tests at the service interface for workflow behaviour.

Do not duplicate tests around shallow helpers once behaviour is covered at a deeper module interface. Tests should assert observable outcomes: records created, state transitions, returned results, emitted events, queued jobs, or HTTP payloads.

When a module depends on an owned remote service, test the module with a fake adapter and separately test the HTTP adapter contract.

## Naming

Use the domain terms from `CONTEXT.md` in code, tests, resources, routes, and UI labels where practical. Existing English class names may remain when they are already established, but new concepts should map clearly to the glossary.

Avoid adding generic names such as `Manager`, `Handler`, `Helper`, or `Util` for domain behaviour. Prefer a module name that says which domain capability it owns.

## Deepening Checklist

Before adding a new class or interface, ask:

- What behaviour will sit behind this interface?
- Can the caller express intent with fewer methods or simpler parameters?
- Would deleting this module spread complexity across multiple callers?
- Is this seam real, with more than one adapter or a clear test seam?
- Can tests cross the same interface that production callers use?
- Does this module preserve the vocabulary in `CONTEXT.md`?
