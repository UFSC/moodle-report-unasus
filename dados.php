<?php

//
// Relatório de Atividades vs Notas Atribuídas
//

/**
 * Geração de dados dos tutores e seus respectivos alunos.
 *
 * @return array Array[tutores][aluno][unasus_data]
 */
function get_dados_atividades_vs_notas() {
    $dados = array();

    for ($x = 1; $x <= 5; $x++) {
        $tutor = "Tutor Beltrano de Tal {$x}";
        $alunos = array();
        for ($i = 1; $i <= 30; $i++) {
            $alunos[] = array(new estudante("Fulano de Tal {$i}"),
                avaliacao_aleatoria(),
                avaliacao_aleatoria(),
                avaliacao_aleatoria(),
                avaliacao_aleatoria(),
                avaliacao_aleatoria(true),
                avaliacao_aleatoria(true),
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
        return new dado_atividade_vs_nota(dado_atividade_vs_nota::CORRECAO_ATRASADA, null, rand(1, 20));
    } elseif ($random > 85) { // Não entregue
        return $no_prazo ? new dado_atividade_vs_nota(dado_atividade_vs_nota::ATIVIDADE_NO_PRAZO_ENTREGA) :
                new dado_atividade_vs_nota(dado_atividade_vs_nota::ATIVIDADE_NAO_ENTREGUE);
    }
}

//
// Relatório de Acompanhamento de Entrega de Atividades
//

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
            $alunos[] = array(new estudante("Fulano de Tal {$i}"),
                atividade_aleatoria(),
                atividade_aleatoria(),
                atividade_aleatoria(),
                atividade_aleatoria(),
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
        return new dado_entrega_atividade(dado_entrega_atividade::ATIVIDADE_ENTREGUE_FORA_DO_PRAZO, rand(1, 20));
    } elseif ($random > 85) { // Não entregue
        return new dado_entrega_atividade(dado_entrega_atividade::ATIVIDADE_NAO_ENTREGUE);
    }
}

/**
 * Geração de dados dos tutores e seus respectivos alunos.
 *
 * @return array Array[tutores][aluno][unasus_data]
 */
function get_dados_acompanhamento_de_avaliacao() {
    $dados = array();

    for ($x = 1; $x <= 5; $x++) {
        $tutor = "Tutor Beltrano de Tal {$x}";
        $alunos = array();
        for ($i = 1; $i <= 30; $i++) {
            $alunos[] = array(new estudante("Fulano de Tal {$i}"),
                avaliacao_atividade_aleatoria(),
                avaliacao_atividade_aleatoria(),
                avaliacao_atividade_aleatoria(),
                avaliacao_atividade_aleatoria(),
                avaliacao_atividade_aleatoria(),
                avaliacao_atividade_aleatoria(),
                avaliacao_atividade_aleatoria());
        }
        $dados[$tutor] = $alunos;
    }

    return $dados;
}

function avaliacao_atividade_aleatoria() {
    $random = rand(0, 100);

    if ($random <= 65) {
        return new dado_acompanhamento_avaliacao(dado_acompanhamento_avaliacao::CORRECAO_NO_PRAZO, rand(0, 3));
    } elseif ($random > 65 && $random <= 85) {
        return new dado_acompanhamento_avaliacao(dado_acompanhamento_avaliacao::ATIVIDADE_NAO_ENTREGUE);
    } elseif ($random > 85) {
        return new dado_acompanhamento_avaliacao(dado_acompanhamento_avaliacao::CORRECAO_ATRASADA, rand(4, 20));
    }
}

/**
 * Geração de dados dos tutores e seus respectivos alunos.
 *
 * @return array Array[tutores][aluno][unasus_data]
 */
function get_dados_atividades_nao_avaliadas() {
    $dados = array();

    $tutores = array();
    for ($i = 1; $i <= 30; $i++) {
        $tutores[] = array(new tutor("Tutor Beltrano de Tal {$i}"),
            new dado_atividades_nao_avaliadas(rand(0, 100)),
            new dado_atividades_nao_avaliadas(rand(0, 100)),
            new dado_atividades_nao_avaliadas(rand(0, 100)),
            new dado_atividades_nao_avaliadas(rand(0, 100)),
            new dado_atividades_nao_avaliadas(rand(0, 100)),
            new dado_atividades_nao_avaliadas(rand(0, 100)),
            new dado_media(rand(0, 100)));
    }
    $dados = $tutores;

    return $dados;
}

function get_dados_estudante_sem_atividade_postada() {
    $dados = array();

    for ($x = 1; $x <= 5; $x++) {
        $tutor = "Tutor Beltrano de Tal {$x}";
        $alunos = array();
        for ($i = 1; $i <= 30; $i++) {
            $alunos[] = atividade_nao_postada($i);
        }
        $dados[$tutor] = $alunos;
    }

    return $dados;
}

function atividade_nao_postada($i) {
    switch (rand(1, 3)) {
        case 1:
            return array(
                new estudante("Fulano de tal {$i}"),
                new dado_modulo("Modulo " . rand(0, 3)),
                new dado_atividade("Atividade " . rand(0, 4)));
        case 2:
            return array(
                new estudante("Fulano de tal {$i}"),
                new dado_modulo("Modulo " . rand(0, 3)),
                new dado_atividade("Atividade " . rand(0, 2)),
                new dado_atividade("Atividade " . rand(3, 5)));
        case 3:
            return array(
                new estudante("Fulano de tal {$i}"),
                new dado_modulo("Modulo " . rand(0, 1)),
                new dado_atividade("Atividade " . rand(0, 2)),
                new dado_atividade("Atividade " . rand(3, 5)),
                new dado_modulo("Modulo " . rand(2, 3)),
                new dado_atividade("Atividade " . rand(0, 2)));
    }
}

function get_dados_avaliacao_em_atraso_tutor() {
    return get_dados_atividades_nao_avaliadas();
}

function get_dados_atividades_nota_atribuida_tutor() {
    return get_dados_atividades_nao_avaliadas();
}

function get_dados_acesso_tutor() {
    $dados = array();

    $tutores = array();
    for ($i = 1; $i <= 30; $i++) {
        $tutores[] = array(new estudante("Tutor Fulano de Tal {$i}"),
            new dado_acesso(rand(0, 3) ? true : false),
            new dado_acesso(rand(0, 3) ? true : false),
            new dado_acesso(rand(0, 3) ? true : false),
            new dado_acesso(rand(0, 3) ? true : false),
            new dado_acesso(rand(0, 3) ? true : false),
            new dado_acesso(rand(0, 3) ? true : false),
            new dado_acesso(rand(0, 3) ? true : false));
    }
    $dados["Tutores"] = $tutores;


    return $dados;
}

/**
 * @TODO arrumar media
 */
function get_dados_uso_sistema_tutor() {
    $dados = array();

    $tutores = array();
    for ($i = 1; $i <= 30; $i++) {
        $media = new dado_media(rand(0, 20));

        $tutores[] = array(new estudante("Tutor Fulano de Tal {$i}"),
            new dado_tempo_acesso(rand(0, 20)),
            new dado_tempo_acesso(rand(0, 20)),
            new dado_tempo_acesso(rand(0, 20)),
            new dado_tempo_acesso(rand(0, 20)),
            new dado_tempo_acesso(rand(0, 20)),
            new dado_tempo_acesso(rand(0, 20)),
            $media->value(),
            new dado_somatorio(rand(0, 20) + rand(0, 20) + rand(0, 20) + rand(0, 20) + rand(0, 20) + rand(0, 20)));
    }
    $dados["Tutores"] = $tutores;


    return $dados;
}

function get_dados_potenciais_evasoes() {
    $dados = array();

    for ($x = 1; $x <= 5; $x++) {
        $tutor = "Tutor Beltrano de Tal {$x}";

        $estudantes = array();
        for ($i = 1; $i <= 30; $i++) {
            $media = new dado_media(rand(0, 20));

            $estudantes[] = array(new estudante("Fulano de Tal {$i}"),
                new dado_potencial_evasao(rand(0, 2)),
                new dado_potencial_evasao(rand(0, 2)),
                new dado_potencial_evasao(rand(0, 2)),
                new dado_potencial_evasao(rand(0, 2)),
                new dado_potencial_evasao(rand(0, 2)),
                new dado_potencial_evasao(rand(0, 2)),
                new dado_potencial_evasao(rand(0, 2)));
        }

        $dados[$tutor] = $estudantes;
    }

    return $dados;
}

function get_header_modulo_atividade() {
    $header = array();
    $header['Módulo 1'] = array('Atividade 1', 'Atividade 2', 'Atividade 3');
    $header['Módulo 2'] = array('Atividade 1', 'Atividade 2', 'Atividade 3', 'Atividade 4');
    return $header;
}

function get_header_modulo_atividade_geral() {
    $header = array();
    $header['Módulo 1'] = array('Atividade 1', 'Atividade 2', 'Atividade 3');
    $header['Módulo 2'] = array('Atividade 1', 'Atividade 2', 'Atividade 3');
    $header[''] = array('Geral');
    return $header;
}

function get_header_modulo_evasoes() {
    $modulos = array('Estudantes');
    for ($i = 1; $i <= 7; $i++) {
        $modulos[] = "Módulo ${i}";
    }
    return $modulos;
}

function get_header_modulo_atividade_consolidado() {
    $header = array();
    $header['Módulo 1'] = array('Atividade 1', 'Atividade 2', 'Atividade 3');
    $header['Módulo 2'] = array('Atividade 1', 'Atividade 2', 'Atividade 3');
    $header[''] = array('Consolidado');
    return $header;
}

function get_header_acesso_tutor() {
    return array('Tutor', '15/06', '16/06', '17/06', '18/06', '19/06', '20/06', '21/06');
}

function get_header_uso_sistema_tutor() {
    return array('Tutor', 'Semana 1', 'Semana 2', 'Semana 3', 'Semana 4', 'Semana 5', 'Semana 6', 'Media', 'Total');
}

function get_header_estudante_sem_atividade_postada() {
    $header = array();
    $header['Atividades não resolvidas'] = array('', '', '');
    return $header;
}
