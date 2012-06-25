<?php

function get_dados_dos_alunos() {
    $dados = array();
    $dados[] = array("Joaozinho", 
                    new Avaliacao(Avaliacao::AVALIADA, "6"), 
                    new Avaliacao(Avaliacao::NAO_ENTREGUE), 
                    new Avaliacao(Avaliacao::AVALIADA, "8"));
    $dados[] = array("Maria", 
                    new Avaliacao(Avaliacao::AVALIADA, "10"),
                    new Avaliacao(Avaliacao::NO_PRAZO),
                    new Avaliacao(Avaliacao::NO_PRAZO));
    $dados[] = array("Joana", 
                    new Avaliacao(Avaliacao::ATRASADA, 0, 1),
                    new Avaliacao(Avaliacao::ATRASADA, 0, 10),
                    new Avaliacao(Avaliacao::NO_PRAZO));
    return $dados;
}

function get_dados_tutor(){
    return new Tutor("joao");
}

function get_nomes_tutores(){
    return array("joao","maria","ana");
}

function get_nomes_polos(){
    return array("xoinville","plumenau","xapecó");
}

class Avaliacao{

    const NAO_ENTREGUE = 0;
    const ATRASADA = 1;
    const AVALIADA = 2;
    const NO_PRAZO = 3;
    

    var $tipo;
    var $nota;
    var $atraso;
    
    function __construct($tipo, $nota = 0, $atraso = 0) {
        
        $this->tipo = $tipo;
        $this->nota = $nota;
        $this->atraso = $atraso;
    }
    
    public function to_string(){
        switch ($this->tipo) {
            case Avaliacao::NAO_ENTREGUE:
                return "Atividade não Entregue";
                break;
            case Avaliacao::ATRASADA:
                return $this->atraso . " Dias";
                break;
            case Avaliacao::AVALIADA:
                return $this->nota;
                break;
            case Avaliacao::NO_PRAZO:
                return "No prazo";
                break;
        }
    }
    
    public function get_css_class(){
        switch ($this->tipo) {
            case Avaliacao::NAO_ENTREGUE:
                return "trabalho_nao_entregue";
            case Avaliacao::ATRASADA:
                return ($this->atraso > 2) ? "alto_atraso" : "baixo_atraso"; 
            case Avaliacao::NO_PRAZO:
                return "no_prazo";
            default:
                return"";
        }

    }

}

class Tutor{
    var $nome;
    
    function __construct($nome) {
        $this->nome = $nome;
    }
    
    function to_string(){
        return $this->nome;
    }

}
