# Arquitetura — SiteTools

## Visão geral

O SiteTools está em migração gradual de endpoints PHP procedurais para uma camada organizada em `app/`. A migração preserva os contratos HTTP existentes e mantém compatibilidade com PHP 8.2+, Composer opcional e WAMP.

O bootstrap da camada é `app/bootstrap.php`; ele registra o autoloader PSR-4 e carrega as configurações compartilhadas necessárias aos serviços.

## Estrutura da camada de aplicação

```
app/
├── Controllers/
│   ├── ImageController.php
│   ├── DocumentController.php
│   └── ToolProcessController.php
├── Services/
│   ├── ImageService.php
│   ├── ImageProcessingService.php
│   ├── PdfService.php
│   ├── DocumentConverterService.php
│   ├── FileStorageService.php
│   └── ...
├── Repositories/
│   ├── ToolRepository.php
│   └── UserRepository.php
├── Middleware/
│   ├── AuthMiddleware.php
│   ├── CsrfMiddleware.php
│   ├── ToolAccessMiddleware.php
│   └── ...
├── Exceptions/
│   ├── AppException.php
│   ├── AuthorizationException.php
│   └── ValidationException.php
├── Helpers/
└── bootstrap.php
```

### Controllers

Controllers coordenam o fluxo de uma operação. Não devem conter algoritmos de conversão, manipulação GD ou SQL diretamente.

- `ImageController`: direciona resize, compress, convert e crop para `ImageService`.
- `DocumentController`: coordena PDF → Word e Word → PDF.
- `ToolProcessController`: infraestrutura compartilhada para endpoints de ferramentas.

Os controllers de imagem e documentos recebem dados já extraídos da requisição. A autenticação, autorização, CSRF, limites e resposta HTTP continuam sendo responsabilidade do endpoint/middleware que integra o fluxo.

### Services

Services concentram regras de negócio e processamento.

- `ImageService`: fachada estável sobre o processador GD existente.
- `ImageProcessingService`: implementação de resize, compress, convert e crop.
- `PdfService`: valida a entrada PDF e delega conversão.
- `DocumentConverterService`: executa conversões com LibreOffice headless.
- `FileStorageService`: fachada sobre as funções de validação e armazenamento privado existentes.

Não duplicar algoritmos ou regras já presentes nos serviços existentes.

### Repositories

Repositories encapsulam consultas PDO:

- `ToolRepository`: consulta ferramentas e regras relacionadas.
- `UserRepository`: consulta e atualiza dados de usuários.

### Middleware

- `AuthMiddleware`: exige uma sessão válida através de `auth_user()`.
- `CsrfMiddleware`: valida requisições mutáveis através de `csrf_verify()`.
- `ToolAccessMiddleware`: verifica autorização e cotas de uso.

O middleware administrativo deve continuar separado da autenticação de usuários comuns, pois o projeto utiliza sessões e tabelas administrativas próprias.

### Exceptions

Use `AppException` e subclasses para erros esperados da aplicação. Mensagens destinadas ao usuário devem ser seguras; detalhes técnicos devem permanecer nos logs.

## Fluxo recomendado

```
Requisição HTTP
  → endpoint / roteador
  → app/bootstrap.php
  → Middleware (CSRF, autenticação, acesso à ferramenta)
  → Controller
  → Service
  → Repository (quando houver acesso a dados)
  → resposta HTTP existente
```

## Compatibilidade e migração

A estrutura nova não exige a migração simultânea de todos os endpoints. Os arquivos em `tools/` podem continuar funcionando como adaptadores legados enquanto são migrados individualmente.

Antes de substituir um endpoint existente:

1. comparar o contrato de entrada e saída usado pelo JavaScript;
2. preservar os nomes dos campos multipart e cabeçalhos de download;
3. manter CSRF, controle de acesso e cotas;
4. validar permissões e propriedade dos arquivos;
5. executar testes locais no WAMP;
6. só então remover lógica duplicada.

## Princípios

- Validação sempre no servidor.
- Consultas SQL preparadas.
- Menor privilégio e isolamento de arquivos por usuário.
- Nenhum detalhe interno de exceção enviado ao cliente.
- Services pequenos e coesos.
- Migração incremental, sem reescrita total do sistema.
