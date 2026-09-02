M.report_unasus = {};

/**
 * Publica a posicao do topo da tabela em `--topo-relatorio`, para o styles.css calcular
 * a altura util ate' o fim da janela (ver "A altura util vai ate' o fim da JANELA").
 *
 * A conta usa a posicao no DOCUMENTO (rect.top + scrollY), e nao na viewport: assim o
 * valor nao muda quando a pagina rola, e a caixa nao fica mudando de tamanho debaixo do
 * cursor de quem esta' lendo.
 */
M.report_unasus.ajusta_altura_util = function() {
    var wrapper = document.querySelector('.relatorio-wrapper');
    if (!wrapper) {
        return; // relatorio ainda no formulario de filtro, sem tabela
    }

    var topo = wrapper.getBoundingClientRect().top + (window.scrollY || window.pageYOffset || 0);
    document.documentElement.style.setProperty('--topo-relatorio', Math.round(topo) + 'px');
};

M.report_unasus.init = function(Y) {

    // Se javascript for executado ele mostra o botão ocultar/mostrar filtros
    var $filter_button = Y.one('#button-mostrar-filtro');
    var $filter_div = Y.one('#div_filtro');

    if ($filter_button.hasClass('visible')) {
        $filter_button.removeClass('hidden');
        $filter_div.addClass('hidden');
    } else {
        $filter_div.addClass('visible');
    }

    // Ao clicar no botao mostrar/ocultar filtros ele esconde/mostra a barra e troca o seu texto
    Y.delegate('click', function(e) {
        var $filter_button = Y.one('#button-mostrar-filtro');
        var $filter_div = Y.one('#div_filtro');

        if ($filter_div.hasClass('visible')) {
            $filter_button.set('text', 'Mostrar Filtro');
            $filter_div.addClass('hidden');
            $filter_div.removeClass('visible');
        } else {
            $filter_button.set('text', 'Ocultar Filtro');
            $filter_div.addClass('visible');
            $filter_div.removeClass('hidden');
        }

        // ⚠️ Abrir ou fechar o painel EMPURRA a tabela, e a altura util depende de onde
        // ela comeca. Sem recalcular aqui, expandir o filtro joga a caixa para fora da
        // janela de novo -- que e' o defeito que a altura responsiva veio corrigir.
        M.report_unasus.ajusta_altura_util();
    }, document, '#button-mostrar-filtro');

    M.report_unasus.ajusta_altura_util();

    // O resize dispara em rajada enquanto se arrasta a borda da janela; um quadro de
    // espera basta para nao recalcular dezenas de vezes por segundo.
    var aguardando = false;
    window.addEventListener('resize', function() {
        if (aguardando) {
            return;
        }
        aguardando = true;
        window.requestAnimationFrame(function() {
            aguardando = false;
            M.report_unasus.ajusta_altura_util();
        });
    });

    // Botoes de selecionar todos e limpar selecao.
    //
    // Todos cancelam o evento: os links sao <a href="#"> e, sem `preventDefault`, o
    // clique salta para o topo da pagina e ainda suja a URL com um "#".
    Y.delegate('click', function(e) {
        e.preventDefault();
        select_all('#multiple_cohort', true);
    }, document, '#select_all_cohort');
    
    Y.delegate('click', function(e) {
        e.preventDefault();
        select_all('#multiple_cohort', false);
    }, document, '#select_none_cohort');
    
    Y.delegate('click', function(e) {
        e.preventDefault();
        select_all('#multiple_modulo', true);
    }, document, '#select_all_modulo');

    Y.delegate('click', function(e) {
        e.preventDefault();
        select_all('#multiple_modulo', false);
    }, document, '#select_none_modulo');

    Y.delegate('click', function(e) {
        e.preventDefault();
        select_all('#multiple_polo', true);
    }, document, '#select_all_polo');

    Y.delegate('click', function(e) {
        e.preventDefault();
        select_all('#multiple_polo', false);
    }, document, '#select_none_polo');

    Y.delegate('click', function(e) {
        e.preventDefault();
        select_all('#multiple_tutor', true);
    }, document, '#select_all_tutor');

    Y.delegate('click', function(e) {
        e.preventDefault();
        select_all('#multiple_tutor', false);
    }, document, '#select_none_tutor');

};

/**
 * Marca ou desmarca todas as opcoes de um <select multiple>.
 *
 * ⚠️ Esta funcao era CHAMADA em oito lugares e nao existia em lugar nenhum -- nem aqui,
 * nem no core do Moodle (la' os nomes sao `select_all_in_element_with_id` e
 * `select_all_in`). Os links "Selecionar Todos" e "Limpar Selecao" estouravam um
 * ReferenceError no console e nao faziam nada; conferido no ar: 0 de 22 opcoes marcadas
 * depois do clique.
 *
 * @param {String} seletor seletor CSS do <select>
 * @param {Boolean} selecionar true para marcar todas, false para limpar
 */
function select_all(seletor, selecionar) {
    var select = document.querySelector(seletor);

    if (!select) {
        return;
    }

    for (var i = 0; i < select.options.length; i++) {
        select.options[i].selected = selecionar;
    }
}

/**
 * @param obj array(array())
 * @return int quantidade de itens pai no array, utilizado para saber quantos tutores foram enviados
 **/
function objectLength(obj) {
    var result = 0;
    for (var prop in obj) {
        if (obj.hasOwnProperty(prop)) {
            result++;
        }
    }
    return result;
}

M.report_unasus.fixed_columns = function(Y) {
    if (!Y || !Y.all || typeof SELECTORS === 'undefined' || !SELECTORS.USERCELL) {
        return;
    }
    // Grab all cells in the user names column.
    var userColumn = Y.all(SELECTORS.USERCELL),

    // Create a floating table.
        floatingUserColumn = Y.Node.create('<div aria-hidden="true" role="presentation" class="floater sideonly"></div>'),

    // Get the XY for the floating element.
        coordinates = this._getRelativeXY(this.firstUserCell);

    // Generate the new fields.
    userColumn.each(function(node) {
        var height = node.getComputedStyle(HEIGHT);
        // Nasty hack to account for Internet Explorer
        if(Y.UA.ie !== 0) {
            var allHeight = node.get('offsetHeight');
            var marginHeight = parseInt(node.getComputedStyle('marginTop'),10) +
                parseInt(node.getComputedStyle('marginBottom'),10);
            var paddingHeight = parseInt(node.getComputedStyle('paddingTop'),10) +
                parseInt(node.getComputedStyle('paddingBottom'),10);
            var borderHeight = parseInt(node.getComputedStyle('borderTopWidth'),10) +
                parseInt(node.getComputedStyle('borderBottomWidth'),10);
            height = allHeight - marginHeight - paddingHeight - borderHeight;
        }
        // Create and configure the new container.
        var containerNode = Y.Node.create('<div></div>');
        containerNode.set('innerHTML', node.get('innerHTML'))
            .setAttribute('class', node.getAttribute('class'))
            .setAttribute('data-uid', node.ancestor('tr').getData('uid'))
            .setStyles({
                height: height,
                width:  node.getComputedStyle(WIDTH)
            });

        // Add the new nodes to our floating table.
        floatingUserColumn.appendChild(containerNode);
    }, this);

    // Style the floating user container.
    floatingUserColumn.setStyles({
        left:       coordinates[0] + 'px',
        position:   'absolute',
        top:        coordinates[1] + 'px'
    });

    // Append to the grader region.
    this.graderRegion.append(floatingUserColumn);

    // Store a reference to this for later - we use it in the event handlers.
    this.userColumn = floatingUserColumn;
}

