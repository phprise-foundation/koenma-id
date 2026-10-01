# GITFLOW.md — Fluxo de trabalho Git

> Guia dos fluxos Git do Koenma ID: como criar branches, commitar, abrir PR,
> fazer merge, versionar e publicar. **Siga este documento** ao contribuir.
>
> Em caso de conflito com a [`PHILOSOPHY.md`](PHILOSOPHY.md), a filosofia vence.

---

## 1. Visão geral

Adotamos um fluxo **enxuto**, baseado em branches curtas e PRs para a `main`.
Não usamos o gitflow clássico (`develop` + `release/*` + `hotfix/*`): ele adiciona
cerimônia sem ganho para o nosso tamanho de time.

```
main ──●────────────────●───────────────●──────────►
        \              / \             /
         ●──●──●──────●   ●──●──●─────●
        feat/...          fix/...
        (branch curta)    (branch curta)
```

- **`main`** é a única branch permanente e sempre estável.
- Todo trabalho nasce de uma **branch curta** (`feat/*` ou `fix/*`).
- O trabalho entra na `main` **somente via PR**.
- Cada mudança relevante gera uma **tag semver** e, quando aplicável, uma
  **release**.

---

## 2. Passo a passo

### 2.1 Atualize a `main`

```bash
git checkout main
git pull origin main
```

### 2.2 Crie a branch de trabalho

A partir da `main` **atualizada**:

```bash
# Nova funcionalidade
git checkout -b feat/descricao-curta

# Correção
git checkout -b fix/descricao-curta
```

Regras de nome:

- `feat/<descricao>` — nova funcionalidade
- `fix/<descricao>` — correção de bug
- Use `kebab-case`, descritivo e curto.

### 2.3 Implemente e adicione ao stage

```bash
git add <arquivos>
```

> **Lembre-se:** antes de implementar, leia a filosofia e os documentos de estado.
> Ao terminar, atualize STATE, ROADMAP, ARCHITECTURE, README e CHANGELOG.

### 2.4 Commit (Conventional Commits)

Formato:

```
<tipo>(<ID>): <descrição no imperativo>
```

Exemplos:

```
feat(KOENMA-123): adiciona endpoint de deleção de API Key
fix(KOENMA-124): corrige validação de documento duplicado
docs(KOENMA-125): atualiza README com fluxo de tokens
```

Tipos comuns: `feat`, `fix`, `docs`, `refactor`, `test`, `chore`.

- O `<ID>` é o identificador da tarefa (ex.: `KOENMA-123`).
- A descrição é curta, no imperativo, em minúsculas.

### 2.5 Push e PR

```bash
git push -u origin feat/descricao-curta

gh pr create \
  --base main \
  --title "feat(KOENMA-123): descrição curta" \
  --body "## O que muda
- ...

## Como testar
- ...

## Checklist
- [ ] Testes verdes
- [ ] Documentos atualizados"
```

### 2.6 Verifique conflitos e faça o merge

Verifique se o PR está mergeável:

```bash
gh pr view <numero> --json mergeable,mergeStateStatus
```

Se **não houver conflito**, faça o merge apagando a branch de trabalho:

```bash
gh pr merge <numero> --merge --delete-branch
```

Se **houver conflito**, resolva localmente:

```bash
git checkout feat/descricao-curta
git fetch origin
git rebase origin/main
# resolva os conflitos
git add <arquivos>
git rebase --continue
git push --force-with-lease
```

### 2.7 Atualize a `main` local

```bash
git checkout main
git pull origin main
```

---

## 3. Versionamento (Semantic Versioning)

Após o merge, crie uma **tag** seguindo [SemVer](https://semver.org/lang/pt-BR/):

| Tipo de mudança | Incremento | Exemplo |
|---|---|---|
| `fix` | **patch** | `v1.0.0` → `v1.0.1` |
| `feat` | **minor** | `v1.0.0` → `v1.1.0` |
| *breaking change* | **major** | `v1.0.0` → `v2.0.0` |

> **Política de tags: sempre com prefixo `v`** (ex.: `v1.0.0`). É a convenção
> dominante para tags Git (Linux, Node, Symfony, Laravel, Docker) e o Composer/
> Packagist normaliza o prefixo automaticamente. A versão em si (SemVer) é
> `MAJOR.MINOR.PATCH`; o `v` pertence à tag, não à versão.

```bash
# Descubra a última tag
git describe --tags --abbrev=0

# Crie a nova tag (na main, já mergeada)
git tag v1.1.0
git push origin v1.1.0
```

> **A cada nova major**, publique a tag como **release** no GitHub.

---

## 4. Publicação (GitHub + Packagist)

A publicação é feita pelo script [`bin/publish.sh`](../bin/publish.sh), que:

1. Garante o repositório Git local.
2. Cria o repositório no GitHub (se não existir).
3. Configura o remote `origin`.
4. Faz push da branch atual.
5. Cria a tag e a release no GitHub.
6. Registra o pacote no Packagist.

Uso:

```bash
PACKAGIST_USER=<usuario> PACKAGIST_TOKEN=<token> \
  bin/publish.sh --tag v1.1.0 --repo phprise-foundation/koenma-id
```

Opções:

| Opção | Descrição |
|---|---|
| `--tag <versao>` | Tag a publicar (ex.: `v1.1.0`). **Obrigatório.** |
| `--path <dir>` | Caminho do repositório local. Padrão: diretório atual. |
| `--repo <owner/name>` | Repositório no GitHub. Obrigatório se o remote `origin` não existir. |
| `--public` | Cria o repositório como público (padrão: privado). |
| `--private` | Cria o repositório como privado (padrão). |
| `--dry-run` | Simula tudo sem alterar nada. |
| `-h`, `--help` | Ajuda. |

> **Remote via SSH.** O script configura o `origin` como
> `git@github.com:<owner>/<name>.git` (SSH), consistente com o `gh`. O Packagist,
> porém, é registrado com a URL **HTTPS** do repositório (é o que ele exige).

> **Atenção:** o script faz `push` da **branch atual** e cria a tag nela. Faça o
> merge do PR primeiro e rode o script estando na `main` atualizada.

---

## 5. Resumo do fluxo

```bash
# 1. Atualizar a main
git checkout main && git pull origin main

# 2. Branch de trabalho
git checkout -b feat/minha-feature

# 3. Implementar + stage
git add .

# 4. Commit
git commit -m "feat(KOENMA-123): minha feature"

# 5. Push + PR
git push -u origin feat/minha-feature
gh pr create --base main --title "feat(KOENMA-123): minha feature"

# 6. Merge (sem conflito)
gh pr merge <numero> --merge --delete-branch

# 7. Atualizar a main
git checkout main && git pull origin main

# 8. Tag semver
git tag v1.1.0 && git push origin v1.1.0

# 9. Publicar (GitHub + Packagist)
PACKAGIST_USER=<usuario> PACKAGIST_TOKEN=<token> \
  bin/publish.sh --tag v1.1.0 --repo phprise-foundation/koenma-id
```

---

## 6. Regras de ouro

- **Nunca** commite direto na `main`.
- **Nunca** commite segredos (`.env.local`, chaves, tokens).
- **Sempre** abra PR, mesmo para mudanças pequenas.
- **Sempre** apague a branch após o merge (`--delete-branch`).
- **Sempre** atualize os documentos de estado antes de abrir o PR.
- **Sempre** rode `bin/phpunit` antes do push.
