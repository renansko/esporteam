# Design QA — contratos do Participante

Data: 2026-07-22

## Escopo verificado

- Rotas públicas: `/entrar`, `/cadastro`, `/descobrir`, `/mapa`, `/eventos`, `/perfil` e `/sessao/:id`.
- Descobrir: card, pilha, carregamento, vazio, erro, filtros múltiplos, swipe em 50% e ações equivalentes por botão.
- Mapa: alternância semântica Mapa/Lista, pinos por teclado, callout glass com quatro linhas, foco programático, zoom, toque longo de 500 ms e botão “Criar sessão”.
- Eventos: filtros Todas/Confirmado/Aguardando/Recusado, estado por texto e ícone, vazio contextual e anúncio de contagem.
- Perfil: leitura, edição, acessibilidade e mensagem “Participante agora · Anfitrião em breve”.
- Detalhe: distinção Aberta/Curadoria, retorno por rota, deep link, estados de participação e cancelamento desabilitado com explicação.
- Privacidade: a camada pública remove capacidade, vagas restantes e equivalentes recursivamente; a interface do Participante não apresenta esses termos.
- Sistema: IBM Plex Sans/Mono, ícones Tabler, glass regular/thin, fallback opaco, tema escuro, foco visível, alvo mínimo de 44 px e controles principais de 48 px.

## Revisão responsiva estrutural

| Largura | Verificação estrutural |
| --- | --- |
| 390 px | Densidade móvel de referência; navegação inferior e conteúdo ocupam o viewport sem moldura. |
| 768 px | Conteúdo ganha respiro, mapa passa a 520 px e sheets ficam limitados e centralizados. |
| 1024 px | Conteúdo principal limitado a 960 px; listas, mapa e perfil limitados a 820 px. |
| 1440 px | O shell continua fluido em toda a janela, com largura de leitura controlada e navegação centralizada. |

## Validação automatizada

- Contratos comportamentais Node cobrem rotas/guards, decisão de retorno, fallback de deep link, filtros múltiplos, deduplicação por rodada, falha parcial/total, swipe, cancelamento do toque longo por movimento, comandos de zoom e foco sem rolagem. Contratos SSR/fonte confirmam a integração e a ausência de vagas/capacidade na UI; a interação real no navegador permanece no gate manual.
- `npm test` e `npm run build` devem permanecer verdes antes da entrega.

## Gate manual

Não foi usada automação de navegador, conforme a decisão registrada no plano. A equivalência visual pixel a pixel não está declarada como aprovada. O gate final é a inspeção manual das telas e estados acima em 390, 768, 1024 e 1440 px, incluindo tema escuro, teclado, movimento reduzido e transparência reduzida.

Os arquivos `Contrato_8_Paginas.dc.html` e `Contrato_Componentes.dc.html` não estavam presentes neste checkout. A revisão estrutural usou os contratos `.dc.html` disponíveis em `src/examples/Papéis e modos de participante/` e as regras consolidadas no plano.
