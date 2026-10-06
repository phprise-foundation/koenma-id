#!/bin/bash
GREEN='\033[0;32m'; YELLOW='\033[1;33m'; BLUE='\033[0;34m'; NC='\033[0m'
AGENT_NAME="Zoe"; AGENT_ROLE="qa"; KANBAN_DIR="docs/kanban"; LOCK_FILE="/tmp/zoe.lock"

get_val() { grep -m 1 "^$2:" "$1" 2>/dev/null | cut -d' ' -f2- | tr -d "'\""; }

find_task() {
    local best=""; local best_p=999; local best_id=999999
    for f in "$KANBAN_DIR"/*.md; do
        [ -f "$f" ] || continue
        local r=$(get_val "$f" "responsible"); [ "$r" != "$AGENT_ROLE" ] && continue
        local s=$(get_val "$f" "status"); local i=$(get_val "$f" "id")
        local p=999
        case "$s" in "testing") p=1;; "qa-refinement") p=2;; esac
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
    if [ -z "$TASK" ]; then
        echo -e "${YELLOW}[$(date)] Fila vazia. Zoe dormindo por 5min...${NC}"; sleep 300; continue
    fi

    echo $$ > "$LOCK_FILE"
    S=$(get_val "$TASK" "status"); I=$(get_val "$TASK" "id")
    echo -e "${BLUE}[$(date)] Zoe processando: $I ($S)${NC}"

    opencode run -m google/gemini-flash-lite-latest --auto "Leia APENAS $TASK. NÃO liste diretórios.
    - Assine TODOS os seus comentários com o seu nome 'Zoe' (prefixe cada linha do campo 'comments' com 'Zoe: '), nunca com o cargo 'qa'.
    - Se 'qa-refinement': Crie BDD e esqueletos de testes de integração. Mova para 'coding' (responsible: coder, worker: '').
    - Se 'testing': Rode a suíte (o código já foi mergeado). Se passar, mova para 'done' (responsible: none). Se falhar, abra uma nova tarefa de correção (novo .md em $KANBAN_DIR com status to-do e responsible: tech lead) e marque a tarefa atual como 'done' (responsible: none) com comentário referenciando a nova tarefa."

    rm -f "$LOCK_FILE"
    echo -e "${GREEN}[$(date)] Zoe finalizou $I.${NC}"; sleep 10
done
