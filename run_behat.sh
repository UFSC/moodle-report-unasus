#!/bin/bash
# =============================================================================
# run_behat.sh - Executa testes Behat do plugin report_unasus via Docker
#
# Uso:
#   ./run_behat.sh                                  # Roda todos os testes @report_unasus
#   ./run_behat.sh tests/behat/unasus.feature       # Roda um feature file específico
#   ./run_behat.sh --tags=@unasus                   # Filtra por tag
#   ./run_behat.sh --init                           # Força reinicialização do ambiente Behat
#
# Pré-requisitos:
#   - Container moodle-local-unasuscp em execução (ou inicia automaticamente)
#   - Imagem selenium/standalone-chrome:3.141.59 disponível (baixada automaticamente)
# =============================================================================

set -e

# ---------------------------------------------------------------------------
# Configurações
# ---------------------------------------------------------------------------
URL_NAME="local-unasus-cp.moodle.ufsc.br"
SISTEM_NAME="local-unasuscp"
DOCKER_VERSION="php56-nginx"
CONTAINER_NAME="moodle-$SISTEM_NAME"
SELENIUM_CONTAINER="selenium-chrome-unasuscp"
SELENIUM_IMAGE="selenium/standalone-chrome:3.141.59"
DOCKER_COMPOSE_DIR="/home/rsc/workspace/docker/$DOCKER_VERSION"
MOODLE_LOCAL_SITE="www/$SISTEM_NAME"
MOODLE_ROOT_IN_CONTAINER="/home/moodle/$MOODLE_LOCAL_SITE"
DOCKER_NETWORK="moodle-network-php56"
BEHAT_PREFIX="bht_"
BEHAT_DATAROOT="/home/moodle/moodledata/behat_$SISTEM_NAME"
BEHAT_TOGGLE_FILE="/home/moodle/moodledata/$SISTEM_NAME/.enable_behat"
BEHAT_WWWROOT="http://$URL_NAME"
PLUGIN_COMPONENT="report_unasus"
PLUGIN_TAG="@report_unasus"

# Argumentos
INIT_FLAG=""
FEATURE_FILE=""
TAGS_ARG=""
for arg in "$@"; do
    case "$arg" in
        --init)       INIT_FLAG="yes" ;;
        --tags=*)     TAGS_ARG="$arg" ;;
        -*)           ;;
        *)            FEATURE_FILE="$arg" ;;
    esac
done

# ---------------------------------------------------------------------------
# Funções auxiliares
# ---------------------------------------------------------------------------
log()  { echo -e "\033[0;32m[INFO]\033[0m  $*"; }
warn() { echo -e "\033[0;33m[WARN]\033[0m  $*"; }
err()  { echo -e "\033[0;31m[ERROR]\033[0m $*" >&2; exit 1; }

container_is_running() {
    sudo docker inspect -f '{{.State.Running}}' "$1" 2>/dev/null | grep -q "true"
}

exec_as_moodle() {
    sudo docker exec -u moodle "$CONTAINER_NAME" bash -c "export MOODLE_ENABLE_BEHAT=1; $1"
}

exec_php_as_moodle_for_init() {
    sudo docker exec -u moodle "$CONTAINER_NAME" bash -c "export MOODLE_ENABLE_BEHAT=1; $1"
}

exec_as_root() {
    sudo docker exec -u 0 "$CONTAINER_NAME" bash -c "export MOODLE_ENABLE_BEHAT=1; $1"
}

enable_behat_mode() {
    log "Ativando modo Behat..."
    sudo docker exec -u 0 "$CONTAINER_NAME" bash -c "set -e
        mkdir -p '$(dirname "$BEHAT_TOGGLE_FILE")'
        touch '$BEHAT_TOGGLE_FILE'
        chown moodle:moodle '$BEHAT_TOGGLE_FILE'
        chmod 664 '$BEHAT_TOGGLE_FILE'
    "
}

disable_behat_mode() {
    if container_is_running "$CONTAINER_NAME"; then
        log "Desativando modo Behat..."
        sudo docker exec -u 0 "$CONTAINER_NAME" bash -c "rm -f '$BEHAT_TOGGLE_FILE'" || true
    fi
}

