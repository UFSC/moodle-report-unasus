#!/bin/bash
# =============================================================================
# run_tests.sh - Executa testes PHPUnit do plugin report_unasus via Docker
#
# Uso:
#   ./run_tests.sh                  # Roda todos os testes do plugin
#   ./run_tests.sh <arquivo>        # Roda um arquivo de teste específico
#   ./run_tests.sh --reset          # Reinicializa as tabelas PHPUnit
#
# Exemplos:
#   ./run_tests.sh
#   ./run_tests.sh tests/unasus_datastructures_test.php
#   ./run_tests.sh --reset
# =============================================================================

set -e

# ---------------------------------------------------------------------------
# Configurações
# ---------------------------------------------------------------------------
SISTEM_NAME="local-unasuscp"
DOCKER_VERSION="php56-nginx"
CONTAINER_NAME="moodle-$SISTEM_NAME"
DOCKER_COMPOSE_DIR="/home/rsc/workspace/docker/$DOCKER_VERSION"
MOODLE_LOCAL_SITE="www/$SISTEM_NAME"
MOODLE_ROOT_IN_CONTAINER="/home/moodle/$MOODLE_LOCAL_SITE"
PHPUNIT_PREFIX="phpu_"
PHPUNIT_DATAROOT="/home/moodle/moodledata/${PHPUNIT_PREFIX}$SISTEM_NAME"
PLUGIN_COMPONENT="report_unasus"

# Argumentos
RESET_FLAG=""
TEST_FILE=""
for arg in "$@"; do
    case "$arg" in
        --reset) RESET_FLAG="yes" ;;
        *)       TEST_FILE="$arg" ;;
    esac
done

# ---------------------------------------------------------------------------
# Funções auxiliares
# ---------------------------------------------------------------------------
log()  { echo -e "\033[0;32m[INFO]\033[0m  $*"; }
warn() { echo -e "\033[0;33m[WARN]\033[0m  $*"; }
err()  { echo -e "\033[0;31m[ERROR]\033[0m $*" >&2; exit 1; }

container_is_running() {
    sudo docker inspect -f '{{.State.Running}}' "$CONTAINER_NAME" 2>/dev/null | grep -q "true"
}

exec_as_root() {
    sudo docker exec -e XDEBUG_MODE=off "$CONTAINER_NAME" bash -c "$1"
}

exec_as_moodle() {
    sudo docker exec -e XDEBUG_MODE=off -u moodle "$CONTAINER_NAME" bash -c "$1"
}

get_moodle_build() {
    exec_as_moodle "grep -E '^\s*\\\$build\s*=' '$MOODLE_ROOT_IN_CONTAINER/version.php' | tr -d ' ;' " 2>/dev/null || echo "unknown"
}

run_phpunit_init() {
    local output

    if ! output=$(exec_as_root "php '$MOODLE_ROOT_IN_CONTAINER/admin/tool/phpunit/cli/init.php' 2>&1"); then
        echo "$output"
        return 1
    fi

    # Filtra apenas avisos informativos do Composer 1 para reduzir ruído no log.
    echo "$output" | grep -Ev '^A new stable major version of Composer is available|^You are already using composer version 1|^You are using Composer 1 which is deprecated' || true
}

# ---------------------------------------------------------------------------
# 1. Garantir que o container está rodando
# ---------------------------------------------------------------------------
log "Verificando container '$CONTAINER_NAME'..."

if container_is_running; then
    log "Container já está rodando."
else
    warn "Container não está rodando. Iniciando via docker compose..."
    (cd "$DOCKER_COMPOSE_DIR" && sudo docker compose up -d --remove-orphans "$CONTAINER_NAME")

    log "Aguardando container inicializar..."
    for i in $(seq 1 12); do
        sleep 5
        if container_is_running; then
            log "Container pronto após $((i * 5))s."
            break
        fi
        echo -n "."
    done

    if ! container_is_running; then
        err "Falha ao iniciar o container '$CONTAINER_NAME'. Verifique: sudo docker compose logs $CONTAINER_NAME"
    fi
fi

# ---------------------------------------------------------------------------
# 2. Garantir que o Composer está instalado no container
# ---------------------------------------------------------------------------
log "Verificando Composer..."

COMPOSER_OK=$(exec_as_root "composer --version 2>/dev/null | grep -q 'Composer version 1\.' && echo yes || echo no" 2>/dev/null || echo "no")

