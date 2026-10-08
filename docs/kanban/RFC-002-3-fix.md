---
id: RFC-002-3-fix
status: reviewing
responsible: tech lead
blocked: false
reason: ""
worker: ""
comments: |
    "Zoe: Aberta tarefa de correção devido a falha na suíte de testes (ApiKeyCollectionProviderTest falhou por tentar fazer mock de classe final ScopedProjectLookup)."
    "Hugo: Break-down concluído (subtarefa RFC-002-3-fix-1). Movendo para coding (responsible: coder, worker: '')."
    "Davi: Refatorado ApiKeyCollectionProviderTest para instanciar ScopedProjectLookup real (ProjectRepository stubado + ScopeGuard real com SecurityScopeProvider stubado), seguindo o padrão de ScopedProjectLookupTest."
    "Davi: Suíte verde (168 testes / 369 asserções)."
    "Davi: PR #8 aberto para a main: https://github.com/phprise-foundation/koenma-id/pull/8. Movendo para reviewing (responsible: tech lead, worker: '')."
---

# Correção de testes unitários da RFC-002-3

Corrigir a falha em `ApiKeyCollectionProviderTest` onde `ScopedProjectLookup` é final e não pode ser duplicado pelo PHPUnit MockObject.
