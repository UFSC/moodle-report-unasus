<?php

//moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");

/**
 *
 * Classe para cricação da barra de filtragem tutor/polo para o
 * relatório de atividades vs notas atribuidas
 * 
 *  
 */
class filter_tutor_polo extends moodleform {

    // adicao de elementos para a form
    function definition() {
        global $CFG;

        $mform = & $this->_form;
                
        $mform->addElement('header','filter_legend', 'Filtrar Estudantes');
        
        //select box do estado da avaliacao
        $select_nota = & $mform->addElement('select', 'situacao', "Situação:", 
                                array("Em Aberto", "Em Dia", "Expirado", "Fora do Prazo"));
         
        
        //radio button da filtragem do tipo de estudante, por tutor ou polo
        $radioarray = array();
        $radioarray[] = &MoodleQuickForm::createElement('radio', 'radiofilter', 'tutor', 'por Tutor', 'tutor');
        $radioarray[] = &MoodleQuickForm::createElement('radio', 'radiofilter', 'polo', 'por Polo', 'polo');
        $mform->addGroup($radioarray, '', 'Filtrar Estudantes: ', null);
    
        
        //div dos nomes dos tutores
        $mform->addElement('html','<div class="relatorio-unasus filter div_list_show" id="div_tutor" >');
        $multiple_tutor = & $mform->addElement('select', 'multiple_tutor',"Tutores",get_nomes_tutores()); 
        $multiple_tutor->setMultiple(true);
        $mform->addElement('html','</div>');
        
        //div dos nomes dos polos
        $mform->addElement('html','<div class="relatorio-unasus filter div_list_show" id="div_polo">');
        $multiple_tutor = & $mform->addElement('select', 'multiple_polo',"Polos", get_nomes_polos()); 
        $multiple_tutor->setMultiple(true);
        $mform->addElement('html','</div>');
        
        //submit button, ainda falta alinhar ele
        $mform->addElement('submit','button_filtrar','Filtrar',array('text_align'));
        
    }

}