if [ "$COMPOSER_OK" != "yes" ]; then
    log "Composer 1.x não encontrado. Instalando..."
    exec_as_root "
        curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php &&
        php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer --1 &&
        rm /tmp/composer-setup.php
    "
    log "Composer 1.x instalado."
fi

# ---------------------------------------------------------------------------
# 3. Garantir extensões PHP necessárias para o PHPUnit
# ---------------------------------------------------------------------------
log "Verificando extensões PHP necessárias para o PHPUnit..."

XMLWRITER_OK=$(exec_as_root "php -m 2>/dev/null | grep -q xmlwriter && echo yes || echo no")

if [ "$XMLWRITER_OK" != "yes" ]; then
    log "Instalando extensão php7-xmlwriter (necessária para phpunit/php-code-coverage)..."
    exec_as_root "apk add --no-cache php7-xmlwriter"
    log "php7-xmlwriter instalado."
fi

# ---------------------------------------------------------------------------
# 4. Instalar dependências de desenvolvimento do Moodle (phpunit etc.)
# ---------------------------------------------------------------------------
log "Verificando dependências Composer do Moodle (vendor/)..."

VENDOR_EXISTS=$(exec_as_moodle "test -f '$MOODLE_ROOT_IN_CONTAINER/vendor/bin/phpunit' && echo yes || echo no" 2>/dev/null || echo "no")

if [ "$VENDOR_EXISTS" != "yes" ]; then
    log "Dependências ausentes. Preparando diretório e executando 'composer install'..."

    # Garante que o diretório vendor/ existe com ownership correto
    exec_as_root "
        mkdir -p '$MOODLE_ROOT_IN_CONTAINER/vendor' &&
        chown -R moodle:moodle '$MOODLE_ROOT_IN_CONTAINER/vendor'
    "

    # Configura git safe.directory para suprimir aviso de ownership
    exec_as_moodle "
        git config --global --add safe.directory '$MOODLE_ROOT_IN_CONTAINER' 2>/dev/null || true
    "

    exec_as_moodle "
        cd '$MOODLE_ROOT_IN_CONTAINER' &&
        composer install --no-interaction --prefer-dist
    "
    log "Dependências instaladas."
fi

# ---------------------------------------------------------------------------
# 4. Garantir que o PHPUnit está configurado no config.php
# ---------------------------------------------------------------------------
log "Verificando configuração PHPUnit no config.php..."

