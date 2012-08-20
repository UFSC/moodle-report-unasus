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
        return new dado_atividades_vs_notas(dado_atividades_vs_notas::ATIVIDADE_AVALIADA, rand(0, 10));
    } elseif ($random > 65 && $random <= 85) { // Avaliação atrasada
        return new dado_atividades_vs_notas(dado_atividades_vs_notas::CORRECAO_ATRASADA, null, rand(1, 20));
    } elseif ($random > 85) { // Não entregue
        return $no_prazo ? new dado_atividades_vs_notas(dado_atividades_vs_notas::ATIVIDADE_NO_PRAZO_ENTREGA) :
              new dado_atividades_vs_notas(dado_atividades_vs_notas::ATIVIDADE_NAO_ENTREGUE);
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
function get_dados_entrega_de_atividades() {
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
        return new dado_entrega_de_atividades(dado_entrega_de_atividades::ATIVIDADE_ENTREGUE_NO_PRAZO);
    } elseif ($random > 65 && $random <= 85) { // Avaliação atrasada
        return new dado_entrega_de_atividades(dado_entrega_de_atividades::ATIVIDADE_ENTREGUE_FORA_DO_PRAZO, rand(1, 20));
    } elseif ($random > 85) { // Não entregue
        return new dado_entrega_de_atividades(dado_entrega_de_atividades::ATIVIDADE_NAO_ENTREGUE);
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
        return new dado_acompanhamento_de_avaliacao(dado_acompanhamento_de_avaliacao::CORRECAO_NO_PRAZO, rand(0, 3));
    } elseif ($random > 65 && $random <= 85) {
        return new dado_acompanhamento_de_avaliacao(dado_acompanhamento_de_avaliacao::ATIVIDADE_NAO_ENTREGUE);
    } elseif ($random > 85) {
        return new dado_acompanhamento_de_avaliacao(dado_acompanhamento_de_avaliacao::CORRECAO_ATRASADA, rand(4, 20));
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
            new dado_avaliacao_em_atraso(rand(0, 100)),
            new dado_avaliacao_em_atraso(rand(0, 100)),
            new dado_avaliacao_em_atraso(rand(0, 100)),
            new dado_avaliacao_em_atraso(rand(0, 100)),
            new dado_avaliacao_em_atraso(rand(0, 100)),
            new dado_avaliacao_em_atraso(rand(0, 100)),
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
                new dado_atividade("Atividade " . rand(3, 5)),
                new dado_atividade("Atividade " . rand(3, 5)),
                new dado_atividade("Atividade " . rand(3, 5)));
        case 3:
            return array(
                new estudante("Fulano de tal {$i}"),
                new dado_modulo("Modulo " . rand(0, 1)),
                new dado_atividade("Atividade " . rand(0, 2)),
                new dado_atividade("Atividade " . rand(3, 5)),
                new dado_atividade("Atividade " . rand(3, 5)),
                new dado_modulo("Modulo " . rand(2, 3)),
                new dado_atividade("Atividade " . rand(0, 2)),
                new dado_atividade("Atividade " . rand(3, 5)));
    }
}

function get_dados_avaliacao_em_atraso() {
    $dados = array();

    for ($x = 1; $x <= 5; $x++) {
        $tutor = "Tutor Beltrano de Tal {$x}";
        $alunos = array();
        for ($i = 1; $i <= 30; $i++) {
            $alunos[] = array(new estudante("Fulano de Tal {$i}"),
                new dado_avaliacao_em_atraso(rand(0, 25)),
                new dado_avaliacao_em_atraso(rand(0, 25)),
                new dado_avaliacao_em_atraso(rand(0, 25)),
                new dado_avaliacao_em_atraso(rand(0, 25)),
                new dado_avaliacao_em_atraso(rand(0, 25)),
                new dado_avaliacao_em_atraso(rand(0, 25)),
                new dado_media(rand(0, 100)));
        }
        $dados[$tutor] = $alunos;
    }

    return $dados;
}

function get_dados_atividades_nota_atribuida() {
    $dados = array();

    for ($x = 1; $x <= 5; $x++) {
        $tutor = "Tutor Beltrano de Tal {$x}";
        $alunos = array();
        for ($i = 1; $i <= 30; $i++) {
            $alunos[] = array(new estudante("Fulano de Tal {$i}"),
                new dado_avaliacao_em_atraso(rand(75, 100)),
                new dado_avaliacao_em_atraso(rand(75, 100)),
                new dado_avaliacao_em_atraso(rand(75, 100)),
                new dado_avaliacao_em_atraso(rand(75, 100)),
                new dado_avaliacao_em_atraso(rand(75, 100)),
                new dado_avaliacao_em_atraso(rand(75, 100)),
                new dado_media(rand(0, 100)));
        }
        $dados[$tutor] = $alunos;
    }

    return $dados;
}

