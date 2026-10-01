#!/usr/bin/env bash
#
# publish.sh - Publica um projeto PHP no GitHub e no Packagist.
#
# Uso:
#   ./publish.sh --tag v1.0.0 [--path <dir>] [--repo <owner/name>] [--dry-run]
#
# Requisitos: git, gh (autenticado), curl, jq
#
set -Eeuo pipefail

readonly SCRIPT_NAME="${0##*/}"
readonly PACKAGIST_API="https://packagist.org/api/create-package"
readonly GITHUB_HOST="github.com"

readonly COLOR_RESET='\033[0m'
readonly COLOR_INFO='\033[0;34m'
readonly COLOR_OK='\033[0;32m'
readonly COLOR_WARN='\033[0;33m'
readonly COLOR_ERROR='\033[0;31m'

TARGET_PATH="$(pwd)"
REPO_INPUT=""
TAG_INPUT=""
DRY_RUN="false"
VISIBILITY="private"
PACKAGIST_USER="${PACKAGIST_USER:-}"
PACKAGIST_TOKEN="${PACKAGIST_TOKEN:-}"

log_info()  { printf "${COLOR_INFO}[INFO]${COLOR_RESET}  %s\n" "$*"; }
log_ok()    { printf "${COLOR_OK}[ OK ]${COLOR_RESET}  %s\n" "$*"; }
log_warn()  { printf "${COLOR_WARN}[WARN]${COLOR_RESET}  %s\n" "$*"; }
log_error() { printf "${COLOR_ERROR}[FAIL]${COLOR_RESET}  %s\n" "$*" >&2; }

die() {
    log_error "$*"
    exit 1
}

show_help() {
    cat <<HELP
Uso: ${SCRIPT_NAME} [OPÇÕES]

Publica um projeto PHP no GitHub e o registra no Packagist.

Opções:
  --tag <versao>    Tag da versão a publicar (ex: v1.0.0). Obrigatório.
  --path <dir>      Caminho do repositório local. Padrão: diretório atual.
  --repo <owner/name>  Repositório no GitHub. Obrigatório se o remote
                       'origin' ainda não estiver configurado.
  --public          Cria o repositório como público (padrão: privado).
  --private         Cria o repositório como privado (padrão).
  --dry-run         Simula todas as etapas sem alterar nada.
  -h, --help        Mostra esta mensagem.

Credenciais do Packagist (variáveis de ambiente):
  PACKAGIST_USER    Usuário do Packagist.
  PACKAGIST_TOKEN   Token de API do Packagist.

Exemplo:
  PACKAGIST_USER=meu-user PACKAGIST_TOKEN=xxxx \\
    ${SCRIPT_NAME} --tag v1.0.0 --repo minha-org/minha-lib
HELP
}

require_command() {
    command -v "$1" >/dev/null 2>&1 || die "Comando obrigatório não encontrado: $1"
}

run() {
    if [ "$DRY_RUN" = "true" ]; then
        log_warn "[dry-run] $*"
        return 0
    fi
    "$@"
}

is_git_repository() {
    git rev-parse --is-inside-work-tree >/dev/null 2>&1
}

parse_arguments() {
    while [ $# -gt 0 ]; do
        case "$1" in
            --tag)   TAG_INPUT="${2:-}"; shift 2 ;;
            --path)  TARGET_PATH="${2:-}"; shift 2 ;;
            --repo)  REPO_INPUT="${2:-}"; shift 2 ;;
            --public) VISIBILITY="public"; shift ;;
            --private) VISIBILITY="private"; shift ;;
            --dry-run) DRY_RUN="true"; shift ;;
            -h|--help) show_help; exit 0 ;;
            *) die "Parâmetro desconhecido: $1 (use --help)" ;;
        esac
    done
}

validate_environment() {
    require_command git
    require_command gh
    require_command curl
    require_command jq

    [ -n "$TAG_INPUT" ] || die "O parâmetro --tag é obrigatório."
    [ -d "$TARGET_PATH" ] || die "O caminho não existe: $TARGET_PATH"

    gh auth status >/dev/null 2>&1 || die "gh não está autenticado. Execute: gh auth login"

    if [ -z "$PACKAGIST_USER" ] || [ -z "$PACKAGIST_TOKEN" ]; then
        die "Defina PACKAGIST_USER e PACKAGIST_TOKEN no ambiente."
    fi
}

