# RegistrationService

Cria uma identidade de autenticação a partir do cadastro público. Define as permissões iniciais e converte a intenção de cadastro em perfil autenticado, sem permitir que o cliente envie um perfil administrativo arbitrário.

## Dependências

- [[entities/User]] — identidade persistida.
- `UserProfile` — valores permitidos para o perfil autenticado.

## Funções

- [[functions/RegistrationService#createUser]]
