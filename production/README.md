# SiteTools
Projeto com varias ferramentas online

## Configuração do banco de dados

Configure as variáveis de ambiente `DB_HOST`, `DB_NAME`, `DB_USER` e
`DB_PASSWORD` antes de iniciar a aplicação. Todas são obrigatórias; a aplicação
interrompe a inicialização se alguma estiver ausente ou vazia e não permite o
uso do usuário MySQL `root`.

Use uma conta exclusiva para a aplicação. Como o SiteTools consulta apenas a
tabela `tools`, conceda somente o privilégio de leitura necessário:

```sql
CREATE USER 'sitetools_app'@'localhost' IDENTIFIED BY 'substitua-por-uma-senha-forte';
GRANT SELECT ON webtools__sitetools.tools TO 'sitetools_app'@'localhost';
```

Defina `DB_USER=sitetools_app` e forneça a senha correspondente em
`DB_PASSWORD`. Armazene essas configurações no ambiente de execução, não no
código-fonte.