ensure_legacy_composer_for_behat_init() {
    log "Preparando composer legado para inicialização do Behat..."
    TMP_COMPOSER_WRAPPER=$(mktemp)
    cat > "$TMP_COMPOSER_WRAPPER" <<'PHPWRAPPER'
<?php
if (PHP_SAPI !== 'cli') {
    exit(1);
}

$args = $_SERVER['argv'];
array_shift($args);

if (!empty($args) && $args[0] === 'self-update') {
    fwrite(STDOUT, "Skipping composer self-update for legacy PHP environment\n");
    exit(0);
}

$real = __DIR__ . '/composer-real.phar';
$cmd = 'USE_ZEND_ALLOC=0 php -d opcache.enable_cli=0 ' . escapeshellarg($real);
foreach ($args as $arg) {
    $cmd .= ' ' . escapeshellarg($arg);
}

passthru($cmd, $exitcode);
exit($exitcode);
PHPWRAPPER

    sudo docker exec -u 0 "$CONTAINER_NAME" bash -c "set -e
        curl -sS -L -o /tmp/composer22.phar https://github.com/composer/composer/releases/download/2.2.21/composer.phar
        cp /tmp/composer22.phar '$MOODLE_ROOT_IN_CONTAINER/composer-real.phar'
    "
    sudo docker cp "$TMP_COMPOSER_WRAPPER" "$CONTAINER_NAME:$MOODLE_ROOT_IN_CONTAINER/composer.phar"
    rm -f "$TMP_COMPOSER_WRAPPER"

    sudo docker exec -u 0 "$CONTAINER_NAME" bash -c "set -e
        chown moodle:moodle '$MOODLE_ROOT_IN_CONTAINER/composer-real.phar'
        chmod 555 '$MOODLE_ROOT_IN_CONTAINER/composer-real.phar'
        chown moodle:moodle '$MOODLE_ROOT_IN_CONTAINER/composer.phar'
        chmod 555 '$MOODLE_ROOT_IN_CONTAINER/composer.phar'
    "
}

# ---------------------------------------------------------------------------
# 1. Garantir que o container Moodle está rodando
# ---------------------------------------------------------------------------
log "Verificando container '$CONTAINER_NAME'..."

if container_is_running "$CONTAINER_NAME"; then
    log "Container Moodle já está rodando."
else
    warn "Container não está rodando. Iniciando via docker compose..."
    (cd "$DOCKER_COMPOSE_DIR" && sudo docker compose up -d --remove-orphans "$CONTAINER_NAME")

    for i in $(seq 1 12); do
        sleep 5
        if container_is_running "$CONTAINER_NAME"; then
            log "Container Moodle pronto após $((i * 5))s."
            break
        fi
        echo -n "."
    done

    container_is_running "$CONTAINER_NAME" || \
        err "Falha ao iniciar '$CONTAINER_NAME'. Verifique: sudo docker compose logs $CONTAINER_NAME"
fi

# Mantém o site em modo Behat apenas durante esta execução.
enable_behat_mode
trap disable_behat_mode EXIT

# ---------------------------------------------------------------------------
# 2. Garantir que o container Selenium está rodando
# ---------------------------------------------------------------------------
log "Verificando container Selenium '$SELENIUM_CONTAINER'..."

if container_is_running "$SELENIUM_CONTAINER"; then
    log "Container Selenium já está rodando."
    sudo docker network connect "$DOCKER_NETWORK" "$SELENIUM_CONTAINER" 2>/dev/null || true
else
    if sudo docker inspect "$SELENIUM_CONTAINER" &>/dev/null; then
        log "Reiniciando container Selenium existente..."
        sudo docker start "$SELENIUM_CONTAINER"
    else
        log "Iniciando novo container Selenium (imagem: $SELENIUM_IMAGE)..."
        sudo docker run -d \
            --name "$SELENIUM_CONTAINER" \
            --network "$DOCKER_NETWORK" \
            --shm-size=2g \
            -p 4444:4444 \
            "$SELENIUM_IMAGE"
    fi

    log "Aguardando Selenium inicializar..."
    for i in $(seq 1 12); do
        sleep 5
        if sudo docker exec "$SELENIUM_CONTAINER" curl -sf http://localhost:4444/wd/hub/status &>/dev/null; then
            log "Selenium pronto após $((i * 5))s."
            break
        fi
        echo -n "."
    done
fi

