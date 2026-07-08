# PRD: Front-end MVP de Descoberta e Participacao

## Problem Statement

Entusiastas ainda nao tem uma experiencia de front-end centrada em Descoberta para encontrar Sessoes Esportivas, entender rapidamente se uma sessao e aberta ou passa por curadoria, demonstrar interesse, acompanhar o estado das suas partidas e navegar por alternativas acessiveis ao swipe e ao mapa.

O front atual esta estruturado em Vue, Pinia e servicos HTTP, mas a experiencia principal ainda nao reflete as telas de referencia do MVP mobile: Descobrir, Mapa/Lista, Detalhe aberto, Detalhe com curadoria, Partidas e Perfil. A implementacao precisa preservar a linguagem de dominio do Esporteam, principalmente a diferenca entre autenticacao de usuario e Perfil Esportivo na Descoberta.

## Solution

Construir uma experiencia front-end responsiva inspirada no layout mobile das referencias, usando Vue 3, Pinia e Axios, com componentes reutilizaveis de design system para Sessoes Esportivas.

O MVP entrega o modo Participante para Entusiastas: uma aba Descobrir com pilha de cards e botoes equivalentes ao swipe, uma aba Mapa com alternancia para Lista, telas de detalhe para sessoes abertas e com curadoria, uma aba Partidas para acompanhar estados de participacao e uma area de Perfil que expõe o Perfil Esportivo e a futura troca de modo entre Participante e Anfitriao.

Sessoes abertas confirmam participacao diretamente. Sessoes com curadoria criam uma solicitacao pendente que depende do Anfitriao da Sessao. Todo estado visual deve ser comunicado por icone, texto e cor, nunca apenas por cor.

## User Stories

