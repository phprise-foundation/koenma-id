#!/bin/bash
GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; NC='\033[0m'
AGENT_NAME="Davi"; AGENT_ROLE="coder"; KANBAN_DIR="docs/kanban"; LOCK_FILE="/tmp/davi.lock"

get_val() { grep -m 1 "^$2:" "$1" 2>/dev/null | cut -d' ' -f2- | tr -d "'\""; }
set_val() { sed -i "s|^$2:.*|$2: $3|" "$1"; }

find_and_claim_task() {
    exec 9>>"/tmp/coder-claim.lock"
    flock -x 9
    for f in "$KANBAN_DIR"/*.md; do
        [ -f "$f" ] || continue
        local r=$(get_val "$f" "responsible"); [ "$r" != "$AGENT_ROLE" ] && continue
        local s=$(get_val "$f" "status"); [ "$s" != "coding" ] && continue
        local w=$(get_val "$f" "worker")
        if [ -z "$w" ]; then
            set_val "$f" "worker" "\"$AGENT_NAME\""
            echo "$f"
            flock -u 9
            return 0
        fi
    done
    flock -u 9
    return 1
}

while true; do
    if [ -f "$LOCK_FILE" ]; then
        LOCK_PID=$(cat "$LOCK_FILE" 2>/dev/null | tr -dc '0-9')
        if [ -n "$LOCK_PID" ] && kill -0 "$LOCK_PID" 2>/dev/null; then
            sleep 30; continue
        fi
        rm -f "$LOCK_FILE"
    fi
    TASK=$(find_and_claim_task)
    if [ -z "$TASK" ]; then
        echo -e "${YELLOW}[$(date)] Fila vazia. Davi dormindo...${NC}"; sleep 300; continue
    fi

    echo $$ > "$LOCK_FILE"
    I=$(get_val "$TASK" "id")
    echo -e "${BLUE}[$(date)] Davi processando: $I${NC}"

    opencode run -m deepseek/deepseek-flash --auto "Leia APENAS $TASK e docs/GITFLOW.md. NÃO liste diretórios.
    - Assine TODOS os seus comentários com o seu nome 'Davi' (prefixe cada linha do campo 'comments' com 'Davi: '), nunca com o cargo 'coder'.
    - O campo 'worker' deve conter seu nome 'Davi' enquanto você trabalha; ao finalizar, deixe-o vazio ('').
    - Implemente código e testes unitários; rode bin/phpunit até ficar verde.
    - Se passar, siga docs/GITFLOW.md e abra um PR para a main (branch curta feat|fix, Conventional Commits, git push, gh pr create --base main).
    - Registre o número/URL do PR no campo 'comments' e mova para 'reviewing' (responsible: tech lead, worker: '').
    - Se bloqueado: mova para 'blocked' e explique no reason.
    - Ao finalizar, limpe worker para ''."

    set_val "$TASK" "worker" "\"\""
    rm -f "$LOCK_FILE"
    echo -e "${GREEN}[$(date)] Davi finalizou $I.${NC}"; sleep 10
done