# Garantir que o Selenium consegue resolver $URL_NAME para o container Moodle.
# Docker DNS só resolve nomes de container, não o domínio externo usado em behat_wwwroot.
log "Configurando /etc/hosts do Selenium para resolver '$URL_NAME'..."
MOODLE_IP=$(sudo docker inspect -f "{{(index .NetworkSettings.Networks \"$DOCKER_NETWORK\").IPAddress}}" "$CONTAINER_NAME" 2>/dev/null)
if [ -z "$MOODLE_IP" ] || [ "$MOODLE_IP" = "<no value>" ]; then
    err "Não foi possível obter IP do container '$CONTAINER_NAME' na rede '$DOCKER_NETWORK'."
fi
sudo docker exec -u 0 "$SELENIUM_CONTAINER" bash -c "TMP=/tmp/hosts.\$\$; grep -v '[[:space:]]$URL_NAME$' /etc/hosts > \"\$TMP\" || true; cat \"\$TMP\" > /etc/hosts; rm -f \"\$TMP\"; echo '$MOODLE_IP $URL_NAME' >> /etc/hosts"
log "Selenium resolve '$URL_NAME' -> $MOODLE_IP."

# ---------------------------------------------------------------------------
# 3. Configurar Behat no config.php (se ainda não configurado)
# ---------------------------------------------------------------------------
log "Verificando configuração Behat no config.php..."

