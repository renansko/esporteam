# RegistrationService

## createUser

`createUser(array $data): User`

Cria o usuário de autenticação para uma intenção de cadastro `participant` ou `teacher`. Define `permissions` de acordo com a presença de convite e converte apenas a intenção `teacher` para o perfil autenticado `teacher`; os demais cadastros recebem `user`.

- Parâmetros: dados já validados do registro público.
- Retorno: [[entities/User]] recém-persistida.
- Efeitos: grava `users`; remove campos transitórios de convite e intenção antes da persistência.
