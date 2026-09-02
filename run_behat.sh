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
#   ./run_behat.sh --parallel=4                     # Executa em 4 workers paralelos
#   ./run_behat.sh --parallel                       # Idem, usando BEHAT_PARALLEL do .env
#
# Execução paralela:
#   O Moodle instala um site por worker (/behatrunN, com dataroot e prefixo próprios) e
#   distribui as features entre eles. A primeira execução com um novo N reinstala os sites,
#   o que é demorado; depois disso o ambiente é reaproveitado.
#
#   O balanceamento usa BEHAT_FEATURE_TIMING_FILE (definido no config.php): o Behat cronometra
#   cada feature e, nas execuções seguintes, aloca a mais cara ao worker mais leve. Sem esse
#   arquivo a divisão é por quantidade de features, ignorando o custo de cada uma.
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
# Como no run_tests.sh: tudo abaixo e' derivado de CORE_NAME/DOCKER_VERSION e pode ser
# sobreposto no .env, para ambientes que fogem da convencao.
SISTEM_NAME="${SISTEM_NAME:-local-$CORE_NAME}"
CONTAINER_NAME="${CONTAINER_NAME:-moodle-$SISTEM_NAME}"
SELENIUM_CONTAINER="${SELENIUM_CONTAINER:-selenium-chrome-$CORE_NAME}"
# Imagem do Selenium. A 3.141 (Chrome 75, de 2019) fala o protocolo OSS; o Moodle 4.5 usa
# o driver W3C (OAndreyev\Mink\Driver\WebDriver) e precisa de Selenium 4. Ambientes antigos
# seguem no default; os novos definem SELENIUM_IMAGE no .env.
SELENIUM_IMAGE="${SELENIUM_IMAGE:-selenium/standalone-chrome:3.141.59-selenium}"
# Porta do HOST publicada para o Selenium (lado esquerdo do -p). A porta interna do
# container é sempre 4444. Parametrizável via SELENIUM_PORT no .env para permitir rodar
# vários ambientes em paralelo sem conflito na 4444. Default: 4444.
SELENIUM_HOST_PORT="${SELENIUM_PORT:-4444}"
DOCKER_COMPOSE_DIR="${DOCKER_COMPOSE_DIR:-/home/$USER/workspace/docker/$DOCKER_VERSION}"
MOODLE_LOCAL_SITE="${MOODLE_LOCAL_SITE:-www/$SISTEM_NAME}"
MOODLE_ROOT_IN_CONTAINER="${MOODLE_ROOT_IN_CONTAINER:-/home/moodle/$MOODLE_LOCAL_SITE}"
CONTAINER_USER="${CONTAINER_USER:-moodle}"
DOCKER_NETWORK="${DOCKER_NETWORK:-moodle-network-$DOCKER_VERSION}"
BEHAT_PREFIX="${BEHAT_PREFIX:-bht_}"
BEHAT_DATAROOT="${BEHAT_DATAROOT:-/home/moodle/moodledata/${BEHAT_PREFIX}$SISTEM_NAME}"
# Alguns sites so' respondem em https; o esquema vem do .env quando difere.
BEHAT_WWWROOT="${BEHAT_WWWROOT:-http://$URL_NAME}"
BEHAT_ENABLE_FILE="/tmp/.${BEHAT_PREFIX}${SISTEM_NAME}_enabled"
PLUGIN_COMPONENT="report_unasus"
PLUGIN_TAG="@report_unasus"
MOODLE_ENABLE_BEHAT=1

# Argumentos
INIT_FLAG=""
FEATURE_FILE=""
TAGS_ARG=""
PARALLEL_RUNS=""
BEHAT_EXTRA_ARGS=()
for arg in "$@"; do
    case "$arg" in
        --init)       INIT_FLAG="yes" ;;
        --tags=*)     TAGS_ARG="$arg" ;;
        --parallel=*) PARALLEL_RUNS="${arg#*=}" ;;
        --parallel)   PARALLEL_RUNS="${BEHAT_PARALLEL:-4}" ;;
        -*)           BEHAT_EXTRA_ARGS+=("$arg") ;;
        *)            FEATURE_FILE="$arg" ;;
    esac
done

