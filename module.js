M.report_unasus = {};

M.report_unasus.init = function(Y) {
    node_tutor = Y.one('#div_tutor');
    node_polo = Y.one('#div_polo');
    if(!node_tutor || !node_polo)
        return;
    node_tutor.hide();
    node_polo.hide();

    Y.on('click', function(e) {
        node_tutor.show();
        node_polo.hide();
    },'#id_radiofilter_tutor' );

    Y.on('click', function(e) {
        node_tutor.hide();
        node_polo.show();
    },'#id_radiofilter_polo' );

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