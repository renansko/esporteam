// Bilingual strings for the Esporteam UI chrome.
// Usage: pickLang(STR.foo, lang)

export const pickLang = (obj, lang) => {
  if (obj == null) return ''
  if (typeof obj === 'string') return obj
  return obj[lang] ?? obj.pt ?? obj.en ?? ''
}

export const STR = {
  // App chrome
  appName:           { pt: 'Esporteam',                       en: 'Esporteam' },
  workspaceTagline:  { pt: 'Gestão de Roadmap com IA',      en: 'AI-powered Roadmap Management' },
  // Sidebar
  nav_inbox:         { pt: 'Ideias',                        en: 'Ideas' },
  nav_ideas:         { pt: 'Roadmap interno',               en: 'Internal Roadmap' },
  nav_competitors:   { pt: 'Concorrentes',                  en: 'Competitors' },
  nav_roadmap:       { pt: 'Roadmap público',               en: 'Public Roadmap' },
  nav_settings:      { pt: 'Ajustes',                       en: 'Settings' },
  // Onboarding / Login
  login_title:       { pt: 'Entrar no Esporteam',             en: 'Sign in to Esporteam' },
  login_subtitle:    { pt: 'Gestão de roadmap orientada por ideias reais.', en: 'Roadmap management driven by real ideas.' },
  login_email:       { pt: 'Email de trabalho',             en: 'Work email' },
  login_password:    { pt: 'Senha',                         en: 'Password' },
  login_submit:      { pt: 'Continuar',                     en: 'Continue' },
  login_demo:        { pt: 'Entrar como demo',              en: 'Sign in as demo' },
  login_demo_note:   { pt: 'Workspace de demonstração com dados seedados.', en: 'Demo workspace with seeded data.' },
  login_no_account:  { pt: 'Não tem conta?',                en: "Don't have an account?" },
  login_to_register: { pt: 'Criar conta',                   en: 'Create account' },
  register_title:    { pt: 'Criar conta',                   en: 'Create account' },
  register_subtitle: { pt: 'Comece um workspace novinho do Esporteam.', en: 'Start a fresh Esporteam workspace.' },
  register_name:     { pt: 'Seu nome',                      en: 'Your name' },
  register_email:    { pt: 'Email',                         en: 'Email' },
  register_password: { pt: 'Senha (mín. 8, com maiúsculas, minúsculas e números)', en: 'Password (min 8, mixed case + numbers)' },
  register_password_confirm: { pt: 'Confirme a senha',     en: 'Confirm password' },
  register_workspace: { pt: 'Nome do workspace',           en: 'Workspace name' },
  register_workspace_hint: { pt: 'Pode ser o nome da sua empresa ou produto.', en: 'Your company or product name works.' },
  register_submit:   { pt: 'Criar conta',                   en: 'Create account' },
  register_have_account: { pt: 'Já tem conta?',             en: 'Already have an account?' },
  register_to_login: { pt: 'Fazer login',                   en: 'Sign in' },
  workspace_setup_title: { pt: 'Escolha um workspace',       en: 'Choose a workspace' },
  workspace_setup_subtitle: { pt: 'Sua conta está ativa. Crie um workspace ou selecione um existente para continuar.', en: 'Your account is active. Create or select a workspace to continue.' },
  workspace_setup_create: { pt: 'Criar workspace',           en: 'Create workspace' },
  workspace_setup_pick: { pt: 'Workspaces disponíveis',      en: 'Available workspaces' },
  workspace_setup_logout: { pt: 'Sair desta conta',          en: 'Sign out of this account' },
  logout_confirm_title: { pt: 'Sair da conta?',             en: 'Sign out?' },
  logout_confirm_body:  { pt: 'Seu token vai ser revogado no servidor. Pra voltar, é só logar de novo.', en: 'Your token will be revoked on the server. Sign in again to come back.' },
  logout_confirm_yes:   { pt: 'Sair',                       en: 'Sign out' },
  // Ideias (entrada bruta — antes "Inbox de feedback")
  inbox_title:       { pt: 'Ideias recebidas',              en: 'Received ideas' },
  inbox_subtitle:    { pt: 'Entrada bruta — manual, CSV, formulário público, redes.', en: 'Raw input — manual, CSV, public form, social.' },
  inbox_filter_all:  { pt: 'Tudo',                          en: 'All' },
  inbox_filter_unclustered: { pt: 'Pendente',               en: 'Pending' },
  inbox_filter_clustered:   { pt: 'No roadmap',             en: 'On roadmap' },
  inbox_search:      { pt: 'Buscar ideia…',                 en: 'Search idea…' },
  inbox_source:      { pt: 'Origem',                        en: 'Source' },
  inbox_analyze:     { pt: 'Analisar com IA',               en: 'Analyze with AI' },
  inbox_analyzing:   { pt: 'Agrupando ideias…',             en: 'Clustering ideas…' },
  inbox_new:         { pt: '+ Nova ideia',                  en: '+ New idea' },
  inbox_count:       { pt: 'ideias',                        en: 'ideas' },
  inbox_clustered_into: { pt: 'agrupadas em',               en: 'clustered into' },
  inbox_ideas_word:  { pt: 'itens do roadmap',              en: 'roadmap items' },
  // Sources
  src_manual:        { pt: 'Manual',                        en: 'Manual' },
  src_support:       { pt: 'Suporte',                       en: 'Support' },
  src_sales:         { pt: 'Vendas',                        en: 'Sales' },
  src_form:          { pt: 'Formulário',                    en: 'Form' },
  src_social:        { pt: 'Redes sociais',                 en: 'Social' },
  src_csv:           { pt: 'CSV',                           en: 'CSV' },
  src_public_form:   { pt: 'Formulário público',            en: 'Public form' },
  // Roadmap interno
  ideas_title:       { pt: 'Roadmap interno',               en: 'Internal roadmap' },
  ideas_subtitle:    { pt: 'Itens curados pela IA, ranqueados por score RICE + votos públicos.', en: 'AI-curated items, ranked by RICE score + public votes.' },
  ideas_col_title:   { pt: 'Item',                          en: 'Item' },
  ideas_col_status:  { pt: 'Status',                        en: 'Status' },
  ideas_col_score:   { pt: 'Score',                         en: 'Score' },
  ideas_col_votes:   { pt: 'Votos',                         en: 'Votes' },
  ideas_col_feedback:{ pt: 'Ideias',                        en: 'Ideas' },
  ideas_col_origin:  { pt: 'Origem',                        en: 'Origin' },
  ideas_filter_all:  { pt: 'Todos',                         en: 'All' },
  // Statuses
  status_analysis:   { pt: 'Em análise',                    en: 'In analysis' },
  status_planned:    { pt: 'Planejado',                     en: 'Planned' },
  status_development:{ pt: 'Em desenvolvimento',            en: 'In development' },
  status_shipped:    { pt: 'Lançado',                       en: 'Shipped' },
  // Origin
  origin_clustered:  { pt: 'IA',                            en: 'AI' },
  origin_manual:     { pt: 'Manual',                        en: 'Manual' },
  origin_competitor: { pt: 'Gap concorrente',               en: 'Competitor gap' },
  // Item detail
  idea_sources:      { pt: 'Ideias originais',              en: 'Source ideas' },
  idea_breakdown:    { pt: 'Composição do score',           en: 'Score breakdown' },
  idea_rationale:    { pt: 'Por que esse score?',           en: 'Why this score?' },
  idea_reach:        { pt: 'Alcance',                       en: 'Reach' },
  idea_impact:       { pt: 'Impacto',                       en: 'Impact' },
  idea_confidence:   { pt: 'Confiança',                     en: 'Confidence' },
  idea_effort:       { pt: 'Esforço',                       en: 'Effort' },
  idea_formula:      { pt: '(Alcance × Impacto × Confiança) / Esforço + boost de votos públicos',
                       en: '(Reach × Impact × Confidence) / Effort + public-vote boost' },
  idea_save:         { pt: 'Salvar',                        en: 'Save' },
  idea_cancel:       { pt: 'Cancelar',                      en: 'Cancel' },
  idea_close:        { pt: 'Fechar',                        en: 'Close' },
  idea_public_votes: { pt: 'Votos públicos',                en: 'Public votes' },
  idea_vote_boost:   { pt: 'Boost de votos',                en: 'Vote boost' },
  // Competitors
  comp_title:        { pt: 'Concorrentes',                  en: 'Competitors' },
  comp_subtitle:     { pt: 'Cole o changelog. A IA marca o que vocês já têm, o que falta e o que é parcial.',
                       en: 'Paste a changelog. AI marks what you have, what\'s missing, what\'s partial.' },
  comp_add:          { pt: '+ Novo concorrente',            en: '+ New competitor' },
  comp_paste_label:  { pt: 'Cole o changelog ou release',   en: 'Paste the changelog or release' },
  comp_analyze:      { pt: 'Analisar changelog',            en: 'Analyze changelog' },
  comp_analyzing:    { pt: 'Extraindo features…',           en: 'Extracting features…' },
  comp_col_feature:  { pt: 'Feature do concorrente',        en: 'Competitor feature' },
  comp_col_status:   { pt: 'Você tem?',                     en: 'Do you have it?' },
  comp_col_idea:     { pt: 'Ideia ligada',                  en: 'Linked idea' },
  comp_match:        { pt: 'Tem',                           en: 'Has' },
  comp_partial:      { pt: 'Parcial',                       en: 'Partial' },
  comp_gap:          { pt: 'Falta',                         en: 'Gap' },
  comp_promote:      { pt: 'Adicionar ao roadmap',          en: 'Add to roadmap' },
  comp_promoted:     { pt: 'Adicionado',                    en: 'Added' },
  // Public roadmap
  roadmap_title:     { pt: 'Roadmap público — Mesa',        en: 'Public roadmap — Mesa' },
  roadmap_subtitle:  { pt: 'Vote no que importa pra você. A gente lê tudo.', en: 'Vote on what matters to you. We read everything.' },
  roadmap_vote:      { pt: 'Votar',                         en: 'Vote' },
  roadmap_voted:     { pt: 'Você votou',                    en: 'You voted' },
  roadmap_vote_modal_title: { pt: 'Votar em uma ideia',     en: 'Vote on an idea' },
  roadmap_vote_email:{ pt: 'Seu email',                     en: 'Your email' },
  roadmap_vote_submit:{ pt: 'Confirmar voto',               en: 'Confirm vote' },
  roadmap_vote_dup:  { pt: 'Esse email já votou nessa ideia.', en: 'This email has already voted on this idea.' },
  roadmap_vote_thanks:{ pt: 'Voto registrado! A prioridade vai se ajustar.', en: 'Vote recorded! Priority will adjust.' },
  roadmap_share:     { pt: 'Compartilhar link',             en: 'Share link' },
  roadmap_pm_view:   { pt: 'Voltar ao painel',              en: 'Back to dashboard' },
  // Closed loop demo
  loop_title:        { pt: 'O loop fechado',                en: 'The closed loop' },
  loop_subtitle:     { pt: 'Veja em 12 segundos como um voto público re-prioriza o roadmap.', en: 'See in 12s how a public vote re-prioritizes the roadmap.' },
  loop_trigger:      { pt: 'Disparar loop',                 en: 'Trigger loop' },
  loop_step_1:       { pt: '1 · Visitante abre o roadmap público', en: '1 · Visitor opens public roadmap' },
  loop_step_2:       { pt: '2 · Vota num item',                    en: '2 · Votes on an item' },
  loop_step_3:       { pt: '3 · Score é recalculado',              en: '3 · Score is recomputed' },
  loop_step_4:       { pt: '4 · Roadmap se reordena',              en: '4 · Roadmap reorders' },
  // Misc
  empty_state:       { pt: 'Nada aqui ainda.',              en: 'Nothing here yet.' },
  saved:             { pt: 'Salvo',                         en: 'Saved' },
}

export const fmtStatus = (status, lang) => pickLang(STR['status_' + status], lang)
export const fmtSource = (source, lang) => pickLang(STR['src_' + source], lang)
export const fmtOrigin = (origin, lang) => {
  if (origin === 'clustered')      return pickLang(STR.origin_clustered, lang)
  if (origin === 'competitor_gap') return pickLang(STR.origin_competitor, lang)
  return pickLang(STR.origin_manual, lang)
}
