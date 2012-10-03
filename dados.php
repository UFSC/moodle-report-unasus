<?php

//
// Relatório de Atividades vs Notas Atribuídas
//

/**
 * Geração de dados dos tutores e seus respectivos alunos.
 *
 * @return array Array[tutores][aluno][unasus_data]
 */
function get_dados_atividades_vs_notas($modulos = array()) {
    global $DB;
    $query = "SELECT u.id as user_id,
                     CONCAT(u.firstname,' ',u.lastname) as user_name,
                     gg.finalgrade,
                     gi.courseid,
                     a.id as assign_id
                FROM {grade_grades} gg
                JOIN {grade_items} gi ON gi.id=gg.itemid
                JOIN {user} u ON u.id=gg.userid
                JOIN {assign} a ON a.course = gi.courseid
                JOIN {course} c ON a.course = c.id
               WHERE gi.itemtype = 'course' ";
    if($modulos){
        $string_modulos = int_array_to_sql($modulos);
        $query .= "AND c.id IN ({$string_modulos})";
    }
    $query .= "ORDER BY u.firstname";
    $atividades_alunos = $DB->get_recordset_sql($query);

    $group_array = new GroupArray();
    $aluno_id = array();
    foreach ($atividades_alunos as $atividade) {
        $aluno_id[$atividade->user_id] = $atividade->user_name;
        $group_array->add($atividade->user_id, array('finalgrade' => $atividade->finalgrade,
            'courseid' => $atividade->courseid,
            'assign_id' => $atividade->assign_id,));
    }
    $array_dados = $group_array->get_assoc();


    $estudantes = array();

    foreach ($array_dados as $id_aluno => $aluno) {
        $lista_atividades[] = new estudante($aluno_id[$id_aluno], $id_aluno);
        foreach ($aluno as $atividade) {
            $lista_atividades[] = new dado_atividades_vs_notas(
                        dado_atividades_vs_notas::ATIVIDADE_AVALIADA, $atividade['assign_id'], $atividade['finalgrade']);
        }
        $estudantes[] = $lista_atividades;
        $lista_atividades = null;
    }
    return(array('Joao' => $estudantes));
}

