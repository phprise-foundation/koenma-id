#!/bin/bash
GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; RED='\033[0;31m'; NC='\033[0m'
AGENT_NAME="Hugo"; AGENT_ROLE="tech lead"; KANBAN_DIR="docs/kanban"; LOCK_FILE="/tmp/hugo.lock"

get_val() { grep -m 1 "^$2:" "$1" 2>/dev/null | cut -d' ' -f2- | tr -d "'\""; }

find_task() {
    local best=""; local best_p=999; local best_id=999999
    for f in "$KANBAN_DIR"/*.md; do
        [ -f "$f" ] || continue
        local r=$(get_val "$f" "responsible"); [ "$r" != "$AGENT_ROLE" ] && continue
        local s=$(get_val "$f" "status"); local i=$(get_val "$f" "id")
        local p=999
        case "$s" in "reviewing") p=1;; "blocked") p=2;; "to-do") p=3;; esac
        local n=$(echo "$i" | grep -o '[0-9]\+' | head -1); [ -z "$n" ] && n=999999
        if [ $p -lt $best_p ] || { [ $p -eq $best_p ] && [ $n -lt $best_id ]; }; then
            best="$f"; best_p=$p; best_id=$n
        fi
    done
    echo "$best"
}

while true; do
    if [ -f "$LOCK_FILE" ]; then
        LOCK_PID=$(cat "$LOCK_FILE" 2>/dev/null | tr -dc '0-9')
        if [ -n "$LOCK_PID" ] && kill -0 "$LOCK_PID" 2>/dev/null; then
            sleep 30; continue
        fi
        rm -f "$LOCK_FILE"
    fi
    TASK=$(find_task)
    
    if [ -n "$TASK" ] && [ "$(get_val "$TASK" "status")" == "to-do" ]; then
        BUSY=$(grep -E -l "status: (qa-refinement|coding|reviewing|testing)" "$KANBAN_DIR"/*.md 2>/dev/null)
        if [ -n "$BUSY" ]; then
            echo -e "${YELLOW}[$(date)] Esteira ocupada. Hugo dormindo...${NC}"; sleep 300; continue
        fi
    fi

    if [ -z "$TASK" ]; then
        echo -e "${YELLOW}[$(date)] Fila vazia. Hugo dormindo por 5min...${NC}"; sleep 300; continue
    fi

    echo $$ > "$LOCK_FILE"
    S=$(get_val "$TASK" "status"); I=$(get_val "$TASK" "id")
    echo -e "${BLUE}[$(date)] Hugo processando: $I ($S)${NC}"

    opencode run -m deepseek/deepseek-v4-pro --auto "Leia APENAS $TASK e docs/GITFLOW.md. NÃO liste diretórios.
    - Assine TODOS os seus comentários com o seu nome 'Hugo' (prefixe cada linha do campo 'comments' com 'Hugo: '), nunca com o cargo 'tech lead'.
    - Você é o guardião dos documentos de estado (docs/ROADMAP.md, docs/STATE.md, docs/ARCHITECTURE.md e CHANGELOG.md); mantenha-os sempre atualizados, especialmente ao finalizar uma RFC.
    - Se 'to-do': Quebre em tarefas atômicas (novos .md em $KANBAN_DIR com 'worker: \"\"').
    - Se 'reviewing': revise o PR (número/URL no campo 'comments') com gh pr view e gh pr diff.
      - Aprovado: gh pr merge --merge --delete-branch; atualize a main; crie a tag semver (docs/GITFLOW.md) e a release (gh release create --generate-notes); atualize docs/ROADMAP.md, docs/STATE.md, docs/ARCHITECTURE.md e CHANGELOG.md; mova para 'testing' (responsible: qa, worker: '').
      - Reprovado: solicite alterações no PR e mova para 'coding' (responsible: coder, worker: '').
    - Se 'blocked': Tente desbloquear.
    - Ao mover para 'done', mude responsible para 'none'."

    rm -f "$LOCK_FILE"
    echo -e "${GREEN}[$(date)] Hugo finalizou $I.${NC}"; sleep 10
done
