const SPORT_ICON_BY_TOKEN = new Map([
  ['beach tennis', 'sportTennis'],
  ['beach-tennis', 'sportTennis'],
  ['tenis', 'sportTennis'],
  ['tennis', 'sportTennis'],
  ['volei', 'sportVolleyball'],
  ['volei de praia', 'sportVolleyball'],
  ['volleyball', 'sportVolleyball'],
  ['futebol', 'sportSoccer'],
  ['soccer', 'sportSoccer'],
  ['corrida', 'sportRunning'],
  ['running', 'sportRunning'],
  ['basquete', 'sportBasketball'],
  ['basketball', 'sportBasketball'],
  ['ciclismo', 'sportBike'],
  ['bike', 'sportBike'],
  ['musculacao', 'sportDumbbell'],
  ['funcional', 'sportDumbbell'],
  ['natacao', 'sportSwim'],
  ['swimming', 'sportSwim'],
  ['yoga', 'sportYoga'],
  ['jiu-jitsu', 'sportFight'],
  ['jiu jitsu', 'sportFight'],
])

function normalizeSportToken(value) {
  return String(value ?? '')
    .normalize('NFD')
    .replace(/\p{Diacritic}/gu, '')
    .trim()
    .toLowerCase()
}

export function resolveSportIcon(modality = {}) {
  const tokens = [
    modality.slug,
    modality.name,
    modality.title,
    modality.category,
    modality.id,
  ].map(normalizeSportToken).filter(Boolean)

  const exact = tokens.find(token => SPORT_ICON_BY_TOKEN.has(token))
  if (exact) return SPORT_ICON_BY_TOKEN.get(exact)

  const partial = tokens.find(token => [...SPORT_ICON_BY_TOKEN.keys()].some(key => token.includes(key)))
  if (partial) {
    const key = [...SPORT_ICON_BY_TOKEN.keys()].find(item => partial.includes(item))
    return SPORT_ICON_BY_TOKEN.get(key)
  }

  return 'sportDefault'
}
