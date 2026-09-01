M.report_unasus = {};

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
    }, document, '#button-mostrar-filtro');

    //Botoes de selecionar todos e limpar seleção
    Y.delegate('click', function(e) {
        select_all('#multiple_cohort', true);
    }, document, '#select_all_cohort');
    
    Y.delegate('click', function(e) {
        select_all('#multiple_cohort', false);
    }, document, '#select_none_cohort');
    
    Y.delegate('click', function(e) {
        select_all('#multiple_modulo', true);
    }, document, '#select_all_modulo');

    Y.delegate('click', function(e) {
        select_all('#multiple_modulo', false);
    }, document, '#select_none_modulo');

    Y.delegate('click', function(e) {
        select_all('#multiple_polo', true);
    }, document, '#select_all_polo');

    Y.delegate('click', function(e) {
        select_all('#multiple_polo', false);
    }, document, '#select_none_polo');

    Y.delegate('click', function(e) {
        select_all('#multiple_tutor', true);
    }, document, '#select_all_tutor');

    Y.delegate('click', function(e) {
        select_all('#multiple_tutor', false);
    }, document, '#select_none_tutor');

};

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

