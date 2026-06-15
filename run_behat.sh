#!/bin/bash
# =============================================================================
# run_behat.sh - Executa testes Behat do plugin report_unasus via Docker
#
# Uso:
#   ./run_behat.sh                                  # Roda todos os testes @report_unasus
#   ./run_behat.sh tests/behat/unasus.feature       # Roda um feature file específico
#   ./run_behat.sh --tags=@unasus                   # Filtra por tag
#   ./run_behat.sh --name="boletim exporta CSV com dados esperados"  # Filtra por nome do cenário
#   ./run_behat.sh --init                           # Força reinicialização do ambiente Behat
#
# Pré-requisitos:
#   - Container moodle-local-unasuscp em execução (ou inicia automaticamente)
#   - Imagem selenium/standalone-chrome:3.141.59 disponível (baixada automaticamente)
# =============================================================================

set -e

# ---------------------------------------------------------------------------
# Funções auxiliares
# ---------------------------------------------------------------------------
log()  { echo -e "\033[0;32m[INFO]\033[0m  $*"; }
warn() { echo -e "\033[0;33m[WARN]\033[0m  $*"; }
err()  { echo -e "\033[0;31m[ERROR]\033[0m $*" >&2; exit 1; }

# ---------------------------------------------------------------------------
# Leitura do arquivo .env para memória
# ---------------------------------------------------------------------------
if [ -f "../../../../.env" ]; then
  set -a
  source ../../../../.env
  set +a
else
  err "Arquivo ../../../../.env não encontrado."
fi

if [ -f ".env" ]; then
  set -a
  source .env
  set +a
else
  err "Arquivo .env não encontrado."
fi

# ---------------------------------------------------------------------------
# Configurações
# ---------------------------------------------------------------------------
SISTEM_NAME="local-$CORE_NAME"
CONTAINER_NAME="moodle-$SISTEM_NAME"
SELENIUM_CONTAINER="selenium-chrome-$CORE_NAME"
SELENIUM_IMAGE="selenium/standalone-chrome:3.141.59-selenium"
# Porta do HOST publicada para o Selenium (lado esquerdo do -p). A porta interna do
# container é sempre 4444. Parametrizável via SELENIUM_PORT no .env para permitir rodar
# vários ambientes em paralelo sem conflito na 4444. Default: 4444.
SELENIUM_HOST_PORT="${SELENIUM_PORT:-4444}"
DOCKER_COMPOSE_DIR="/home/$USER/workspace/docker/$DOCKER_VERSION"
MOODLE_LOCAL_SITE="www/$SISTEM_NAME"
MOODLE_ROOT_IN_CONTAINER="/home/moodle/$MOODLE_LOCAL_SITE"
DOCKER_NETWORK="moodle-network-$DOCKER_VERSION"
#BEHAT_PREFIX="bht_"
BEHAT_DATAROOT="/home/moodle/moodledata/${BEHAT_PREFIX}$SISTEM_NAME"
BEHAT_WWWROOT="http://$URL_NAME"
BEHAT_ENABLE_FILE="/tmp/.${BEHAT_PREFIX}${SISTEM_NAME}_enabled"
PLUGIN_COMPONENT="report_unasus"
PLUGIN_TAG="@report_unasus"
MOODLE_ENABLE_BEHAT=1

# Argumentos
INIT_FLAG=""
FEATURE_FILE=""
TAGS_ARG=""
BEHAT_EXTRA_ARGS=()
for arg in "$@"; do
    case "$arg" in
        --init)       INIT_FLAG="yes" ;;
        --tags=*)     TAGS_ARG="$arg" ;;
        -*)           BEHAT_EXTRA_ARGS+=("$arg") ;;
        *)            FEATURE_FILE="$arg" ;;
    esac
done

build_escaped_args() {
    local out=""
    local arg
    for arg in "$@"; do
        out="$out $(printf "%q" "$arg")"
    done
    echo "$out"
}

container_is_running() {
    docker inspect -f '{{.State.Running}}' "$1" 2>/dev/null | grep -q "true"
}

exec_as_moodle() {
    docker exec -u moodle "$CONTAINER_NAME" bash -c "$1"
}

