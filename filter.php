<?php

//moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");

class filter_tutor_polo extends moodleform {

    // adicao de elementos para a form
    function definition() {
        global $CFG;

        $mform = & $this->_form;
        //javascrip para ocultar as divs tutor/polo
        $mform->addElement('html',
                '<script type="text/javascript">
                    function toggleDivs(val){
                        if(val == "tutor" || val === true)
                        {
                            document.getElementById("div_tutor").style.display = "none";
                            document.getElementById("div_polo").style.display = "block";
                        }
                        else if(val == "polo" || val === false){
                            document.getElementById("div_polo").style.display = "none";
                            document.getElementById("div_tutor").style.display = "block";
                        }
                    }
                 </script>');
                
        
        //verificar a necessidade de criar um fieldset antes do legend
        $mform->addElement('header','filter_legend', 'Filtrar Estudantes');
        
        //select box do estado da avaliacao
        $select_nota = & $mform->addElement('select', 'situacao', "Situação:", 
                                array("Em Aberto", "Em Dia", "Expirado", "Fora do Prazo"));
         
        
        //radio button da filtragem do tipo de estudante, por tutor ou polo
        $attributes = "";
        $radioarray = array();
        $radioarray[] = &MoodleQuickForm::createElement('radio', 'tutor_polo', 'tutor', 'por Tutor', "tutor", 
                            array('onclick'=> 'toggleDivs(!this.checked)'));
        $radioarray[] = &MoodleQuickForm::createElement('radio', 'tutor_polo', 'polo', 'por Polo', "polo", 
                            array('onclick'=> 'toggleDivs(this.checked)'));
        $mform->addGroup($radioarray, 'radio_tutor_polo', 'Filtrar Estudantes: ');
    
        
        //div dos nomes dos tutores
        $mform->addElement('html','<div class="div_tutor" id="div_tutor" >');
        $multiple_tutor = & $mform->addElement('select', 'multiple_tutor',"Tutores",get_nomes_tutores()); 
        $multiple_tutor->setMultiple(true);
        $mform->addElement('html','</div>');
        
        //div dos nomes dos polos
        $mform->addElement('html','<div class="div_polo" id="div_polo">');
        $multiple_tutor = & $mform->addElement('select', 'multiple_polo',"Polos", get_nomes_polos()); 
        $multiple_tutor->setMultiple(true);
        $mform->addElement('html','</div>');
        
        //submit button, ainda falta alinhar ele
        $mform->addElement('submit','button_filtrar','Filtrar',array('text_align'));
        
    }

}

