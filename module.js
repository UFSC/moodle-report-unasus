M.report_unasus = {};

M.report_unasus.init = function(Y) {
    if(YAHOO.util.Dom.hasClass('button-mostrar-filtro', 'visible')){
        YAHOO.util.Dom.removeClass('button-mostrar-filtro','hidden');
        YAHOO.util.Dom.addClass('div_filtro','hidden');
    }else{
        YAHOO.util.Dom.addClass('div_filtro','visible');
    }

    Y.delegate('click', function(e) {
        var buttonID = e.currentTarget.get('id');
        if (buttonID === 'button-mostrar-filtro') {

            if(YAHOO.util.Dom.hasClass('div_filtro', 'visible')){
                YAHOO.util.Dom.get('button-mostrar-filtro').firstChild.data = 'Mostrar Filtro';
                YAHOO.util.Dom.addClass('div_filtro','hidden');
                YAHOO.util.Dom.removeClass('div_filtro','visible');
            }else{
                YAHOO.util.Dom.get('button-mostrar-filtro').firstChild.data = 'Ocultar Filtro';
                YAHOO.util.Dom.addClass('div_filtro','visible');
                YAHOO.util.Dom.removeClass('div_filtro','hidden');
            }
        }

    }, document, 'button');
};


var chart1;
M.report_unasus.init_graph = function(Y, param1, tipos, title, porcentagem) {
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

    for(tutor in param1){
        options.xAxis.categories.push(tutor);

        for(d in data){
            data[d].push(param1[tutor][d]);
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