resolve_behat_yml() {
    # Localiza o behat.yml real gerado pelo init.php do Moodle. O caminho NÃO é
    # reconstruível a partir do prefixo: o behat_dataroot vem do config.php (no padrão
    # UFSC é computado em runtime a partir de getenv('MOODLEUFSC_BEHAT_PREFIX'), logo não
    # é um literal extraível por grep) e o arquivo pode ficar em behat/ OU em
    # behatrun/behat/ conforme a versão do Moodle / parallel-run. Por isso procuramos o
    # arquivo de fato, escopado a este site. Se houver mais de um (layout antigo
    # remanescente, parallel-run), o mais recente (ls -t) vence. Vazio se não existe.
    exec_as_moodle "find /home/moodle/moodledata -path '*${SISTEM_NAME}*/behat/behat.yml' -exec ls -t {} + 2>/dev/null | head -1" 2>/dev/null || true
}

exec_php_as_moodle_for_init() {
    docker exec -u moodle "$CONTAINER_NAME" bash -c "$1"
}

enable_behat_environment() {
    log "Ativando configuração Behat para esta execução..."
    # Ensure parent directory exists and is owned by moodle user
    docker exec -u 0 "$CONTAINER_NAME" bash -c "mkdir -p /home/moodle/moodledata && chown moodle:moodle /home/moodle/moodledata && chmod 755 /home/moodle/moodledata"
    exec_as_moodle "mkdir -p '$BEHAT_DATAROOT' && touch '$BEHAT_ENABLE_FILE' && rm -f '$BEHAT_DATAROOT/.behat_enabled'"

    # Garantir que o diretório de faildump exista e seja gravável. O hook
    # behat_hooks::before_suite() aborta a suíte INTEIRA com "non-writable
    # directory" se $CFG->behat_faildump_path apontar para um dir inexistente.
    # Extração espelha a do behat_dataroot (sem backreferences, à prova de aspas).
    local faildump
    faildump=$(exec_as_moodle "grep -v '^[[:space:]]*//' '$MOODLE_ROOT_IN_CONTAINER/config.php' | grep 'behat_faildump_path' | grep -o \"'[^']*'\" | tr -d \"'\" | head -1" 2>/dev/null || true)
    if [ -n "$faildump" ]; then
        log "Garantindo diretório de faildump: $faildump"
        exec_as_moodle "mkdir -p '$faildump'"
    else
        warn "Não foi possível extrair behat_faildump_path do config.php; pulando criação do diretório de faildump."
    fi
}

disable_behat_environment() {
    if ! container_is_running "$CONTAINER_NAME"; then
        return
    fi

    log "Desabilitando modo Behat para restaurar o ambiente local..."
    exec_php_as_moodle_for_init "MOODLE_SKIP_COMPOSER_SELF_UPDATE=1 USE_ZEND_ALLOC=0 php -d memory_limit=512M '$MOODLE_ROOT_IN_CONTAINER/admin/tool/behat/cli/util.php' --disable 2>&1 || true"
    exec_as_moodle "rm -f '$BEHAT_ENABLE_FILE' '$BEHAT_DATAROOT/.behat_enabled'"
}

ensure_behat_test_mode_enabled() {
    log "Garantindo que o modo de testes do Behat esteja habilitado..."
    exec_php_as_moodle_for_init "MOODLE_SKIP_COMPOSER_SELF_UPDATE=1 USE_ZEND_ALLOC=0 php -d memory_limit=512M '$MOODLE_ROOT_IN_CONTAINER/admin/tool/behat/cli/util.php' --enable 2>&1"
}

cleanup() {
    # Captura o código de saída em vigor ANTES de qualquer comando de limpeza,
    # senão o status do último comando do trap mascararia uma falha do behat
    # (ex.: o rm -f de disable_behat_environment retorna 0).
    local rc=$?
    disable_behat_environment
    exit "$rc"
}

trap cleanup EXIT

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

    docker exec -u 0 "$CONTAINER_NAME" bash -c "set -e
        curl -sS -L -o /tmp/composer22.phar https://github.com/composer/composer/releases/download/2.2.21/composer.phar
        cp /tmp/composer22.phar '$MOODLE_ROOT_IN_CONTAINER/composer-real.phar'
    "
    docker cp "$TMP_COMPOSER_WRAPPER" "$CONTAINER_NAME:$MOODLE_ROOT_IN_CONTAINER/composer.phar"
    rm -f "$TMP_COMPOSER_WRAPPER"

    docker exec -u 0 "$CONTAINER_NAME" bash -c "set -e
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
    (cd "$DOCKER_COMPOSE_DIR" && docker compose up -d --remove-orphans "$CONTAINER_NAME")

    for i in $(seq 1 12); do
        sleep 5
        if container_is_running "$CONTAINER_NAME"; then
            log "Container Moodle pronto após $((i * 5))s."
            break
        fi
        echo -n "."
    done

    container_is_running "$CONTAINER_NAME" || \
        err "Falha ao iniciar '$CONTAINER_NAME'. Verifique: docker compose logs $CONTAINER_NAME"