1. As an Entusiasta, I want to open the app and land in Descobrir, so that I can immediately see compatible Sessoes Esportivas.
2. As an Entusiasta, I want to see one Sessao Esportiva per card, so that I can decide without scanning a dense list.
3. As an Entusiasta, I want each card to show Modalidade, Anfitriao da Sessao, distance, date/time, Nivel Esportivo and participant count, so that I can judge fit quickly.
4. As an Entusiasta, I want each card to show whether the sessao is open or curated, so that I know what happens after I show interest.
5. As an Entusiasta, I want to tap Tenho interesse, so that I can participate or request approval.
6. As an Entusiasta, I want to tap Pular, so that I can dismiss a sessao that does not fit.
7. As an Entusiasta, I want to tap Voltar, so that I can recover a previously skipped or liked card.
8. As an Entusiasta, I want swipe gestures to work on the card, so that the discovery flow feels fast on touch devices.
9. As an Entusiasta using assistive technology, I want visible buttons equivalent to every swipe gesture, so that no critical action depends only on gesture control.
10. As an Entusiasta, I want reduced-motion behavior to replace card flight animations, so that the UI respects my system preference.
11. As an Entusiasta, I want filters for Modalidade, distance, Nivel Esportivo, availability and type of participation, so that my Descoberta is relevant.
12. As an Entusiasta, I want loading skeletons while compatible sessoes load, so that the app does not feel broken during network calls.
13. As an Entusiasta, I want an empty state when there are no nearby sessoes, so that I know whether to broaden filters or create/host something later.
14. As an Entusiasta, I want an offline/error state, so that I understand when discovery cannot refresh.
15. As an Entusiasta, I want to open a detail screen from a card, so that I can inspect the sessao before acting.
16. As an Entusiasta, I want the detail screen to show description, meeting point, rules, equipment and people going, so that I can decide with context.
17. As an Entusiasta, I want a sticky primary action on the detail screen, so that joining or requesting approval is always easy.
18. As an Entusiasta, I want an open sessao to show Vou participar, so that I understand participation is immediate.
19. As an Entusiasta, I want a curated sessao to show Pedir para participar, so that I understand the Anfitriao da Sessao will decide.
20. As an Entusiasta, I want confirmation feedback after joining an open sessao, so that I know my participation was recorded.
21. As an Entusiasta, I want pending feedback after requesting a curated sessao, so that I know I am waiting for approval.
22. As an Entusiasta, I want duplicate interest actions to be prevented, so that I do not create repeated participation requests.
23. As an Entusiasta, I want the app to avoid exposing remaining capacity before a successful match/action, so that the UI respects the global discovery ADR.
24. As an Entusiasta, I want the app to show participant count where allowed, so that I can see social proof without seeing hidden capacity details.
25. As an Entusiasta, I want a Mapa tab with pins for nearby sessoes, so that I can discover opportunities geographically.
26. As an Entusiasta, I want each map pin to include time and Modalidade, so that I can compare opportunities without opening every detail.
27. As an Entusiasta using assistive technology, I want a Lista alternative to the map with the same sessoes, so that map interaction is not required.
28. As an Entusiasta, I want tapping a pin or list item to open a bottom sheet summary, so that I can inspect and act quickly.
29. As an Entusiasta, I want the bottom sheet to show primary action and detail navigation, so that I can either act or inspect more deeply.
30. As an Entusiasta, I want the Partidas tab to list sessoes I acted on, so that I can track my participation.
31. As an Entusiasta, I want Partidas filters for Todas, Confirmado, Aguardando and Recusado, so that I can focus on a specific state.
32. As an Entusiasta, I want each Partidas item to show state with icon, text and color, so that state is understandable without relying on color.
33. As an Entusiasta, I want a confirmed item to open its sessao details, so that I can re-check time, place and rules.
34. As an Entusiasta, I want a pending item to explain the approval process, so that I know what is still waiting.
35. As an Entusiasta, I want a refused item to remain visible, so that the outcome is explicit and does not disappear silently.
36. As an Entusiasta, I want a Perfil tab for my Perfil Esportivo, so that discovery data is separate from authentication data.
37. As an Entusiasta, I want Perfil to show Modalidades, Nivel Esportivo, Objetivos Esportivos and Disponibilidade, so that I understand why sessoes are recommended.
38. As an Entusiasta, I want to edit discovery preferences from Perfil or filters, so that I can improve future matches.
39. As a Perfil Esportivo that can also host, I want a visible future mode switch between Participante and Anfitriao, so that one account can support both roles later.
40. As a Professor, I want curated sessoes to be visually distinct, so that participants understand approval is required.
41. As an Organizador, I want open sessoes to be visually distinct, so that participants understand they can enter directly.
42. As an Anfitriao da Sessao, I want the participant-facing UI to set correct expectations, so that fewer people arrive without approval.
43. As a user on mobile, I want bottom navigation with icon and label, so that primary tabs are reachable and understandable.
44. As a user on desktop, I want the same experience to remain usable in a constrained app layout, so that development and review can happen in the browser.
45. As a user with VoiceOver/TalkBack, I want each card to be announced as one meaningful group, so that card content is not tedious to navigate.
46. As a user with Voice Control, I want visible names on buttons, so that I can activate actions by label.
47. As a user with larger text, I want cards and detail sections to expand without clipping content, so that the app remains readable.
48. As a user with low vision, I want WCAG AA contrast in text and status chips, so that states and actions are readable.
49. As a maintainer, I want shared components for cards, badges, chips, pins, bottom sheets and primary actions, so that visual language stays consistent.
50. As a maintainer, I want discovery behavior behind composables and services, so that screens stay focused on composition and rendering.
51. As a maintainer, I want API payload normalization in services, so that UI components do not depend on backend response quirks.
52. As a maintainer, I want the participation state machine isolated, so that open and curated flows can be tested without rendering every screen.
53. As a maintainer, I want mock data that mirrors API contracts during front-end buildout, so that UI work can proceed before all backend endpoints are final.
54. As a maintainer, I want the app to preserve the distinction between User and Perfil Esportivo, so that discovery does not leak authentication concepts into the domain UI.

## Implementation Decisions

