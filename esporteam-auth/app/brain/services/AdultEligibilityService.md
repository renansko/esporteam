# AdultEligibilityService

Registra a declaraÃ§Ã£o sensÃ­vel de nascimento e maioridade da identidade autenticada. A interface `declare` calcula a idade, rejeita menores de 18 anos, persiste somente no auth, invalida tokens quando a capacidade muda e audita a declaraÃ§Ã£o.

O contrato que sai do auth Ã© exclusivamente o booleano `is_adult` no JWT. Data de nascimento e data do atestado nÃ£o atravessam esse seam.