fi

# ---------------------------------------------------------------------------
# 2. Garantir que o container Selenium está rodando
# ---------------------------------------------------------------------------
log "Verificando container Selenium '$SELENIUM_CONTAINER'..."

if ! docker network inspect "$DOCKER_NETWORK" >/dev/null 2>&1; then
    warn "Rede Docker '$DOCKER_NETWORK' não encontrada. Criando..."
    docker network create "$DOCKER_NETWORK" >/dev/null
fi

# Recria o container Selenium se a imagem configurada não bate com a do existente.
# Necessário porque Chrome ≥ 76 quebra a compatibilidade com Behat 2.x/Mink
# (chromedriver responde só em W3C; Mink antigo só lê OSS WebDriver).
if docker inspect "$SELENIUM_CONTAINER" &>/dev/null; then
    EXISTING_SELENIUM_IMAGE=$(docker inspect -f '{{.Config.Image}}' "$SELENIUM_CONTAINER" 2>/dev/null || true)
    if [ -n "$EXISTING_SELENIUM_IMAGE" ] && [ "$EXISTING_SELENIUM_IMAGE" != "$SELENIUM_IMAGE" ]; then
        warn "Container Selenium usa imagem '$EXISTING_SELENIUM_IMAGE' (esperado '$SELENIUM_IMAGE'). Recriando..."
        docker rm -f "$SELENIUM_CONTAINER" >/dev/null 2>&1 || true
    fi
fi

if container_is_running "$SELENIUM_CONTAINER"; then
    log "Container Selenium já está rodando."
    docker network connect "$DOCKER_NETWORK" "$SELENIUM_CONTAINER" 2>/dev/null || true
else
    if docker inspect "$SELENIUM_CONTAINER" &>/dev/null; then
        log "Reiniciando container Selenium existente..."
        START_OUTPUT=""
        if ! START_OUTPUT=$(docker start "$SELENIUM_CONTAINER" 2>&1); then
            if echo "$START_OUTPUT" | grep -qi "network .* not found"; then
                warn "Container Selenium preso a rede removida. Recriando container..."
                docker rm -f "$SELENIUM_CONTAINER" >/dev/null 2>&1 || true
                docker run -d \
                    --name "$SELENIUM_CONTAINER" \
                    --network "$DOCKER_NETWORK" \
                    --shm-size=2g \
                    -p ${SELENIUM_HOST_PORT}:4444 \
                    "$SELENIUM_IMAGE"
            else
                err "Falha ao iniciar '$SELENIUM_CONTAINER': $START_OUTPUT"
            fi
        fi
    else
        log "Iniciando novo container Selenium (imagem: $SELENIUM_IMAGE)..."
        docker run -d \
            --name "$SELENIUM_CONTAINER" \
            --network "$DOCKER_NETWORK" \
            --shm-size=2g \
            -p ${SELENIUM_HOST_PORT}:4444 \
            "$SELENIUM_IMAGE"
    fi

    log "Aguardando Selenium inicializar..."
    for i in $(seq 1 12); do
        sleep 5
        if docker exec "$SELENIUM_CONTAINER" curl -sf http://localhost:4444/wd/hub/status &>/dev/null; then
            log "Selenium pronto após $((i * 5))s."
            break
        fi
        echo -n "."
    done
fi

# Garantir que o Selenium consegue resolver $URL_NAME para o container Moodle.
# Docker DNS só resolve nomes de container, não o domínio externo usado em behat_wwwroot.
log "Configurando /etc/hosts do Selenium para resolver '$URL_NAME'..."
MOODLE_IP=$(docker inspect -f "{{(index .NetworkSettings.Networks \"$DOCKER_NETWORK\").IPAddress}}" "$CONTAINER_NAME" 2>/dev/null)
if [ -z "$MOODLE_IP" ] || [ "$MOODLE_IP" = "<no value>" ]; then
    err "Não foi possível obter IP do container '$CONTAINER_NAME' na rede '$DOCKER_NETWORK'."
