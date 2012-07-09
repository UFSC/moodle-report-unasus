<?php

/**
 * Estrutura de dados de Pessoas (Tutores, Estudantes)
 * Auxilia renderização nos relatórios.
 */
abstract class pessoa {
    protected $name;

    function __construct($name) {
        $this->name = $name;
    }
}


/**
 * Representa um estudante nos relatórios
 */
class estudante extends pessoa {

    function __toString() {
        $link = new moodle_url('/user/profile.php', array('id' => 1)); // mock id - visitante
        return html_writer::link($link, $this->name);
    }
}

/**
 * Representa um tutor nos relatórios
 */
class tutor extends pessoa {
    function __toString() {
        return $this->name;
    }
}
?>
