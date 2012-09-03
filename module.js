M.report_unasus = {};

M.report_unasus.init = function(Y) {

    if(YAHOO.util.Dom.hasClass('button-mostrar-filtro', 'visible')){
        YAHOO.util.Dom.removeClass('button-mostrar-filtro','hidden');
        YAHOO.util.Dom.addClass('div_filtro','hidden');
    }else{
        YAHOO.util.Dom.addClass('div_filtro','visible');
    }

    Y.delegate('click', function(e) {
        if(YAHOO.util.Dom.hasClass('div_filtro', 'visible')){
            YAHOO.util.Dom.get('button-mostrar-filtro').firstChild.data = 'Mostrar Filtro';
            YAHOO.util.Dom.addClass('div_filtro','hidden');
            YAHOO.util.Dom.removeClass('div_filtro','visible');
        }else{
            YAHOO.util.Dom.get('button-mostrar-filtro').firstChild.data = 'Ocultar Filtro';
            YAHOO.util.Dom.addClass('div_filtro','visible');
            YAHOO.util.Dom.removeClass('div_filtro','hidden');
        }
    }, document, '#button-mostrar-filtro');

    Y.delegate('click', function(e) {
        select_all('multiple_modulo',true);
    }, document, '#select_all_modulo');

    Y.delegate('click', function(e) {
        select_all('multiple_modulo',false);
    }, document, '#select_none_modulo');

    Y.delegate('click', function(e) {
        select_all('multiple_polo',true);
    }, document, '#select_all_polo');

    Y.delegate('click', function(e) {
        select_all('multiple_polo',false);
    }, document, '#select_none_polo');

    Y.delegate('click', function(e) {
        select_all('multiple_tutor',true);
    }, document, '#select_all_tutor');

    Y.delegate('click', function(e) {
        select_all('multiple_tutor',false);
    }, document, '#select_none_tutor');

};

function select_all(target, select){
    var multiple = YAHOO.util.Dom.get(target);
    for(item in multiple){
        multiple[item].selected = select;
    }
}


var chart1;
M.report_unasus.init_graph = function(Y, dados_grafico, tipos, title, porcentagem) {
    var stack_option = 'normal';
    if(porcentagem)
        stack_option = 'percent';

    var options = {
        chart: {
            renderTo: 'container',
            type: 'bar'
        },
        title: {
            text: title
        },
        xAxis: {
            categories: []
        },
        yAxis: {
            title: {
                text: "Estado das avalições"
            }
        },
        legend: {
            reversed:true,
            layout: 'vertical'
        },
        plotOptions: {
            series: {

                stacking: stack_option

            }
        },
        series: []
    };

    if(porcentagem){
        options['tooltip'] = {
            formatter:  function(){
                return '<b>'+this.x+'</b>'  +'<br><span style="color:'+ this.series.color + '">' + this.series.name +'</span>: ' + Math.round(this.percentage*100)/100 +' % (quantidade: '+this.y + ' de ' + this.total+ ')';
            }
        }
    }

    var data = [];
    for(tipo in tipos){
        data[tipo] = [];
    }

    for(tutor in dados_grafico){
        options.xAxis.categories.push(tutor);

        for(d in data){
            data[d].push(dados_grafico[tutor][d]);
        }

    }

    for(tipo in tipos){
        options.series.push({
            name: tipos[tipo],
            data: data[tipo]
        })
    }

    chart1 = new Highcharts.Chart(options)
}

M.report_unasus.init_dot_graph = function (Y, dados){
    var xs = [];
    var ys = [];
    var data = [];
    var axisy = [];
    var axisx = [];

    var count_tutor = objectLength(dados);
    var ysize = count_tutor-1;
    var count_semana;
    var primeira_vez = true;

    for(tutor in dados){
        count_semana = 0;
        axisy.push(tutor);

        for(dias in dados[tutor]){
            if(primeira_vez){
                axisx.push(dias);
            }
            data.push(dados[tutor][dias]);
            ys.push(count_tutor);
            xs.push(count_semana);
            count_semana++;
        }
        count_tutor--;
        primeira_vez = false;
    }
    axisy = axisy.reverse();

    var r = Raphael("container"), xs, ys, data, axisy , axisx;
    r.dotchart(10, 10, 620, 260, xs, ys, data, {
        symbol: "o",
        max: 15,
        heat: true,
        axis: "0 0 1 1",
        axisxstep: count_semana-1,
        axisystep: ysize,
        axisxlabels: axisx,
        axisxtype: "+",
        axisytype: "+",
        axisylabels: axisy
    }).hover(function () {
        this.marker = this.marker || r.tag(this.x, this.y, this.value, 0, this.r + 2).insertBefore(this);
        this.marker.show();
    }, function () {
        this.marker && this.marker.hide();
    });
};

function objectLength(obj) {
    var result = 0;
    for(var prop in obj) {
        if (obj.hasOwnProperty(prop)) {
            // or Object.prototype.hasOwnProperty.call(obj, prop)
            result++;
        }
    }
    return result;
}