fi
docker exec -u 0 "$SELENIUM_CONTAINER" bash -c "TMP=/tmp/hosts.\$\$; grep -v '[[:space:]]$URL_NAME$' /etc/hosts > \"\$TMP\" || true; cat \"\$TMP\" > /etc/hosts; rm -f \"\$TMP\"; echo '$MOODLE_IP $URL_NAME' >> /etc/hosts"
log "Selenium resolve '$URL_NAME' -> $MOODLE_IP."

# Garantir que o próprio container Moodle resolva $URL_NAME para si mesmo (127.0.0.1).
# Sem isto, o behat init e o behat_wwwroot check saem pelo DNS externo e podem
# bater num servidor remoto que responda pelo mesmo domínio (301 -> https, etc.).
log "Configurando /etc/hosts do Moodle para resolver '$URL_NAME' -> 127.0.0.1..."
docker exec -u 0 "$CONTAINER_NAME" bash -c "TMP=/tmp/hosts.\$\$; grep -v '[[:space:]]$URL_NAME$' /etc/hosts > \"\$TMP\" || true; cat \"\$TMP\" > /etc/hosts; rm -f \"\$TMP\"; echo '127.0.0.1 $URL_NAME' >> /etc/hosts"
log "Moodle resolve '$URL_NAME' -> 127.0.0.1."

# ---------------------------------------------------------------------------
# 3. Ativar configuração Behat no config.php para esta execução
# ---------------------------------------------------------------------------
enable_behat_environment

# ---------------------------------------------------------------------------
# 3. Configurar Behat no config.php (se ainda não configurado)
# ---------------------------------------------------------------------------
log "Verificando configuração Behat no config.php..."

