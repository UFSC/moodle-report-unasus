<?php

/**
 * Geração de dados dos tutores e seus respectivos alunos.
 *
 * @return array Array[tutores][aluno][unasus_data]
 */
function get_dados_dos_alunos() {
    $dados = array();

    for ($x = 1; $x <= 5; $x++) {
        $tutor = "Tutor Beltrano de Tal {$x}";
        $alunos = array();
        for ($i = 1; $i <= 30; $i++) {
            $alunos[] = array("Fulano de Tal {$i}",
                avaliacao_aleatoria(),
                avaliacao_aleatoria(),
                avaliacao_aleatoria(true));
        }
        $dados[$tutor] = $alunos;
    }

    return $dados;
}

/**
 * Utilizado para gerar uma avalização aleatórioa para finalidade de testes.
 * @return Avaliacao avaliacao aleatória para os relatórios
 */
function avaliacao_aleatoria($no_prazo = false) {
    $random = rand(0, 100);

    if ($random <= 65) { // Avaliada
        return new dado_atividade_vs_nota(dado_atividade_vs_nota::ATIVIDADE_AVALIADA, rand(0, 10));
    } elseif ($random > 65 && $random <= 85) { // Avaliação atrasada
        return new dado_atividade_vs_nota(dado_atividade_vs_nota::CORRECAO_ATRASADA, null, rand(0, 20));
    } elseif ($random > 85) { // Não entregue
        return $no_prazo ? new dado_atividade_vs_nota(dado_atividade_vs_nota::ATIVIDADE_NO_PRAZO_ENTREGA) : 
                            new dado_atividade_vs_nota(dado_atividade_vs_nota::ATIVIDADE_NAO_ENTREGUE);
    }
}

/**
 * Geração de dados dos tutores e seus respectivos alunos.
 *
 * @return array Array[tutores][aluno][unasus_data]
 */
function get_dados_entrega_atividades() {
    $dados = array();

    for ($x = 1; $x <= 5; $x++) {
        $tutor = "Tutor Beltrano de Tal {$x}";
        $alunos = array();
        for ($i = 1; $i <= 30; $i++) {
            $alunos[] = array("Fulano de Tal {$i}",
                atividade_aleatoria(),
                atividade_aleatoria(),
                atividade_aleatoria());
        }
        $dados[$tutor] = $alunos;
    }

    return $dados;
}

function atividade_aleatoria() {
    $random = rand(0, 100);

    if ($random <= 65) { // Avaliada
        return new dado_entrega_atividade(dado_entrega_atividade::ATIVIDADE_ENTREGUE_NO_PRAZO);
    } elseif ($random > 65 && $random <= 85) { // Avaliação atrasada
        return new dado_entrega_atividade(dado_entrega_atividade::ATIVIDADE_ENTREGUE_FORA_DO_PRAZO, rand(0, 20));
    } elseif ($random > 85) { // Não entregue
        return new dado_entrega_atividade(dado_entrega_atividade::ATIVIDADE_NAO_ENTREGUE);
    }
}