extract_repo_id() {
    local url="$1"
    url="${url%.git}"
    if [[ "$url" =~ ${GITHUB_HOST}[:/]([^/]+/[^/]+)$ ]]; then
        printf '%s' "${BASH_REMATCH[1]}"
        return 0
    fi
    return 1
}

ensure_git_repository() {
    if is_git_repository; then
        log_ok "Repositório Git já inicializado."
        return 0
    fi
    log_info "Inicializando repositório Git local..."
    run git init -b main
    run git add .
    if [ "$DRY_RUN" = "false" ] && git diff --cached --quiet 2>/dev/null; then
        log_warn "Nada para commitar no commit inicial."
        return 0
    fi
    run git commit -m "Initial commit"
}

resolve_repository_id() {
    if is_git_repository && git remote get-url origin >/dev/null 2>&1; then
        local remote_url
        remote_url="$(git remote get-url origin)"
        if REPO_ID="$(extract_repo_id "$remote_url")"; then
            log_ok "Repositório detectado pelo remote: $REPO_ID"
            return 0
        fi
        log_warn "Não foi possível identificar o repositório em: $remote_url"
    fi

    [ -n "$REPO_INPUT" ] || die "Remote 'origin' ausente e --repo não informado."
    REPO_ID="$REPO_INPUT"
    log_info "Usando repositório informado: $REPO_ID"
}

ensure_github_repository() {
    if gh api "repos/$REPO_ID" >/dev/null 2>&1; then
        log_ok "Repositório já existe no GitHub."
        return 0
    fi

    [ -n "$REPO_INPUT" ] || die "Repositório inexistente no GitHub e --repo não informado."
    log_info "Criando repositório $VISIBILITY no GitHub: $REPO_ID"
    run gh repo create "$REPO_ID" "--$VISIBILITY" --source=. --remote=origin
}

configure_origin_remote() {
    local target_url="git@${GITHUB_HOST}:${REPO_ID}.git"
    if is_git_repository && git remote get-url origin >/dev/null 2>&1; then
        run git remote set-url origin "$target_url"
        return 0
    fi
    run git remote add origin "$target_url"
}

push_branch() {
    local branch
    branch="$(git rev-parse --abbrev-ref HEAD 2>/dev/null || printf 'main')"
    log_info "Enviando branch '$branch' para o GitHub..."
    run git push -u origin "$branch"
}

publish_release() {
    if gh release view "$TAG_INPUT" >/dev/null 2>&1; then
        log_warn "A release '$TAG_INPUT' já existe. Pulando criação."
        return 0
    fi

    if ! git rev-parse "$TAG_INPUT" >/dev/null 2>&1; then
        log_info "Criando tag local '$TAG_INPUT'..."
        run git tag "$TAG_INPUT"
    fi

    log_info "Enviando tag '$TAG_INPUT'..."
    run git push origin "$TAG_INPUT"

    log_info "Criando release no GitHub..."
    run gh release create "$TAG_INPUT" --generate-notes
}

register_on_packagist() {
    local repository_url="https://${GITHUB_HOST}/${REPO_ID}"
    local response_file http_code response_body

    if [ "$DRY_RUN" = "true" ]; then
        log_warn "[dry-run] Registraria no Packagist: $repository_url"
        return 0
    fi

    response_file="$(mktemp)"
    trap 'rm -f "$response_file"' RETURN

    log_info "Registrando no Packagist: $repository_url"
    http_code="$(curl -sS -o "$response_file" -w '%{http_code}' \
        -X POST "${PACKAGIST_API}?username=${PACKAGIST_USER}&apiToken=${PACKAGIST_TOKEN}" \
        -H 'Content-Type: application/json' \
        -d "$(jq -n --arg url "$repository_url" '{repository: {url: $url}}')")"

    response_body="$(cat "$response_file")"

    case "$http_code" in
        200|202) log_ok "Packagist aceitou o pacote (HTTP $http_code)." ;;
        409)     log_warn "Pacote já registrado no Packagist (HTTP 409). Sincronizará em breve." ;;
        *)       die "Erro no Packagist (HTTP $http_code): $response_body" ;;
    esac
}

main() {
    parse_arguments "$@"
    validate_environment

    cd "$TARGET_PATH"
    log_info "Diretório de trabalho: $(pwd)"

    ensure_git_repository
    resolve_repository_id
    ensure_github_repository
    configure_origin_remote
    push_branch
    publish_release
    register_on_packagist

    log_ok "Publicação concluída: ${REPO_ID} @ ${TAG_INPUT}"
}

main "$@"