BEHAT_CONFIGURED=$(exec_as_moodle "
    grep -v '^[[:space:]]*//' '$MOODLE_ROOT_IN_CONTAINER/config.php' | \
    grep -q 'behat_prefix' && echo yes || echo no
" 2>/dev/null || echo "no")

if [ "$BEHAT_CONFIGURED" != "yes" ]; then
    warn "Behat não configurado no config.php. Adicionando configurações..."

    # Ensure parent directory exists with correct permissions
    docker exec -u 0 "$CONTAINER_NAME" bash -c "mkdir -p /home/moodle/moodledata && chown moodle:moodle /home/moodle/moodledata && chmod 755 /home/moodle/moodledata"
    exec_as_moodle "mkdir -p '$BEHAT_DATAROOT' && chown -R moodle:moodle '$BEHAT_DATAROOT'"

    exec_as_moodle "sed -i \"/require_once.*lib\/setup\.php/i\\
\\\$CFG->behat_wwwroot  = '$BEHAT_WWWROOT';\\
\\\$CFG->behat_prefix   = '$BEHAT_PREFIX';\\
\\\$CFG->behat_dataroot = '$BEHAT_DATAROOT';\\
\\\$CFG->behat_config   = array(\\
    'default' => array(\\
        'extensions' => array(\\
            'Behat\\\\\\\\MinkExtension\\\\\\\\Extension' => array(\\
                'selenium2' => array(\\
                    'browser'      => 'chrome',\\
                    'capabilities' => array('chrome' => array('switches' => array('--no-sandbox', '--disable-dev-shm-usage'))),\\
                    'wd_host'      => 'http://$SELENIUM_CONTAINER:4444/wd/hub',\\
                ),\\
            ),\\
        ),\\
    ),\\
);
\" '$MOODLE_ROOT_IN_CONTAINER/config.php'"

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

# Detectar behat_dataroot desatualizado (prefixo errado, ex: behat_ em vez de bht_)
BEHAT_DATAROOT_ACTUAL=$(exec_as_moodle "
    grep -v '^[[:space:]]*//' '$MOODLE_ROOT_IN_CONTAINER/config.php' | \
    grep 'behat_dataroot' | grep -o \"'[^']*'\" | tr -d \"'\"
" 2>/dev/null || true)

if [ -n "$BEHAT_DATAROOT_ACTUAL" ] && [ "$BEHAT_DATAROOT_ACTUAL" != "$BEHAT_DATAROOT" ]; then
    warn "behat_dataroot no config.php ('$BEHAT_DATAROOT_ACTUAL') não corresponde ao esperado ('$BEHAT_DATAROOT'). Corrigindo..."
    exec_as_moodle "sed -i \"s|behat_dataroot = '$BEHAT_DATAROOT_ACTUAL'|behat_dataroot = '$BEHAT_DATAROOT'|g\" '$MOODLE_ROOT_IN_CONTAINER/config.php'"
    log "behat_dataroot corrigido para '$BEHAT_DATAROOT'. Forçando reinicialização do Behat..."
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

# Detectar ambiente Behat inicializado para outra versão/build do Moodle.
# util.php --enable emite "initialised for a different version" / "Reinstall Behat"
# quando o build mudou (ex.: upgrade do Moodle ou outro plugin rodou o init antes).
# Sem isto, a execução aborta pedindo `php init.php`; aqui forçamos o init sozinhos.
# (Só faz sentido quando o ambiente já existe; a primeira inicialização é tratada
# pelo branch "[ -z BEHAT_YML ]" logo abaixo.)
if [ -z "$INIT_FLAG" ] && [ -n "$(resolve_behat_yml)" ]; then
    BEHAT_VERSION_PROBE=$(exec_php_as_moodle_for_init "MOODLE_SKIP_COMPOSER_SELF_UPDATE=1 USE_ZEND_ALLOC=0 php -d memory_limit=512M '$MOODLE_ROOT_IN_CONTAINER/admin/tool/behat/cli/util.php' --enable 2>&1 || true")
    if echo "$BEHAT_VERSION_PROBE" | grep -qiE 'different version|Reinstall Behat'; then
        warn "Ambiente Behat inicializado para outra versão do Moodle. Forçando reinicialização..."
        INIT_FLAG="yes"
    fi
fi

# ---------------------------------------------------------------------------
# 4. Inicializar (ou reinicializar) o ambiente Behat
# ---------------------------------------------------------------------------
BEHAT_YML=$(resolve_behat_yml)

if [ -n "$INIT_FLAG" ]; then
    log "Reinicializando ambiente Behat (--init)..."
    ensure_legacy_composer_for_behat_init

    # Tenta preparar o site para modo behat (alguns forks exigem isso explicitamente).
    exec_php_as_moodle_for_init "MOODLE_SKIP_COMPOSER_SELF_UPDATE=1 USE_ZEND_ALLOC=0 php -d memory_limit=512M '$MOODLE_ROOT_IN_CONTAINER/admin/tool/behat/cli/util.php' --enable 2>&1 || true"

    # --drop pode falhar se ainda não for site behat; é esperado na primeira execução.
    exec_php_as_moodle_for_init "MOODLE_SKIP_COMPOSER_SELF_UPDATE=1 USE_ZEND_ALLOC=0 php -d memory_limit=512M '$MOODLE_ROOT_IN_CONTAINER/admin/tool/behat/cli/util_single_run.php' --drop 2>&1 || true"

    INIT_OUTPUT=""
    if ! INIT_OUTPUT=$(exec_php_as_moodle_for_init "MOODLE_SKIP_COMPOSER_SELF_UPDATE=1 USE_ZEND_ALLOC=0 php -d memory_limit=512M '$MOODLE_ROOT_IN_CONTAINER/admin/tool/behat/cli/init.php' 2>&1"); then
        if echo "$INIT_OUTPUT" | grep -qi "upgraderunning"; then
            warn "Lock de upgrade detectado durante init do Behat. Limpando lock órfão e tentando novamente..."
            exec_php_as_moodle_for_init "MOODLE_SKIP_COMPOSER_SELF_UPDATE=1 USE_ZEND_ALLOC=0 php -r \"
                require('$MOODLE_ROOT_IN_CONTAINER/config.php');
                global \\$DB;
                \\$DB->delete_records('config', array('name' => 'upgraderunning'));
                echo 'upgraderunning lock removido' . PHP_EOL;
            \" 2>&1"
            INIT_OUTPUT=$(exec_php_as_moodle_for_init "MOODLE_SKIP_COMPOSER_SELF_UPDATE=1 USE_ZEND_ALLOC=0 php -d memory_limit=512M '$MOODLE_ROOT_IN_CONTAINER/admin/tool/behat/cli/init.php' 2>&1") || {
                err "Falha ao inicializar Behat após remover lock de upgrade: $INIT_OUTPUT"
            }
        elif echo "$INIT_OUTPUT" | grep -qi "This is not a behat test site"; then
            warn "Site ainda não está em modo Behat. Forçando habilitação e tentando novamente..."
            exec_php_as_moodle_for_init "MOODLE_SKIP_COMPOSER_SELF_UPDATE=1 USE_ZEND_ALLOC=0 php -d memory_limit=512M '$MOODLE_ROOT_IN_CONTAINER/admin/tool/behat/cli/util.php' --enable 2>&1 || true"
            INIT_OUTPUT=$(exec_php_as_moodle_for_init "MOODLE_SKIP_COMPOSER_SELF_UPDATE=1 USE_ZEND_ALLOC=0 php -d memory_limit=512M '$MOODLE_ROOT_IN_CONTAINER/admin/tool/behat/cli/init.php' 2>&1") || {
                err "Falha ao inicializar Behat após forçar modo behat: $INIT_OUTPUT"
            }
        else
            err "Falha ao inicializar Behat: $INIT_OUTPUT"
        fi
    fi

    log "Behat reinicializado."

elif [ -z "$BEHAT_YML" ]; then
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

# O caminho real do behat.yml só é conhecido com certeza após o init/reinit, que pode
# tê-lo criado ou movido para outro dataroot/layout. Re-resolve incondicionalmente.
BEHAT_YML=$(resolve_behat_yml)
[ -n "$BEHAT_YML" ] || err "Não foi possível localizar o behat.yml após a inicialização do ambiente Behat."
log "Usando config Behat: $BEHAT_YML"

# ---------------------------------------------------------------------------
# 5. Habilitar explicitamente o modo de testes antes da execução
# ---------------------------------------------------------------------------
ensure_behat_test_mode_enabled

# ---------------------------------------------------------------------------
# 5. Executar os testes
# ---------------------------------------------------------------------------
echo ""
log "============================================================"
log " Executando testes Behat: $PLUGIN_COMPONENT"
log "============================================================"

# Diagnóstico: verificar se a front page do site behat está acessível e com o título correto.
log "Diagnóstico: verificando front page behat em http://$URL_NAME/ ..."
DIAG_TITLE=$(docker exec "$SELENIUM_CONTAINER" bash -c "curl -sL --max-time 10 'http://$URL_NAME/' 2>&1 | grep -o '<title>[^<]*</title>'" 2>/dev/null || echo "(curl falhou)")
log "  Título da página: ${DIAG_TITLE:-(sem título / página em branco)}"
DIAG_STATUS=$(docker exec "$SELENIUM_CONTAINER" bash -c "curl -so /dev/null -w '%{http_code}' --max-time 10 'http://$URL_NAME/'" 2>/dev/null || echo "???")
log "  HTTP status: $DIAG_STATUS"

BEHAT_CMD="cd '$MOODLE_ROOT_IN_CONTAINER' && vendor/bin/behat --config='$BEHAT_YML'"
EXTRA_ARGS_ESCAPED="$(build_escaped_args "${BEHAT_EXTRA_ARGS[@]}")"

if [ -n "$FEATURE_FILE" ]; then
    if [[ "$FEATURE_FILE" == /* ]]; then
        FEATURE_PATH="$FEATURE_FILE"
    else
        FEATURE_PATH="$MOODLE_ROOT_IN_CONTAINER/report/unasus/$FEATURE_FILE"
    fi
    log "Feature: $FEATURE_PATH"
    echo ""
    exec_as_moodle "$BEHAT_CMD $(printf "%q" "$FEATURE_PATH")$EXTRA_ARGS_ESCAPED"

elif [ -n "$TAGS_ARG" ]; then
    log "Tags: $TAGS_ARG"
    echo ""
    exec_as_moodle "$BEHAT_CMD $(printf "%q" "$TAGS_ARG")$EXTRA_ARGS_ESCAPED"

else
    log "Tag padrão: $PLUGIN_TAG"
    echo ""
    exec_as_moodle "$BEHAT_CMD --tags='$PLUGIN_TAG'$EXTRA_ARGS_ESCAPED"
fi

echo ""
log "============================================================"
log " Testes Behat concluídos."
log "============================================================"
