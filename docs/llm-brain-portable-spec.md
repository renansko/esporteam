# Implantar um Brain de documentação para LLM

## Objetivo

Criar no projeto uma wiki pequena, navegável e mantida por agentes de IA. O Brain deve registrar somente contexto que não é fácil inferir do código — decisões, contratos, invariantes, efeitos colaterais e relações entre módulos — para reduzir leitura repetida de arquivos e consumo de tokens.

O nome e o caminho podem ser adaptados à stack, mas este documento usa `<brain-root>/` como diretório configurável. Exemplos comuns: `app/brain/`, `src/brain/` ou `docs/brain/`.

## Estrutura mínima

```text
<brain-root>/
├── AGENTS.md          # regras de manutenção e navegação
├── index.md           # ponto de entrada e índice completo
├── log.md             # histórico curto das atualizações
├── entities/          # entidades e invariantes
├── services/          # responsabilidades e dependências
├── functions/         # contratos de funções públicas
├── resources/         # formatos de entrada e saída
├── conventions/       # padrões transversais
└── product/           # linguagem e regras do produto, se necessário
```

Use `CLAUDE.md` no lugar de `AGENTS.md` quando essa for a convenção do agente principal do projeto. Se mais de um agente for usado, mantenha uma fonte canônica e faça os outros arquivos apontarem para ela.

## Regras de conteúdo

- Cada página deve ter menos de 100 linhas. Divida assuntos maiores e ligue as partes pelo índice.
- Não copie a implementação. Registre o que ajuda a interpretar ou alterar o código com segurança.
- Use um arquivo por conceito, com nomes estáveis em `PascalCase`.
- Inclua links relativos válidos entre páginas.
- Toda página deve indicar o código-fonte relacionado ou permitir encontrá-lo facilmente.
- Mudanças não triviais devem atualizar as páginas afetadas, o índice quando necessário e o log.
- Remova ou corrija documentação obsoleta no mesmo trabalho que altera o comportamento.

## Conteúdo por tipo de página

### Entidade: `entities/<Entity>.md`

- propósito no domínio;
- campos importantes e tipos;
- relacionamentos;
- invariantes e estados válidos;
- serviços que a alteram;
- interfaces ou rotas que a expõem.

### Serviço: `services/<Service>.md`

- responsabilidade do módulo em 3–5 linhas;
- o que ele deliberadamente não faz;
- dependências;
- entidades tocadas;
- índice de funções públicas, ligado à página de funções.

### Funções: `functions/<Service>.md`

Para cada função pública relevante, registre:

- assinatura;
- comportamento e pré-condições;
- parâmetros e retorno;
- erros esperados;
- efeitos colaterais;
- entidades e integrações tocadas.

### Recurso: `resources/<Resource>.md`

- formato de entrada ou saída;
- campos obrigatórios, opcionais e condicionais;
- regras de compatibilidade ou versionamento;
- exemplo mínimo apenas quando esclarecer o contrato.

### Convenção: `conventions/<Topic>.md`

- decisão transversal;
- quando aplicar;
- exceções;
- exemplo curto;
- referência a ADR ou padrão externo, quando existir.

### Produto: `product/<Concept>.md`

- vocabulário canônico;
- regras que atravessam módulos;
- distinções que o código legado pode esconder;
- decisões de produto necessárias para interpretar o comportamento.

## Backlinks no código

Quando a linguagem permitir, adicione uma anotação pesquisável no comentário da classe ou função. O formato padrão é:

```text
@wiki <brain-root>/entities/<Entity>.md
@wiki <brain-root>/services/<Service>.md
@wiki <brain-root>/functions/<Service>.md#nomeDaFuncao
```

Se comentários estruturados não forem apropriados à stack, mantenha um mapa `símbolo → página` no índice. Backlinks nunca devem alterar comportamento em runtime.

## Fluxo de ingestão

1. Leia as instruções do repositório, o vocabulário do domínio e o código do conceito.
2. Leia testes, interfaces públicas e chamadas do conceito para confirmar contratos reais.
3. Crie ou atualize a página principal e somente as páginas diretamente relacionadas.
4. Adicione backlinks pesquisáveis sem poluir funções privadas ou triviais.
5. Atualize `index.md` com uma descrição de uma linha.
6. Adicione ao `log.md`: data, issue/PR e escopo resumido.
7. Valide links, limite de linhas e divergências óbvias entre wiki, testes e código.

## Ordem de implantação

1. Definir `<brain-root>` e criar diretórios, `AGENTS.md`, `index.md` e `log.md`.
2. Documentar primeiro 1 entidade e 1 serviço de alto valor, com suas funções públicas.
3. Adicionar os backlinks desses conceitos como prova do fluxo completo.
4. Configurar as instruções da raiz do repositório para mandar agentes consultarem o Brain antes de alterar conceitos ingeridos.
5. Expandir sob demanda; não tentar documentar toda a base de uma vez.

## Critérios de aceitação

- [ ] Existe um `<brain-root>/` com instruções, índice, log e categorias adequadas à stack.
- [ ] As instruções da raiz orientam agentes a começar pelo índice e consultar páginas relevantes antes de mudar código.
- [ ] Uma entidade e um serviço reais estão documentados de ponta a ponta, incluindo funções públicas e contratos.
- [ ] O código ingerido possui backlinks pesquisáveis, ou o índice mantém um mapa equivalente.
- [ ] O índice liga todas as páginas e resume cada uma em uma linha.
- [ ] O log usa o formato `data | issue/PR | escopo`.
- [ ] Nenhuma página ultrapassa 100 linhas.
- [ ] Links internos e âncoras foram validados.
- [ ] A wiki registra contexto não óbvio em vez de duplicar a implementação.
- [ ] O processo de manutenção está descrito para que mudanças futuras atualizem código e Brain juntas.

## Fora de escopo

- Gerar documentação exaustiva de toda a base na primeira implantação.
- Armazenar segredos, dados pessoais, dumps ou conteúdo sensível.
- Substituir testes, ADRs, documentação de API ou comentários essenciais do código.
- Criar automação complexa antes de validar manualmente o fluxo com os primeiros conceitos.
