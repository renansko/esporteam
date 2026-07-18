# DiscoveryCache

## `remember`

Recebe a superfície, Perfil Esportivo autenticado, filtros e um carregador para
cachear a resposta normalizada da Descoberta. A chave inclui a versão global de
invalidação, usuário e filtros ordenados; não persiste Models nem Collections.

## `invalidate`

Avança a versão global usada nas chaves de Descoberta após uma publicação. O
argumento identifica o autor do efeito, mas a invalidação alcança todos os
mapas e listas porque uma Sessão Esportiva pública é descoberta globalmente.
