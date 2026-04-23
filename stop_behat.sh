#!/bin/bash
# =============================================================================
# stop_behat.sh - Para containers usados pelo run_behat.sh
#
# Uso:
#   ./stop_behat.sh         # Para somente containers do Behat (Moodle + Selenium)
#   ./stop_behat.sh --down  # Também executa docker compose down no ambiente
# =============================================================================

set -e

DOCKER_VERSION="php56-nginx"
SISTEM_NAME="local-unasuscp"
CONTAINER_NAME="moodle-$SISTEM_NAME"
SELENIUM_CONTAINER="selenium-chrome-unasuscp"
DOCKER_COMPOSE_DIR="/home/rsc/workspace/docker/$DOCKER_VERSION"

DOWN_FLAG=""
for arg in "$@"; do
    case "$arg" in
        --down) DOWN_FLAG="yes" ;;
        *) ;;
    esac
done

log()  { echo -e "\033[0;32m[INFO]\033[0m  $*"; }
warn() { echo -e "\033[0;33m[WARN]\033[0m  $*"; }

container_exists() {
    sudo docker inspect "$1" >/dev/null 2>&1
}

container_is_running() {
    sudo docker inspect -f '{{.State.Running}}' "$1" 2>/dev/null | grep -q "true"
}

stop_container_if_running() {
    local name="$1"
    if container_exists "$name"; then
        if container_is_running "$name"; then
            log "Parando container '$name'..."
            sudo docker stop "$name" >/dev/null
            log "Container '$name' parado."
        else
            warn "Container '$name' já está parado."
        fi
    else
        warn "Container '$name' não existe."
    fi
}

log "Parando containers do Behat..."
stop_container_if_running "$CONTAINER_NAME"
stop_container_if_running "$SELENIUM_CONTAINER"

if [ -n "$DOWN_FLAG" ]; then
    log "Executando docker compose down em '$DOCKER_COMPOSE_DIR'..."
    (cd "$DOCKER_COMPOSE_DIR" && sudo docker compose down)
    log "docker compose down concluído."
fi

log "Finalizado."