- Build on the existing Vue 3, Pinia and Axios front-end architecture.
- Keep views as composition layers, components as presentation and event emitters, composables as reusable behavior, services as HTTP/payload adapters and stores as cross-screen state.
- Introduce a participant app shell with bottom navigation for Descobrir, Mapa, Partidas and Perfil.
- Use a mobile-first constrained layout for the MVP while keeping desktop review usable.
- Create reusable design tokens for the reference palette: accent blue, open green, curated amber, refused red, neutral text and surface colors.
- Create reusable presentation components for Sessao Esportiva cards, match badges, status chips, people avatars, map/list pins, bottom sheet summaries and sticky primary actions.
- Create a discovery composable that owns current card index, swipe/button actions, undo, filters, loading/error/empty states and reduced-motion behavior.
- Create a participation composable that exposes intent-level actions: join open sessao, request curated sessao, and derive display state.
- Create service methods for compatible sessoes, nearby sessoes, sessao details and participation actions.
- Normalize backend payloads into front-end view models using domain terms: Perfil Esportivo, Modalidade, Sessao Esportiva, Anfitriao da Sessao, Nivel Esportivo and Disponibilidade.
- Treat open and curated participation as two explicit state paths: interest to confirmed for open sessoes, and interest to pending to approved/refused for curated sessoes.
- Do not expose remaining capacity before match/action succeeds; show participant counts only where product rules allow.
- Implement map MVP with a deterministic local map/list presentation first; a real map provider can be added behind the same view model later.
- Include the Lista alternative as a first-class equivalent to Mapa, not a fallback hidden behind accessibility settings.
- Keep mode switching visible in Perfil as an affordance, but defer full Anfitriao creation/approval workflows unless separate PRDs cover them.
- Preserve existing authentication/session behavior and do not model public Descoberta as Workspace-scoped.

## Testing Decisions

- Good tests should verify external behavior: visible states, emitted actions, service contracts, state transitions and accessible alternatives. They should not assert private implementation details or component internals.
- Test the participation state machine for open and curated sessoes: direct confirmation, pending approval, approved, refused, duplicate prevention and error recovery.
- Test discovery behavior through the composable interface: next card, skip, interest, undo, empty state, loading state, error state and reduced-motion mode.
- Test service normalization with representative API payloads so UI receives stable view models.
- Test core components with user-facing assertions: badges render icon/text/color; status chips render labels; cards render modality, host, date, level and action affordances.
- Test the map/list equivalence at the view level: the same nearby sessoes are reachable from Mapa and Lista.
- Add accessibility-oriented checks where the current toolchain supports them: button labels, role/label presence, keyboard focus for actions and no color-only state.
- Prior art in this repo is currently light on front-end automated tests; this PRD expects adding a focused front-end test harness before broad UI coverage if one is not already present.
- Backend behavior should remain covered by existing service/feature tests where participation APIs already exist; this PRD focuses front-end contracts and flows.

## Out of Scope

- Full Anfitriao mode for creating sessoes, managing own sessoes and approving/rejecting participants.
- Chat between Entusiasta and Anfitriao da Sessao.
- Paid sessions, participant fees or checkout.
- Real-time notifications.
- Native iOS/Android implementation.
- Production map provider integration if a deterministic map/list MVP can satisfy the first implementation slice.
- Changes to the global sport discovery ADR.
- Workspace-scoped discovery or participation.
- Backend schema changes unless front-end integration discovers a missing contract.

## Further Notes

- The implementation should use the reference screens as visual direction, not as literal inline HTML.
- The five participant screens from the examples define the first implementation target: Descobrir, Mapa/Lista, Detalhe Aberto, Detalhe Curadoria and Partidas.
- The research reference assumes one account with two modes; this PRD keeps that assumption but only implements the participant flow fully.
- Open question for a later PRD: in curated sessoes, whether final match is only Anfitriao approval or requires double confirmation.
- Open question for a later PRD: whether a real map provider is required in the MVP or whether the accessible list-first map abstraction is enough for initial validation.
- Open question for a later PRD: how host-side approval notifications appear to Entusiastas.