function get_dados_acesso_tutor() {
    $dados = array();

    $tutores = array();
    for ($i = 1; $i <= 30; $i++) {
        $tutores[] = array(new estudante("Tutor Fulano de Tal {$i}"),
            new dado_acesso_tutor(rand(0, 3) ? true : false),
            new dado_acesso_tutor(rand(0, 3) ? true : false),
            new dado_acesso_tutor(rand(0, 3) ? true : false),
            new dado_acesso_tutor(rand(0, 3) ? true : false),
            new dado_acesso_tutor(rand(0, 3) ? true : false),
            new dado_acesso_tutor(rand(0, 3) ? true : false),
            new dado_acesso_tutor(rand(0, 3) ? true : false));
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
            new dado_uso_sistema_tutor(rand(0, 20)),
            new dado_uso_sistema_tutor(rand(0, 20)),
            new dado_uso_sistema_tutor(rand(0, 20)),
            new dado_uso_sistema_tutor(rand(0, 20)),
            new dado_uso_sistema_tutor(rand(0, 20)),
            new dado_uso_sistema_tutor(rand(0, 20)),
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
                new dado_potenciais_evasoes(rand(0, 2)),
                new dado_potenciais_evasoes(rand(0, 2)),
                new dado_potenciais_evasoes(rand(0, 2)),
                new dado_potenciais_evasoes(rand(0, 2)),
                new dado_potenciais_evasoes(rand(0, 2)),
                new dado_potenciais_evasoes(rand(0, 2)),
                new dado_potenciais_evasoes(rand(0, 2)));
        }

        $dados[$tutor] = $estudantes;
    }

    return $dados;
}

function get_table_header_atividades_vs_notas() {
    $header = array();
    $header['Módulo 1'] = array('Atividade 1', 'Atividade 2', 'Atividade 3');
    $header['Módulo 2'] = array('Atividade 1', 'Atividade 2', 'Atividade 3', 'Atividade 4');
    return $header;
}

function get_table_header_entrega_de_atividades() {
    return get_table_header_atividades_vs_notas();
}

function get_table_header_acompanhamento_de_avaliacao() {
    return get_table_header_atividades_vs_notas();
}

function get_header_modulo_atividade_geral() {
    $header = array();
    $header['Módulo 1'] = array('Atividade 1', 'Atividade 2', 'Atividade 3');
    $header['Módulo 2'] = array('Atividade 1', 'Atividade 2', 'Atividade 3');
    $header[''] = array('Geral');
    return $header;
}

function get_table_header_potenciais_evasoes() {
    $modulos = array('Estudantes');
    for ($i = 1; $i <= 7; $i++) {
        $modulos[] = "Módulo ${i}";
    }
    return $modulos;
}

function get_table_header_avaliacao_em_atraso() {
    $header = array();
    $header['Módulo 1'] = array('Atividade 1', 'Atividade 2', 'Atividade 3');
    $header['Módulo 2'] = array('Atividade 1', 'Atividade 2', 'Atividade 3');
    $header[''] = array('Consolidado');
    return $header;
}

function get_table_header_atividades_nota_atribuida() {
    return get_table_header_avaliacao_em_atraso();
}

function get_table_header_acesso_tutor() {
    return array('Tutor', '15/06', '16/06', '17/06', '18/06', '19/06', '20/06', '21/06');
}

function get_table_header_uso_sistema_tutor() {
    return array('Tutor', 'Semana 1', 'Semana 2', 'Semana 3', 'Semana 4', 'Semana 5', 'Semana 6', 'Media', 'Total');
}

function get_header_estudante_sem_atividade_postada($size) {
    $content = array();
    for ($index = 0; $index < $size - 1; $index++) {
        $content[] = '';
    }
    $header['Atividades não resolvidas'] = $content;
    return $header;
}

function get_dados_grafico_atividades_vs_notas() {
    return array(
        'Tutor 1' => array(12, 5, 4, 2, 5),
        'Tutor 2' => array(7, 2, 2, 3, 0),
        'Tutor 3' => array(5, 6, 8, 0, 12),
        'MEDIA DOS TUTORES' => array(8, 4, 5, 1.5, 8)
    );
}

function get_dados_grafico_entrega_de_atividades() {
    return array(
        'Tutor 1' => array(12, 5, 4, 2),
        'Tutor 2' => array(7, 2, 2, 3),
        'Tutor 3' => array(5, 6, 8, 0),
        'MEDIA DOS TUTORES' => array(12, 12, 5, 1)
    );
}

function get_dados_grafico_acompanhamento_de_avaliacao() {
    return array(
        'Tutor 1' => array(5, 23, 4, 2),
        'Tutor 2' => array(2, 30, 2, 2, 3),
        'Tutor 3' => array(12, 6, 8, 0),
        'MEDIA DOS TUTORES' => array(9.5, 19.6, 4.6, 1.6)
    );
}

function get_dados_grafico_uso_sistema_tutor() {
    return array(
        'Tutor 1' => array('semana 1' => 5, 'semana 2' => 23, 'semana 3' => 4, 'semana 4' => 2),
        'Tutor 9' => array('semana 1' => 12, 'semana 2' => 6, 'semana 3' => 8, 'semana 4' => 0),
        'Tutor 10' => array('semana 1' => 2, 'semana 2' => 30, 'semana 3' => 2, 'semana 4' => 2),
        'Amanda' => array('semana 1' => 2, 'semana 2' => 30, 'semana 3' => 2, 'semana 4' => 2),
        'MEDIA D' => array('semana 1' => 9, 'semana 2' => 19, 'semana 3' => 4, 'semana 4' => 1)
    );
}