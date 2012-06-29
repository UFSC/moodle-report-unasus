M.report_unasus = {};

M.report_unasus.init = function(Y) {
    node_tutor = Y.one('#div_tutor');
    node_tutor.hide();
    
    node_polo = Y.one('#div_polo');
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