BEHAT_CONFIGURED=$(exec_as_moodle "
    CFG_FILE='$MOODLE_ROOT_IN_CONTAINER/config.php'
    HAS_PREFIX=\$(grep -Ev '^[[:space:]]*(//|#)' \"\$CFG_FILE\" | grep -Eq '^[[:space:]]*\\\$CFG->behat_prefix[[:space:]]*=' && echo yes || echo no)
    HAS_DATAROOT=\$(grep -Ev '^[[:space:]]*(//|#)' \"\$CFG_FILE\" | grep -Eq '^[[:space:]]*\\\$CFG->behat_dataroot[[:space:]]*=' && echo yes || echo no)
    HAS_WWWROOT=\$(grep -Ev '^[[:space:]]*(//|#)' \"\$CFG_FILE\" | grep -Eq '^[[:space:]]*\\\$CFG->behat_wwwroot[[:space:]]*=' && echo yes || echo no)
    if [ \"\$HAS_PREFIX\" = \"yes\" ] && [ \"\$HAS_DATAROOT\" = \"yes\" ] && [ \"\$HAS_WWWROOT\" = \"yes\" ]; then
        echo yes
    else
        echo no
    fi
" 2>/dev/null || echo "no")

if [ "$BEHAT_CONFIGURED" != "yes" ]; then
    warn "Behat incompleto no config.php. Adicionando configurações obrigatórias..."

    exec_as_root "mkdir -p '$BEHAT_DATAROOT'
if ! chown -R moodle:moodle '$BEHAT_DATAROOT' 2>/dev/null; then
    echo '[WARN] Sem permissão para chown em $BEHAT_DATAROOT (bind mount). Continuando...'
fi"

    exec_as_moodle "set -e
CFG_FILE='$MOODLE_ROOT_IN_CONTAINER/config.php'
TMP1=\$(mktemp)
TMP2=\$(mktemp)

# 1) Remove definições antigas de behat_* e bloco behat_config para evitar duplicação.
awk '
BEGIN { skip = 0 }
{
    if (skip == 1) {
        if (\$0 ~ /^[[:space:]]*\\);[[:space:]]*$/) {
            skip = 0
        }
        next
    }
    if (\$0 ~ /^[[:space:]]*\\\$CFG->behat_config[[:space:]]*=/) {
        skip = 1
        next
    }
    if (\$0 ~ /^[[:space:]]*\\\$CFG->behat_(wwwroot|prefix|dataroot)[[:space:]]*=/) {
        next
    }
    print
}
' \"\$CFG_FILE\" > \"\$TMP1\"

# 2) Insere bloco novo antes do require lib/setup.php (ou no fim, se não achar).
awk -v block=\"\\\$CFG->behat_wwwroot  = '$BEHAT_WWWROOT';
\\\$CFG->behat_prefix   = '$BEHAT_PREFIX';
\\\$CFG->behat_dataroot = '$BEHAT_DATAROOT';
\\\$CFG->behat_config   = array(
    'default' => array(
        'extensions' => array(
            'Behat\\\\MinkExtension\\\\Extension' => array(
                'selenium2' => array(
                    'browser'      => 'chrome',
                    'capabilities' => array('chrome' => array('switches' => array('--no-sandbox', '--disable-dev-shm-usage'))),
                    'wd_host'      => 'http://$SELENIUM_CONTAINER:4444/wd/hub',
                ),
            ),
        ),
    ),
);
\" '
BEGIN { inserted = 0 }
{
    if (!inserted && \$0 ~ /require_once.*lib\\/setup\\.php/) {
        print block
        inserted = 1
    }
    print
}
END {
    if (!inserted) {
        print \"\"
        print block
    }
}
' \"\$TMP1\" > \"\$TMP2\"

mv \"\$TMP2\" \"\$CFG_FILE\"
rm -f \"\$TMP1\"
"

    log "Configurações Behat adicionadas ao config.php."
fi

# Detectar configuração desatualizada (chromeOptions/extra_capabilities não são válidos nesta versão)
# O formato correto para esta versão do MinkExtension é: capabilities.chrome.switches
BEHAT_CONFIG_STALE=$(exec_as_moodle "
    grep -q 'chromeOptions\|extra_capabilities' '$MOODLE_ROOT_IN_CONTAINER/config.php' && echo yes || echo no
" 2>/dev/null || echo "no")

if [ "$BEHAT_CONFIG_STALE" = "yes" ]; then
    warn "Configuração Behat desatualizada (chromeOptions/extra_capabilities). Corrigindo config.php..."
    exec_as_moodle "php -r \"
        \\\$f = file_get_contents('$MOODLE_ROOT_IN_CONTAINER/config.php');
        \\\$old = array(
            \\\"'capabilities' => array('chromeOptions' => array('args' => array('--headless', '--no-sandbox', '--disable-dev-shm-usage')))\\\",
            \\\"'capabilities' => array('extra_capabilities' => array('chromeOptions' => array('args' => array('--headless', '--no-sandbox', '--disable-dev-shm-usage'))))\\\",
        );
        \\\$new = \\\"'capabilities' => array('chrome' => array('switches' => array('--no-sandbox', '--disable-dev-shm-usage')))\\\";
        \\\$f = str_replace(\\\$old, \\\$new, \\\$f);
        file_put_contents('$MOODLE_ROOT_IN_CONTAINER/config.php', \\\$f);
    \""
    log "config.php corrigido. Forçando reinicialização do Behat..."
    INIT_FLAG="yes"
fi

# Detectar chave quebrada da extensão Mink no behat_config (ex.: BehatMinkExtensionExtension)
BEHAT_MINK_EXT_BROKEN=$(exec_as_moodle "
    grep -q \"BehatMinkExtensionExtension\\|'Behat\\\\MinkExtension\\\\Extension'\" '$MOODLE_ROOT_IN_CONTAINER/config.php' && echo yes || echo no
" 2>/dev/null || echo "no")

if [ "$BEHAT_MINK_EXT_BROKEN" = "yes" ]; then
    warn "Chave da extensão Mink no behat_config está inválida. Corrigindo config.php..."
    exec_as_moodle "php -r \"
        \\\$file = '$MOODLE_ROOT_IN_CONTAINER/config.php';
        \\\$f = file_get_contents(\\\$file);
        \\\$f = preg_replace(
            array(
                '/\\x27BehatMinkExtensionExtension\\x27\\s*=>/',
                '/\\x27Behat\\\\\\\\MinkExtension\\\\\\\\Extension\\x27\\s*=>/',
                '/\\x27Behat\\\\MinkExtension\\\\Extension\\x27\\s*=>/'
            ),
            '\\\\\\\\Behat\\\\\\\\MinkExtension\\\\\\\\Extension::class =>',
            \\\$f
        );
        file_put_contents(\\\$file, \\\$f);
    \""
    log "Chave MinkExtension corrigida. Forçando reinicialização do Behat..."
    INIT_FLAG="yes"
fi

# Detectar behat_wwwroot desatualizado (container name em vez do domínio correto)
BEHAT_WWWROOT_STALE=$(exec_as_moodle "
    grep -q \"behat_wwwroot.*moodle-$SISTEM_NAME\" '$MOODLE_ROOT_IN_CONTAINER/config.php' && echo yes || echo no
" 2>/dev/null || echo "no")

if [ "$BEHAT_WWWROOT_STALE" = "yes" ]; then
    warn "behat_wwwroot aponta para o container ($CONTAINER_NAME) em vez de '$BEHAT_WWWROOT'. Corrigindo config.php..."
    exec_as_moodle "sed -i 's|http://$CONTAINER_NAME|$BEHAT_WWWROOT|g' '$MOODLE_ROOT_IN_CONTAINER/config.php'"
    log "behat_wwwroot corrigido para '$BEHAT_WWWROOT'. Forçando reinicialização do Behat..."
    INIT_FLAG="yes"
fi

# ---------------------------------------------------------------------------
# 4. Inicializar (ou reinicializar) o ambiente Behat
# ---------------------------------------------------------------------------
BEHAT_YML="$BEHAT_DATAROOT/behat/behat.yml"

if [ -n "$INIT_FLAG" ]; then
    log "Reinicializando ambiente Behat (--init)..."
    ensure_legacy_composer_for_behat_init
    exec_php_as_moodle_for_init "MOODLE_SKIP_COMPOSER_SELF_UPDATE=1 USE_ZEND_ALLOC=0 php -d memory_limit=512M '$MOODLE_ROOT_IN_CONTAINER/admin/tool/behat/cli/util_single_run.php' --drop 2>&1 || true"
    exec_php_as_moodle_for_init "MOODLE_SKIP_COMPOSER_SELF_UPDATE=1 USE_ZEND_ALLOC=0 php -d memory_limit=512M '$MOODLE_ROOT_IN_CONTAINER/admin/tool/behat/cli/init.php' 2>&1"
    log "Behat reinicializado."

elif ! exec_as_moodle "test -f '$BEHAT_YML'" 2>/dev/null; then
    log "Inicializando ambiente Behat pela primeira vez (pode demorar alguns minutos)..."

    # Garante permissão de escrita no dirroot para o container criar behat.yml
    MOODLE_HOST_DIR="$DOCKER_COMPOSE_DIR/$MOODLE_LOCAL_SITE"
    chmod a+w "$MOODLE_HOST_DIR"

    ensure_legacy_composer_for_behat_init
    exec_php_as_moodle_for_init "MOODLE_SKIP_COMPOSER_SELF_UPDATE=1 USE_ZEND_ALLOC=0 php -d memory_limit=512M '$MOODLE_ROOT_IN_CONTAINER/admin/tool/behat/cli/init.php' 2>&1"
    log "Behat inicializado com sucesso."
else
    log "Ambiente Behat já inicializado."
fi

# ---------------------------------------------------------------------------
# 5. Executar os testes
# ---------------------------------------------------------------------------
echo ""
log "============================================================"
log " Executando testes Behat: $PLUGIN_COMPONENT"
log "============================================================"

# Diagnóstico: verificar se a front page do site behat está acessível e com o título correto.
log "Diagnóstico: verificando front page behat em http://$URL_NAME/ ..."
DIAG_TITLE=$(sudo docker exec "$SELENIUM_CONTAINER" bash -c "curl -sL --max-time 10 'http://$URL_NAME/' 2>&1 | grep -o '<title>[^<]*</title>'" 2>/dev/null || echo "(curl falhou)")
log "  Título da página: ${DIAG_TITLE:-(sem título / página em branco)}"
DIAG_STATUS=$(sudo docker exec "$SELENIUM_CONTAINER" bash -c "curl -so /dev/null -w '%{http_code}' --max-time 10 'http://$URL_NAME/'" 2>/dev/null || echo "???")
log "  HTTP status: $DIAG_STATUS"

BEHAT_CMD="cd '$MOODLE_ROOT_IN_CONTAINER' && vendor/bin/behat --config='$BEHAT_YML' --ansi"

if [ -n "$FEATURE_FILE" ]; then
    if [[ "$FEATURE_FILE" == /* ]]; then
        FEATURE_PATH="$FEATURE_FILE"
    else
        FEATURE_PATH="$MOODLE_ROOT_IN_CONTAINER/report/unasus/$FEATURE_FILE"
    fi
    log "Feature: $FEATURE_PATH"
    echo ""
    exec_as_moodle "$BEHAT_CMD $FEATURE_PATH"

elif [ -n "$TAGS_ARG" ]; then
    log "Tags: $TAGS_ARG"
    echo ""
    exec_as_moodle "$BEHAT_CMD $TAGS_ARG"

else
    log "Tag padrão: $PLUGIN_TAG"
    echo ""
    exec_as_moodle "$BEHAT_CMD --tags='$PLUGIN_TAG'"
fi

echo ""
log "============================================================"
log " Testes Behat concluídos."
log "============================================================"
