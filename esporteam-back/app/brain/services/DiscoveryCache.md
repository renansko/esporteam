# DiscoveryCache

Cacheia payloads públicos da Descoberta e do mapa sem serializar Models ou Collections do Laravel.

## Dependências

- Cache: Redis em runtime; store array nos testes.
- discovery.cache_ttl_seconds: TTL curto, padrão de 30 segundos.

## Funções

- remember(surface, userId, filters, load) — chaveia por versão, superfície, usuário e filtros normalizados.

## Invalidação

Eventos Eloquent de criação, atualização, remoção e restauração incrementam discovery:version. A versão faz respostas prévias expirarem logicamente sem exigir varredura de chaves.