PHPUNIT_CONFIGURED=$(exec_as_root "
    grep -q \"phpunit_prefix\" '$MOODLE_ROOT_IN_CONTAINER/config.php' &&
    grep -v '^[[:space:]]*//' '$MOODLE_ROOT_IN_CONTAINER/config.php' | grep -q 'phpunit_prefix' &&
    echo yes || echo no
" 2>/dev/null || echo "no")

if [ "$PHPUNIT_CONFIGURED" != "yes" ]; then
    warn "PHPUnit não configurado no config.php. Adicionando configurações..."

    # Cria o diretório de dados do phpunit dentro do container
    exec_as_root "mkdir -p '$PHPUNIT_DATAROOT' && chown -R moodle:moodle '$PHPUNIT_DATAROOT'"

    # Adiciona as configurações antes do require_once do lib/setup.php
    exec_as_root "
        sed -i \"/require_once.*lib\/setup\.php/i\\
\\\$CFG->phpunit_prefix = '$PHPUNIT_PREFIX';\\
\\\$CFG->phpunit_dataroot = '$PHPUNIT_DATAROOT';\\
\\\$CFG->phpunit_directorypermissions = 02777;
\" '$MOODLE_ROOT_IN_CONTAINER/config.php'
    "
    log "Configurações PHPUnit adicionadas ao config.php."
fi

# ---------------------------------------------------------------------------
# 5. Inicializar (ou reinicializar) as tabelas PHPUnit no banco de dados
# ---------------------------------------------------------------------------
PHPUNIT_XML="$MOODLE_ROOT_IN_CONTAINER/phpunit.xml"
PHPUNIT_VERSION_MARKER="$PHPUNIT_DATAROOT/moodle_build_marker"

# Garante o dataroot do PHPUnit para escrita do marcador de versão.
exec_as_root "mkdir -p '$PHPUNIT_DATAROOT' && chown -R moodle:moodle '$PHPUNIT_DATAROOT'"

if [ -n "$RESET_FLAG" ]; then
    log "Reinicializando tabelas PHPUnit (--reset)..."
    exec_as_root "php '$MOODLE_ROOT_IN_CONTAINER/admin/tool/phpunit/cli/util.php' --drop"
    run_phpunit_init
    CURRENT_BUILD=$(get_moodle_build)
    exec_as_moodle "echo '$CURRENT_BUILD' > '$PHPUNIT_VERSION_MARKER'"
    log "PHPUnit reinicializado."

elif ! exec_as_moodle "test -f '$PHPUNIT_XML'" 2>/dev/null; then
    log "Inicializando PHPUnit pela primeira vez (pode demorar alguns minutos)..."

    # O init.php precisa criar phpunit.xml no dirroot (volume montado do host).
    # Docker com user namespace mapping impede que root do container escreva no host.
    # Solução: garantir permissão de escrita no dirroot a partir do HOST antes de chamar init.php.
    MOODLE_HOST_DIR="$DOCKER_COMPOSE_DIR/$MOODLE_LOCAL_SITE"
    log "Ajustando permissões no host para permitir escrita pelo container..."
    chmod a+w "$MOODLE_HOST_DIR"

    # composer.phar: init.php tenta baixar/criar no dirroot; usamos o composer global instalado.
    COMPOSER_PHAR="$MOODLE_ROOT_IN_CONTAINER/composer.phar"
    COMPOSER_PHAR_HOST="$MOODLE_HOST_DIR/composer.phar"
    if [ ! -f "$COMPOSER_PHAR_HOST" ]; then
        log "Criando composer.phar como wrapper do composer global..."
        exec_as_root "ln -sf /usr/local/bin/composer '$COMPOSER_PHAR'"
    fi

    run_phpunit_init
    CURRENT_BUILD=$(get_moodle_build)
    exec_as_moodle "echo '$CURRENT_BUILD' > '$PHPUNIT_VERSION_MARKER'"
    log "PHPUnit inicializado com sucesso."
else
    CURRENT_BUILD=$(get_moodle_build)
    STORED_BUILD=$(exec_as_moodle "cat '$PHPUNIT_VERSION_MARKER' 2>/dev/null || echo ''" 2>/dev/null || echo "")
    if [ "$CURRENT_BUILD" != "$STORED_BUILD" ] || [ -z "$STORED_BUILD" ]; then
        log "Versão do Moodle alterada. Atualizando ambiente PHPUnit..."
        run_phpunit_init
        exec_as_moodle "echo '$CURRENT_BUILD' > '$PHPUNIT_VERSION_MARKER'"
        log "PHPUnit atualizado com sucesso."
    else
        log "PHPUnit já inicializado e compatível."
    fi
fi

# ---------------------------------------------------------------------------
# 6. Executar os testes
# ---------------------------------------------------------------------------
echo ""
log "============================================================"
log " Executando testes: $PLUGIN_COMPONENT"
log "============================================================"

PLUGIN_TEST_DIR="$MOODLE_ROOT_IN_CONTAINER/report/unasus/tests"

if [ -n "$TEST_FILE" ]; then
    # Resolve o caminho do arquivo de teste dentro do container
    if [[ "$TEST_FILE" == /* ]]; then
        TEST_PATH="$TEST_FILE"
    else
        TEST_PATH="$MOODLE_ROOT_IN_CONTAINER/report/unasus/$TEST_FILE"
    fi
    log "Arquivo: $TEST_PATH"
    echo ""
    exec_as_root "
        cd '$MOODLE_ROOT_IN_CONTAINER' &&
        php vendor/bin/phpunit --colors=always '$TEST_PATH'
    "
else
    log "Diretório: $PLUGIN_TEST_DIR"
    echo ""
    exec_as_root "
        set -e
        cd '$MOODLE_ROOT_IN_CONTAINER'
        TEST_FILES=\$(find '$PLUGIN_TEST_DIR' -type f -name '*_test.php' | sort)

        if [ -z \"\$TEST_FILES\" ]; then
            echo '[ERROR] Nenhum arquivo *_test.php encontrado em $PLUGIN_TEST_DIR' >&2
            exit 1
        fi

        for file in \$TEST_FILES; do
            php vendor/bin/phpunit --colors=always \"\$file\"
        done
    "
fi

echo ""
log "============================================================"
log " Testes concluídos."
log "============================================================"
