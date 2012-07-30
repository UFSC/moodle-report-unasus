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

        //multi-select dos módulos
        $multiple_modulos = &$mform->createElement('select', 'multiple_modulos', "Módulos", get_nomes_modulos());
        $multiple_modulos->setMultiple(true);

        //multi-select dos tutores
        $multiple_tutor = &$mform->createElement('select', 'multiple_tutor', "Tutores", get_nomes_tutores());
        $multiple_tutor->setMultiple(true);

        //multi-select dos polos
        $multiple_polo = &$mform->createElement('select', 'multiple_polo', "Polos", get_nomes_polos());
        $multiple_polo->setMultiple(true);

        //group para os multi-selects
        $lists_array = array();
        $lists_array[] = $multiple_modulos;
        $lists_array[] = $multiple_tutor;
        $lists_array[] = $multiple_polo;
        $mform->addGroup($lists_array, '', 'Filtrar por Módulo: ',
                                            array('Filtrar por Tutor: ','Filtrar por Polo: '));

        //submit button, ainda falta alinhar ele
        $mform->addElement('submit', 'button_filtrar', 'Filtrar', array('class' => 'filter'));
    }

}

