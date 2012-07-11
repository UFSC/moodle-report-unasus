<?php

//moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");

/**
 * Formulário para cricação da caixa de filtragem
 * A filtragem pode ser feita por situação e agrupamento por
 * Tutor ou Pólo.
 */
class filter_tutor_polo extends moodleform {

    // adicao de elementos para a form
    function definition() {

        $mform = &$this->_form;

        $mform->addElement('header', 'filter_legend', 'Filtrar Estudantes');
        $mform->addElement('hidden', 'relatorio', $this->_customdata['relatorio']);

        //select box do estado da avaliacao
        $mform->addElement('select', 'situacao', "Situação:", array("Em Aberto", "Em Dia", "Expirado", "Fora do Prazo"));


        //radio button da filtragem do tipo de estudante, por tutor ou polo
        $radioarray = array();
        $radioarray[] = &$mform->createElement('radio', 'radiofilter', 'tutor', 'por Tutor', 'tutor');
        $radioarray[] = &$mform->createElement('radio', 'radiofilter', 'polo', 'por Polo', 'polo');
        $mform->addGroup($radioarray, '', 'Filtrar Estudantes: ', null);


        //div dos nomes dos tutores
        $mform->addElement('html', '<div class="relatorio-unasus filter div_list_show" id="div_tutor" >');
        $multiple_tutor = &$mform->addElement('select', 'multiple_tutor', "Tutores", get_nomes_tutores());
        $multiple_tutor->setMultiple(true);
        $mform->addElement('html', '</div>');

        //div dos nomes dos polos
        $mform->addElement('html', '<div class="relatorio-unasus filter div_list_show" id="div_polo">');
        $multiple_polo = &$mform->addElement('select', 'multiple_polo', "Polos", get_nomes_polos());
        $multiple_polo->setMultiple(true);
        $mform->addElement('html', '</div>');

        //submit button, ainda falta alinhar ele
        $mform->addElement('submit', 'button_filtrar', 'Filtrar', array('class' => 'filter'));
    }

}

