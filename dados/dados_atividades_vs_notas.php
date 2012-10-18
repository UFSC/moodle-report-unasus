<?php

//
// Relatório de Atividades vs Notas Atribuídas
//

/**
 * Geração de dados dos tutores e seus respectivos alunos.
 *
 * @return array Array[tutores][aluno][unasus_data]
 */
function get_dados_atividades_vs_notas($modulos) {
    global $DB;

    // Consulta
    $query = " SELECT u.id as user_id,
                      CONCAT(u.firstname,' ',u.lastname) as user_name,
                      sub.timecreated as submission_date,
                      gr.timemodified,
                      gr.grade
                 FROM (
                      SELECT DISTINCT u.*
                        FROM {role_assignments} as ra
                        JOIN {role} as r
                          ON (r.id=ra.roleid)
                        JOIN {context} as c
                          ON (c.id=ra.contextid)
                        JOIN {user} as u
                          ON (u.id=ra.userid)
                       WHERE c.contextlevel=50
                      ) u
            LEFT JOIN {assign_submission} sub
            ON (u.id=sub.userid AND sub.assignment=:assignmentid)
            LEFT JOIN {assign_grades} gr
            ON (gr.assignment=sub.assignment AND gr.userid=u.id)
            ORDER BY u.firstname
    ";


    // Recupera dados auxiliares

    $modulos = get_atividades_modulos($modulos);
    $alunos = array(); // TODO recuperar alunos antes da consulta
    $group_dados = new GroupArray();


    // Executa Consulta
    foreach ($modulos as $modulo => $atividades) {

        foreach ($atividades as $atividade) {
            $result = $DB->get_recordset_sql($query, array('courseid' => $modulo, 'assignmentid' => $atividade->assign_id));

            foreach ($result as $r) {
                $alunos[$r->user_id] = $r->user_name;

                // Adiciona campos extras
                $r->courseid = $modulo;
                $r->assignid = $atividade->assign_id;
                $r->duedate = $atividade->duedate;

                // Agrupa os dados por usuário
                $group_dados->add($r->user_id, $r);
            }

        }

    }
    //transforma a consulta num array associativo
    $array_dados = $group_dados->get_assoc();


    //pega a hora atual para comparar se uma atividade esta atrasada ou nao
    $timenow = time();
    $estudantes = array();


    foreach ($array_dados as $id_aluno => $aluno) {
        $lista_atividades[] = new estudante($alunos[$id_aluno], $id_aluno);
        foreach ($aluno as $atividade) {

            $atraso = null;

            // Não entregou
            if (is_null($atividade->submission_date)) {
                if ((int)$atividade->duedate == 0) {
                    $tipo = dado_atividades_vs_notas::ATIVIDADE_SEM_PRAZO_ENTREGA;
                } elseif ($atividade->duedate > $timenow) {
                    $tipo = dado_atividades_vs_notas::ATIVIDADE_NO_PRAZO_ENTREGA;
                } else {
                    $tipo = dado_atividades_vs_notas::ATIVIDADE_NAO_ENTREGUE;
                }
            } // Entregou e ainda não foi avaliada
            elseif (is_null($atividade->grade) || (float)$atividade->grade < 0) {
                $tipo = dado_atividades_vs_notas::CORRECAO_ATRASADA;
                $submission_date = ((int)$atividade->submission_date == 0) ? $atividade->timemodified : $atividade->submission_date;
                $datadiff = date_diff(date_create(), get_datetime_from_unixtime($submission_date));
                $atraso = $datadiff->format("%a");
            } // Atividade entregue e avaliada
            elseif ((float)$atividade->grade > -1) {
                $tipo = dado_atividades_vs_notas::ATIVIDADE_AVALIADA;
            } else {
                print_error('unmatched_condition', 'report_unasus');
            }

            $lista_atividades[] = new dado_atividades_vs_notas($tipo, $atividade->assignid, $atividade->grade, $atraso);
        }
        $estudantes[] = $lista_atividades;
        $lista_atividades = null;
    }

    return (array('Tutor' => $estudantes));
}

/**
 *  Cabeçalho de duas linhas para os relatórios
 *  Primeira linha módulo1, modulo2
 *  Segunda linha ativ1_mod1, ativ2_mod1, ativ1_mod2, ativ2_mod2
 *
 * @param array $modulos
 * @return type
 */
function get_table_header_atividades_vs_notas($modulos = array()) {
    $atividades_modulos = query_atividades_modulos($modulos);

    $group = new GroupArray();
    $modulos = array();
    $header = array();

    // Agrupa atividades por curso e cria um índice de cursos
    foreach ($atividades_modulos as $atividade) {
        $modulos[$atividade->course_id] = $atividade->course_name;
        $group->add($atividade->course_id, $atividade);
    }

    $group_assoc = $group->get_assoc();

    foreach ($group_assoc as $course_id => $atividades) {
        $course_url = new moodle_url('/course/view.php', array('id' => $course_id));
        $course_link = html_writer::link($course_url, $modulos[$course_id]);
        $dados = array();

        foreach ($atividades as $atividade) {
            $cm = get_coursemodule_from_instance('assign', $atividade->assign_id, $course_id, null, MUST_EXIST);

            $atividade_url = new moodle_url('/mod/assign/view.php', array('id' => $cm->id));
            $dados[] = html_writer::link($atividade_url, $atividade->assign_name);
        }
        $header[$course_link] = $dados;
    }

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