# Sem --parallel explícito, o .env decide. Ausente, mantém o modo sequencial.
if [ -z "$PARALLEL_RUNS" ] && [ -n "${BEHAT_PARALLEL:-}" ]; then
    PARALLEL_RUNS="$BEHAT_PARALLEL"
fi

if [ -n "$PARALLEL_RUNS" ]; then
    if ! [[ "$PARALLEL_RUNS" =~ ^[0-9]+$ ]] || [ "$PARALLEL_RUNS" -lt 1 ]; then
        echo "ERRO: --parallel espera um inteiro >= 1 (recebido: '$PARALLEL_RUNS')" >&2
        exit 1
    fi
    [ "$PARALLEL_RUNS" -eq 1 ] && PARALLEL_RUNS=""   # 1 worker é o próprio modo sequencial
fi

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
    docker exec -u "$CONTAINER_USER" "$CONTAINER_NAME" bash -c "$1"
}

exec_php_as_moodle_for_init() {
    docker exec -u "$CONTAINER_USER" "$CONTAINER_NAME" bash -c "$1"
}

enable_behat_environment() {
    log "Ativando configuração Behat para esta execução..."
    # Ensure parent directory exists and is owned by moodle user
    docker exec -u 0 "$CONTAINER_NAME" bash -c "mkdir -p '$(dirname "$BEHAT_DATAROOT")' && chown $CONTAINER_USER:$CONTAINER_USER '$(dirname "$BEHAT_DATAROOT")' && chmod 755 '$(dirname "$BEHAT_DATAROOT")'"

    # O behat_dataroot é volume montado do host: criado por lá, fica com o uid do host, que não é o
    # do usuário moodle do container. Sem escrita para o moodle, o util.php --enable aborta com
    # "behat_dataroot ... must point to an existing writable directory" antes de rodar cenário algum.
    # O chown precisa ser como root: feito como moodle, falha em silêncio.
    # Na execução paralela cada worker usa "$BEHAT_DATAROOT<N>", que precisa do mesmo tratamento.
    local dataroots="'$BEHAT_DATAROOT'"
    if [ -n "$PARALLEL_RUNS" ]; then
        local i
        for i in $(seq 1 "$PARALLEL_RUNS"); do
            dataroots="$dataroots '${BEHAT_DATAROOT}${i}'"
        done
    fi
    docker exec -u 0 "$CONTAINER_NAME" bash -c "mkdir -p $dataroots && chown -R moodle:moodle $dataroots"

    exec_as_moodle "touch '$BEHAT_ENABLE_FILE' && rm -f '$BEHAT_DATAROOT/.behat_enabled'"

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
    # No modo paralelo o util.php --enable (sem --parallel) enxergaria o ambiente como
    # sequencial e abortaria com "initialised for a different version". O init.php --parallel
    # já deixa os sites habilitados, e o run.php cuida do resto — então aqui não há o que fazer.
    if [ -n "$PARALLEL_RUNS" ]; then
        return
    fi
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

    # O dirroot e' um bind mount e o container roda com namespace de usuario mapeado: o
    # composer nao consegue reescrever vendor/composer/installed.php. A permissao e' dada
    # pelo host, onde ela existe -- mesmo contorno que o run_tests.sh usa no dirroot.
    MOODLE_HOST_DIR="$DOCKER_COMPOSE_DIR/$MOODLE_LOCAL_SITE"
    if [ -d "$MOODLE_HOST_DIR/vendor/composer" ]; then
        log "Liberando escrita em vendor/composer a partir do host..."
        chmod a+w "$MOODLE_HOST_DIR" "$MOODLE_HOST_DIR/vendor" 2>/dev/null || true
        chmod -R a+w "$MOODLE_HOST_DIR/vendor/composer" 2>/dev/null || true
        # ⚠️ E o proprio autoload.php: o composer o REESCREVE ao gerar o autoload, e o
        # arquivo ja' existe com o uid do host. Sem esta linha o init falha com
        # "file_put_contents(vendor/autoload.php): Permission denied" -- DEPOIS de ja' ter
        # derrubado a base de teste, deixando o ambiente sem tabelas e com uma mensagem
        # seguinte que nao diz onde foi a queda.
        chmod a+w "$MOODLE_HOST_DIR/vendor/autoload.php" 2>/dev/null || true
    fi
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
# O config.php e' um bind mount do host. Com mapeamento de namespace de usuario, o root
# do container nao consegue escrever nele -- o `sed -i` falha em criar o arquivo temporario.
# Por isso as edicoes sao feitas pelo host, onde a permissao existe. Mesmo obstaculo que o
# run_tests.sh ja' contorna ao inicializar o PHPUnit.
CONFIG_NO_HOST="$DOCKER_COMPOSE_DIR/$MOODLE_LOCAL_SITE/config.php"

if [ ! -w "$CONFIG_NO_HOST" ]; then
    err "config.php do host não encontrado ou sem permissão de escrita: $CONFIG_NO_HOST"
fi

log "Verificando configuração Behat no config.php..."

BEHAT_CONFIGURED=$(exec_as_moodle "
    grep -v '^[[:space:]]*//' '$MOODLE_ROOT_IN_CONTAINER/config.php' | \
    grep -q 'behat_prefix' && echo yes || echo no
" 2>/dev/null || echo "no")

if [ "$BEHAT_CONFIGURED" != "yes" ]; then
    warn "Behat não configurado no config.php. Adicionando configurações..."

    # Diretórios e ownership já garantidos por enable_behat_environment().

    python3 - "$CONFIG_NO_HOST" "$BEHAT_WWWROOT" "$BEHAT_PREFIX" "$BEHAT_DATAROOT" \
             "$SELENIUM_CONTAINER" "$BEHAT_ENABLE_FILE" <<'PYCFG'
import io, re, sys

caminho, wwwroot, prefixo, dataroot, selenium, arquivo_ativo = sys.argv[1:7]
conteudo = io.open(caminho, encoding='utf-8').read()

# O bloco fica dentro de um file_exists(): sem essa guarda o Moodle enxerga behat_dataroot
# o tempo todo e o site normal quebra com HTTP 500, porque o diretorio so' existe enquanto o
# Behat esta' ligado. E' o modelo que o unasus-cp usa.
bloco = (
    "// Behat: so' ativo enquanto o arquivo-sentinela existir. Escrito por run_behat.sh.\n"
    "if (file_exists('%s')) {\n"
    "    $CFG->behat_wwwroot  = '%s';\n"
    "    $CFG->behat_prefix   = '%s';\n"
    "    $CFG->behat_dataroot = '%s';\n"
    "    $CFG->behat_faildump_path = '%s/faildumps';\n"
    "\n"
    "    define('BEHAT_FEATURE_TIMING_FILE', '%s/timing.json');\n"
    "\n"
    "    $CFG->behat_config = array(\n"
    "        'default' => array(\n"
    "            'extensions' => array(\n"
    "                // Forma do Moodle 4.x: `Behat\\MinkExtension` + `webdriver`.\n"
    "                // A antiga (`...\\Extension` + `selenium2`) e' ignorada em SILENCIO\n"
    "                // pelo gerador do behat.yml, que entao cai no padrao: Firefox em\n"
    "                // localhost:4444. O erro resultante fala em Selenium fora do ar e\n"
    "                // aponta para o lugar errado.\n"
    "                'Behat\\\\MinkExtension' => array(\n"
    "                    'webdriver' => array(\n"
    "                        'browser'      => 'chrome',\n"
    "                        'wd_host'      => 'http://%s:4444/wd/hub',\n"
    "                        'capabilities' => array(\n"
    "                            'extra_capabilities' => array(\n"
    "                                'goog:chromeOptions' => array(\n"
    "                                    'args' => array('no-sandbox', 'disable-dev-shm-usage'),\n"
    "                                ),\n"
    "                            ),\n"
    "                        ),\n"
    "                    ),\n"
    "                ),\n"
    "            ),\n"
    "        ),\n"
    "    );\n"
    "}\n\n"
) % (arquivo_ativo, wwwroot, prefixo, dataroot, dataroot, dataroot, selenium)

alvo = re.search(r'^.*require_once.*lib/setup\.php.*$', conteudo, re.M)
if not alvo:
    sys.exit("require_once de lib/setup.php nao encontrado em " + caminho)

conteudo = conteudo[:alvo.start()] + bloco + conteudo[alvo.start():]
io.open(caminho, 'w', encoding='utf-8').write(conteudo)
PYCFG

    log "Configurações Behat adicionadas ao config.php."
fi

# ⚠️ AQUI HAVIA UMA "CORRECAO" QUE QUEBRAVA A EXECUCAO NO MOODLE 4.5.
#
# O bloco antigo tratava `chromeOptions`/`extra_capabilities` no config.php como
# configuracao desatualizada e reescrevia para a forma do MinkExtension 1.x
# (`selenium2` + `capabilities.chrome.switches`). So' que o gerador do behat.yml do
# Moodle 4.5 le' a forma NOVA (`Behat\MinkExtension` + `webdriver`) e IGNORA a antiga em
# silencio: o yml saia com o padrao -- Firefox em localhost:4444 -- e o teste falhava
# dizendo "The Selenium or WebDriver server is not running", apontando para o lugar
# errado. O Selenium estava no ar; o Behat e' que procurava outro navegador noutro
# endereco.
#
# Pior: quando a reescrita falhava por permissao (config.php pertence ao uid do host,
# nao ao do container), o bloco ainda marcava INIT_FLAG=yes -- e cada execucao passava a
# derrubar e recriar a base de teste antes de testar, num laco que nunca estabilizava.
#
# Nada substitui o bloco: a configuracao correta vive no config.php e nao cabe a este
# script reescrever. O que cabe e' AVISAR quando a forma antiga estiver la'.
BEHAT_CONFIG_LEGADO=$(exec_as_moodle "
    grep -q \"MinkExtension..Extension\|'selenium2'\" '$MOODLE_ROOT_IN_CONTAINER/config.php' && echo yes || echo no
" 2>/dev/null || echo "no")

if [ "$BEHAT_CONFIG_LEGADO" = "yes" ]; then
    warn "config.php usa a forma antiga do MinkExtension (Extension/selenium2)."
    warn "No Moodle 4.x ela e' ignorada em silencio e o Behat cai no padrao (Firefox em localhost:4444)."
    warn "Use: 'Behat\\MinkExtension' => array('webdriver' => array('browser' => ..., 'wd_host' => ...))"
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

# ---------------------------------------------------------------------------
# 4. Inicializar (ou reinicializar) o ambiente Behat
# ---------------------------------------------------------------------------
# O caminho do behat.yml mudou entre versoes do Moodle: nas antigas fica direto em
# <dataroot>/behat/, nas 4.x sob <dataroot>/behatrun/behat/. Como este script serve varios
# ambientes, o arquivo e' procurado em vez de assumido.
BEHAT_YML=$(exec_as_moodle "find '$BEHAT_DATAROOT' -type f -name behat.yml -path '*/behat/*' 2>/dev/null | head -1" 2>/dev/null || true)
BEHAT_YML="${BEHAT_YML:-$BEHAT_DATAROOT/behat/behat.yml}"

# Nº de workers para o qual o ambiente está instalado. O Moodle grava esse valor em
# parallel_environment_enabled.txt dentro do behat dir de cada worker; ausente = sequencial.
current_parallel_runs() {
    exec_as_moodle "cat '${BEHAT_DATAROOT}1/behat/parallel_environment_enabled.txt' 2>/dev/null" 2>/dev/null | tr -d '[:space:]' || true
}

if [ -n "$PARALLEL_RUNS" ]; then
    INSTALLED_RUNS="$(current_parallel_runs)"
    if [ "$INSTALLED_RUNS" != "$PARALLEL_RUNS" ] || [ -n "$INIT_FLAG" ]; then
        # O dirroot precisa ser gravável: o run.php cria os symlinks behatrun1..N nele.
        chmod a+w "$DOCKER_COMPOSE_DIR/$MOODLE_LOCAL_SITE" 2>/dev/null || true

        if [ -n "$INSTALLED_RUNS" ]; then
            log "Ambiente instalado para $INSTALLED_RUNS worker(s); reinstalando para $PARALLEL_RUNS..."
        else
            log "Instalando ambiente Behat paralelo com $PARALLEL_RUNS workers (demorado: um site por worker)..."
        fi
        ensure_legacy_composer_for_behat_init
        exec_php_as_moodle_for_init "MOODLE_SKIP_COMPOSER_SELF_UPDATE=1 USE_ZEND_ALLOC=0 php -d memory_limit=512M '$MOODLE_ROOT_IN_CONTAINER/admin/tool/behat/cli/init.php' --parallel=$PARALLEL_RUNS 2>&1"
        log "Ambiente paralelo pronto ($PARALLEL_RUNS workers)."
    else
        log "Ambiente Behat paralelo já inicializado ($PARALLEL_RUNS workers)."
    fi

elif [ -n "$INIT_FLAG" ]; then
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

    # ⚠️ RE-RESOLVE o caminho: o behat.yml so' passa a existir AGORA.
    #
    # A resolucao la' em cima roda antes do init. Num ambiente ainda nao inicializado --
    # ou logo apos um --init -- o `find` nao acha nada e cai no palpite
    # "<dataroot>/behat/behat.yml", enquanto o Moodle 4.x cria em
    # "<dataroot>/behatrun/behat/behat.yml". A execucao seguinte morria com "The
    # requested config file does not exist", depois de varios minutos de init bem
    # sucedido -- e bastava rodar de novo para funcionar, o que mascarava a causa.
    BEHAT_YML=$(exec_as_moodle "find '$BEHAT_DATAROOT' -type f -name behat.yml -path '*/behat/*' 2>/dev/null | head -1" 2>/dev/null || true)
    BEHAT_YML="${BEHAT_YML:-$BEHAT_DATAROOT/behat/behat.yml}"

elif ! exec_as_moodle "test -f '$BEHAT_YML'" 2>/dev/null; then
    log "Inicializando ambiente Behat pela primeira vez (pode demorar alguns minutos)..."

    # Garante permissão de escrita no dirroot para o container criar behat.yml
    MOODLE_HOST_DIR="$DOCKER_COMPOSE_DIR/$MOODLE_LOCAL_SITE"
    chmod a+w "$MOODLE_HOST_DIR"

    ensure_legacy_composer_for_behat_init
    exec_php_as_moodle_for_init "MOODLE_SKIP_COMPOSER_SELF_UPDATE=1 USE_ZEND_ALLOC=0 php -d memory_limit=512M '$MOODLE_ROOT_IN_CONTAINER/admin/tool/behat/cli/init.php' 2>&1"
    log "Behat inicializado com sucesso."

    # Mesmo motivo do bloco de reinicializacao: o arquivo acabou de nascer.
    BEHAT_YML=$(exec_as_moodle "find '$BEHAT_DATAROOT' -type f -name behat.yml -path '*/behat/*' 2>/dev/null | head -1" 2>/dev/null || true)
    BEHAT_YML="${BEHAT_YML:-$BEHAT_DATAROOT/behat/behat.yml}"
else
    log "Ambiente Behat já inicializado."
fi

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

EXTRA_ARGS_ESCAPED="$(build_escaped_args "${BEHAT_EXTRA_ARGS[@]}")"

if [ -n "$PARALLEL_RUNS" ]; then
    # O run.php orquestra um processo behat por worker, cada um com seu behat.yml.
    # Diferente do behat, ele não aceita a feature como argumento posicional: exige --feature=.
    log "Modo paralelo: $PARALLEL_RUNS workers"
    BEHAT_CMD="cd '$MOODLE_ROOT_IN_CONTAINER' && MOODLE_SKIP_COMPOSER_SELF_UPDATE=1 USE_ZEND_ALLOC=0 php -d memory_limit=512M admin/tool/behat/cli/run.php"
else
    BEHAT_CMD="cd '$MOODLE_ROOT_IN_CONTAINER' && vendor/bin/behat --config='$BEHAT_YML'"
fi

if [ -n "$FEATURE_FILE" ]; then
    if [[ "$FEATURE_FILE" == /* ]]; then
        FEATURE_PATH="$FEATURE_FILE"
    else
        FEATURE_PATH="$MOODLE_ROOT_IN_CONTAINER/report/unasus/$FEATURE_FILE"
    fi
    log "Feature: $FEATURE_PATH"
    echo ""
    if [ -n "$PARALLEL_RUNS" ]; then
        exec_as_moodle "$BEHAT_CMD --feature=$(printf "%q" "$FEATURE_PATH")$EXTRA_ARGS_ESCAPED"
    else
        exec_as_moodle "$BEHAT_CMD $(printf "%q" "$FEATURE_PATH")$EXTRA_ARGS_ESCAPED"
    fi

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
