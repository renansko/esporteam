# User

Identidade autenticada global da plataforma. A descoberta esportiva usa o Perfil Esportivo de outro serviço; este registro mantém o perfil de autenticação, permissões globais e credenciais.

## Campos relevantes

- `name`, `email`, `password`
- `profile` — perfil autenticado (`user`, `teacher`, `helper` ou `admin`).
- `permissions` — bitmask de permissões globais.

## Serviços

- [[services/RegistrationService]] cria novos registros a partir da intenção de cadastro.