function get_table_header_atividades_vs_notas($modulos = array()) {
    global $DB;
    $query = "SELECT a.id as assign_id, a.name as assign_name, c.fullname as course_name, c.id as course_id
                    FROM {course} as c
                    JOIN {assign} as a
                      ON (c.id = a.course)";
    if (!empty($modulos)) {
        $string_modulos = int_array_to_sql($modulos);
        $query .= "WHERE c.id IN ({$string_modulos})";
    }
    $query .= "ORDER BY c.id";

    $atividades_modulos = $DB->get_recordset_sql($query);

    $group = new GroupArray();
    foreach ($atividades_modulos as $atividade) {
        $group->add($atividade->course_name, $atividade->assign_name);
    }
    return $group->get_assoc();
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
    $tutores = get_nomes_tutores();
    $estudantes = get_nomes_estudantes();

    for ($x = 0; $x <= 5; $x++) {
        $tutor = $tutores[$x];
        $alunos = array();
        for ($i = 0; $i < 30; $i++) {
            $estudante = $estudantes->current();
            $estudantes->next();
            $alunos[] = array(new estudante($estudante->fullname, $estudante->id),
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

    $estudantes->close();
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
    $tutores = get_nomes_tutores();
    $estudantes = get_nomes_estudantes();

    for ($x = 0; $x <= 5; $x++) {
        $tutor = $tutores[$x];
        $alunos = array();
        for ($i = 0; $i < 30; $i++) {
            $estudante = $estudantes->current();
            $estudantes->next();
            $alunos[] = array(new estudante($estudante->fullname, $estudante->id),
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

    $estudantes->close();
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
    $list_tutores = get_nomes_tutores();
    foreach ($list_tutores as $tutor) {
        $dados[] = array(new tutor($tutor->fullname, $tutor->id),
            new dado_avaliacao_em_atraso(rand(0, 100)),
            new dado_avaliacao_em_atraso(rand(0, 100)),
            new dado_avaliacao_em_atraso(rand(0, 100)),
            new dado_avaliacao_em_atraso(rand(0, 100)),
            new dado_avaliacao_em_atraso(rand(0, 100)),
            new dado_avaliacao_em_atraso(rand(0, 100)),
            new dado_media(rand(0, 100)));
    }

    return $dados;
}

function get_dados_estudante_sem_atividade_avaliada() {
    return get_dados_estudante_sem_atividade_postada();
}

function get_dados_estudante_sem_atividade_postada() {
    $dados = array();

    $tutores = get_nomes_tutores();
    $estudantes = get_nomes_estudantes();
    $modulos = get_nomes_modulos();

    for ($x = 0; $x <= 3; $x++) {
        $tutor = $tutores[$x];
        $alunos = array();
        for ($i = 0; $i < 40; $i++) {
            $estudante = $estudantes->current();
            $estudantes->next();
            $alunos[] = atividade_nao_postada(new estudante($estudante->fullname, $estudante->id), $modulos);
        }
        $dados[$tutor] = $alunos;
    }

    $estudantes->close();
    return $dados;
}

function atividade_nao_postada($estudante, $modulos) {
    switch (rand(1, 3)) {
        case 1:
            return array(
                $estudante,
                new dado_modulo($modulos[rand(5, 8)]),
                new dado_atividade("Atividade " . rand(1, 4)));
        case 2:
            return array(
                $estudante,
                new dado_modulo($modulos[rand(5, 8)]),
                new dado_atividade("Atividade " . rand(1, 2)),
                new dado_atividade("Atividade " . rand(3, 5)),
                new dado_atividade("Atividade " . rand(3, 5)),
                new dado_atividade("Atividade " . rand(3, 5)));
        case 3:
            return array(
                $estudante,
                new dado_modulo($modulos[rand(5, 6)]),
                new dado_atividade("Atividade " . rand(1, 2)),
                new dado_atividade("Atividade " . rand(3, 5)),
                new dado_atividade("Atividade " . rand(3, 5)),
                new dado_modulo($modulos[rand(7, 8)]),
                new dado_atividade("Atividade " . rand(1, 2)),
                new dado_atividade("Atividade " . rand(3, 5)));
    }
}

function get_dados_avaliacao_em_atraso() {
    $dados = array();
    $tutores = get_nomes_tutores();
    $estudantes = get_nomes_estudantes();

    for ($x = 0; $x <= 5; $x++) {
        $tutor = $tutores[$x];
        $alunos = array();
        for ($i = 0; $i < 30; $i++) {
            $estudante = $estudantes->current();
            $estudantes->next();
            $alunos[] = array(new estudante($estudante->fullname, $estudante->id),
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

    $estudantes->close();
    return $dados;
}

function get_dados_atividades_nota_atribuida() {
    $dados = array();
    $estudantes = get_nomes_estudantes();
    $tutores = get_nomes_tutores();

    for ($x = 0; $x <= 5; $x++) {
        $tutor = $tutores[$x];
        $alunos = array();
        for ($i = 1; $i <= 30; $i++) {
            $estudante = $estudantes->current();
            $estudantes->next();
            $alunos[] = array(new estudante($estudante->fullname, $estudante->id),
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

    $estudantes->close();
    return $dados;
}

function get_dados_acesso_tutor() {
    $dados = array();
    $lista_tutores = get_nomes_tutores();

    $tutores = array();
    foreach ($lista_tutores as $tutor) {
        $tutores[] = array(new tutor($tutor->fullname, $tutor->id),
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
    $lista_tutores = get_nomes_tutores();
    $dados = array();
    $tutores = array();
    foreach ($lista_tutores as $tutor) {
        $media = new dado_media(rand(0, 20));

        $tutores[] = array(new tutor($tutor->fullname, $tutor->id),
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

    $lista_tutores->close();
    return $dados;
}

function get_dados_potenciais_evasoes() {
    $dados = array();
    $nome_tutores = get_nomes_tutores();
    $lista_estudantes = get_nomes_estudantes();
    for ($x = 0; $x <= 5; $x++) {
        $tutor = $nome_tutores[$x];

        $estudantes = array();
        for ($i = 0; $i < 30; $i++) {
            $estudante = $lista_estudantes->current();
            $lista_estudantes->next();
            $estudantes[] = array(new estudante($estudante->fullname, $estudante->id),
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

function get_table_header_entrega_de_atividades() {
    return get_table_header_atividades_vs_notas();
}

function get_table_header_acompanhamento_de_avaliacao() {
    return get_table_header_atividades_vs_notas();
}

function get_header_modulo_atividade_geral() {
    $header = array();
    $modulos = get_nomes_modulos();
    $header[$modulos[6]] = array('Atividade 1', 'Atividade 2', 'Atividade 3');
    $header[$modulos[7]] = array('Atividade 1', 'Atividade 2', 'Atividade 3');
    $header[''] = array('Geral');
    return $header;
}

function get_table_header_potenciais_evasoes() {
    $lista_modulos = get_nomes_modulos();
    $modulos = array('Estudantes');
    $modulos[] = $lista_modulos[4];
    $modulos[] = $lista_modulos[5];
    $modulos[] = $lista_modulos[6];
    $modulos[] = $lista_modulos[7];
    $modulos[] = $lista_modulos[8];
    $modulos[] = $lista_modulos[9];
    $modulos[] = $lista_modulos[10];
    return $modulos;
}

function get_table_header_avaliacao_em_atraso() {
    $header = array();
    $modulos = get_nomes_modulos();
    $header[$modulos[6]] = array('Atividade 1', 'Atividade 2', 'Atividade 3');
    $header[$modulos[7]] = array('Atividade 1', 'Atividade 2', 'Atividade 3');
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
    return array('Tutor', 'Jun/Q4', 'Jul/Q1', 'Jul/Q2', 'Jul/Q3', 'Jul/Q4', 'Ago/Q1', 'Media', 'Total');
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
    $tutores = get_nomes_tutores();
    return array(
        $tutores[0] => array(12, 5, 4, 2, 5),
        $tutores[1] => array(7, 2, 2, 3, 0),
        $tutores[2] => array(5, 6, 8, 0, 12),
        'MEDIA DOS TUTORES' => array(8, 4, 5, 1.5, 8)
    );
}

function get_dados_grafico_entrega_de_atividades() {
    $tutores = get_nomes_tutores();
    return array(
        $tutores[0] => array(12, 5, 4, 2),
        $tutores[1] => array(7, 2, 2, 3),
        $tutores[2] => array(5, 6, 8, 0),
        'MEDIA DOS TUTORES' => array(12, 12, 5, 1)
    );
}

function get_dados_grafico_acompanhamento_de_avaliacao() {
    $tutores = get_nomes_tutores();
    return array(
        $tutores[0] => array(5, 23, 4, 2),
        $tutores[1] => array(2, 30, 2, 2, 3),
        $tutores[2] => array(12, 6, 8, 0),
        'MEDIA DOS TUTORES' => array(9.5, 19.6, 4.6, 1.6)
    );
}

function get_dados_grafico_uso_sistema_tutor() {
    $tutores = get_nomes_tutores();
    return array(
        $tutores[0] => array('Jun/Q4' => 23, 'Jul/Q1' => 23, 'Jul/Q2' => 4, 'Jul/Q3' => 8, 'Jul/Q4' => 12, 'Ago/Q1' => 12),
        $tutores[1] => array('Jun/Q4' => 6, 'Jul/Q1' => 12, 'Jul/Q2' => 19, 'Jul/Q3' => 15, 'Jul/Q4' => 1, 'Ago/Q1' => 1),
        $tutores[2] => array('Jun/Q4' => 9, 'Jul/Q1' => 1, 'Jul/Q2' => 7, 'Jul/Q3' => 22, 'Jul/Q4' => 5, 'Ago/Q1' => 20),
        $tutores[3] => array('Jun/Q4' => 12, 'Jul/Q1' => 1, 'Jul/Q2' => 7, 'Jul/Q3' => 1, 'Jul/Q4' => 8, 'Ago/Q1' => 6)
    );
}