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

    $array_dados = $group_dados->get_assoc();

    $estudantes = array();
    $timenow = time();

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

//
// Relatório de Acompanhamento de Entrega de Atividades
//

/**
 * Geração de dados dos tutores e seus respectivos alunos.
 *
 * @return array Array[tutores][aluno][unasus_data]
 */
function get_dados_entrega_de_atividades($modulos) {
    global $DB;

    // Consulta
    $query = " SELECT u.id as user_id,
                      CONCAT(u.firstname,' ',u.lastname) as user_name,
                      sub.timecreated as submission_date,
                      gr.timemodified,
                      gr.grade,
                      sub.status
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
            ON (gr.assignment=sub.assignment AND gr.userid=u.id AND sub.status LIKE 'submitted')
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

    $array_dados = $group_dados->get_assoc();

    $estudantes = array();

    foreach ($array_dados as $id_aluno => $aluno) {
        $lista_atividades[] = new estudante($alunos[$id_aluno], $id_aluno);

        foreach ($aluno as $atividade) {
            $atraso = null;

            // Não enviou a atividade
            if (is_null($atividade->submission_date)) {
                if ((int) $atividade->duedate == 0) {
                    // Não entregou e Atividade sem prazo de entrega
                    $tipo = dado_entrega_de_atividades::ATIVIDADE_SEM_PRAZO_ENTREGA;
                } else {
                    // Não entregou e fora do prazo
                    $tipo = dado_entrega_de_atividades::ATIVIDADE_NAO_ENTREGUE;
                }
            }
            // Entregou antes ou na data de entrega esperada
            elseif ((int) $atividade->submission_date <= (int) $atividade->duedate) {
                $tipo = dado_entrega_de_atividades::ATIVIDADE_ENTREGUE_NO_PRAZO;
            }
            // Entregou após a data esperada
            else {
                $tipo = dado_entrega_de_atividades::ATIVIDADE_ENTREGUE_FORA_DO_PRAZO;
                $submission_date = ((int) $atividade->submission_date == 0) ? $atividade->timemodified : $atividade->submission_date;
                $datadiff = date_diff(get_datetime_from_unixtime($submission_date), get_datetime_from_unixtime($atividade->duedate));
                $atraso = $datadiff->format("%a");
            }

            $lista_atividades[] = new dado_entrega_de_atividades($tipo, $atividade->assignid, $atraso);
        }
        $estudantes[] = $lista_atividades;
        $lista_atividades = null;
    }

    return(array('Tutor' => $estudantes));
}

function get_table_header_entrega_de_atividades($modulos) {
    return get_table_header_atividades_vs_notas($modulos);
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

//
// Relatório de Histórico de Atribuição de Notas
//

/**
 * Geração de dados dos tutores e seus respectivos alunos.
 *
 * @return array Array[tutores][aluno][unasus_data]
 */
function get_dados_historico_atribuicao_notas($modulos) {
    global $DB, $CFG;

    // Consulta
    $query = " SELECT u.id as user_id,
                      CONCAT(u.firstname,' ',u.lastname) as user_name,
                      sub.timecreated as submission_date,
                      sub.timemodified as submission_modified,
                      gr.timemodified as grade_modified,
                      gr.timecreated as grade_created,
                      gr.grade,
                      sub.status
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
            ON (gr.assignment=sub.assignment AND gr.userid=u.id AND sub.status LIKE 'submitted')
            ORDER BY u.firstname
            LIMIT 200
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

    $array_dados = $group_dados->get_assoc();

    $estudantes = array();
    $timenow = time();
    foreach ($array_dados as $id_aluno => $aluno) {
        $lista_atividades[] = new estudante($alunos[$id_aluno], $id_aluno);

        foreach ($aluno as $atividade) {
            $atraso = null;
            // Não enviou a atividade
            if (is_null($atividade->submission_date)) {
                $tipo = dado_historico_atribuicao_notas::ATIVIDADE_NAO_ENTREGUE;

            } //Atividade entregue e não avaliada
            elseif (is_null($atividade->grade) || $atividade->grade < 0) {

                $tipo = dado_historico_atribuicao_notas::ATIVIDADE_ENTREGUE_NAO_AVALIADA;
                $data_envio = ((int)$atividade->submission_date != 0) ? $atividade->submission_date : $atividade->submission_modified;
                $datadiff = date_diff(get_datetime_from_unixtime($timenow), get_datetime_from_unixtime($data_envio));
                $atraso = (int)$datadiff->format("%a");

            } //Atividade entregue e avalidada
            elseif ((int)$atividade->grade >= 0) {

                //quanto tempo desde a entrega até a correção
                $data_correcao = ((int)$atividade->grade_created != 0) ? $atividade->grade_created : $atividade->grade_modified;
                $data_envio = ((int)$atividade->submission_date != 0) ? $atividade->submission_date : $atividade->submission_modified;
                $datadiff = date_diff(get_datetime_from_unixtime($data_correcao), get_datetime_from_unixtime($data_envio));
                $atraso = (int)$datadiff->format("%a");

                //Correção no prazo esperado
                if ($atraso < $CFG->report_unasus_prazo_avaliacao) {
                    $tipo = dado_historico_atribuicao_notas::CORRECAO_NO_PRAZO;
                } //Correção com pouco atraso
                elseif ($atraso < $CFG->report_unasus_prazo_maximo_avaliacao) {
                    $tipo = dado_historico_atribuicao_notas::CORRECAO_POUCO_ATRASO;
                } //Correção com muito atraso
                else {
                    $tipo = dado_historico_atribuicao_notas::CORRECAO_MUITO_ATRASO;
                }


            } else {
                print_error('unmatched_condition', 'report_unasus');
            }

            $lista_atividades[] = new dado_historico_atribuicao_notas($tipo, $atividade->assignid, $atraso);
        }
        $estudantes[] = $lista_atividades;
        $lista_atividades = null;
    }

    return (array('Tutor' => $estudantes));
}

function get_table_header_historico_atribuicao_notas($modulos) {
    return get_table_header_atividades_vs_notas($modulos);
}

function get_dados_grafico_historico_atribuicao_notas() {
    $tutores = get_nomes_tutores();
    return array(
        $tutores[0] => array(5, 23, 4, 2),
        $tutores[1] => array(2, 30, 2, 2, 3),
        $tutores[2] => array(12, 6, 8, 0),
        'MEDIA DOS TUTORES' => array(9.5, 19.6, 4.6, 1.6)
    );
}

function avaliacao_atividade_aleatoria() {
    $random = rand(0, 100);

    if ($random <= 65) {
        return new dado_historico_atribuicao_notas(dado_historico_atribuicao_notas::CORRECAO_NO_PRAZO, rand(0, 3));
    } elseif ($random > 65 && $random <= 85) {
        return new dado_historico_atribuicao_notas(dado_historico_atribuicao_notas::ATIVIDADE_NAO_ENTREGUE);
    } elseif ($random > 85) {
        return new dado_historico_atribuicao_notas(dado_historico_atribuicao_notas::CORRECAO_ATRASADA, rand(4, 20));
    }
}

//
// Lista: Atividades Não Postadas
//

function get_dados_estudante_sem_atividade_postada() {
    global $DB;

    // Consulta
    $query = " SELECT u.id as user_id,
                      CONCAT(u.firstname,' ',u.lastname) as user_name,
                      sub.timecreated as submission_date,
                      gr.timemodified,
                      gr.grade,
                      sub.status
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
            ON (gr.assignment=sub.assignment AND gr.userid=u.id AND sub.status LIKE 'submitted')
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

    $array_dados = $group_dados->get_assoc();

    $estudantes = array();

    foreach ($array_dados as $id_aluno => $aluno) {
        $lista_atividades[] = new estudante($alunos[$id_aluno], $id_aluno);

        foreach ($aluno as $atividade) {
            $atraso = null;

            // Não enviou a atividade
            if (is_null($atividade->submission_date)) {
                if ((int) $atividade->duedate == 0) {
                    // Não entregou e Atividade sem prazo de entrega
                    $tipo = dado_entrega_de_atividades::ATIVIDADE_SEM_PRAZO_ENTREGA;
                } else {
                    // Não entregou e fora do prazo
                    $tipo = dado_entrega_de_atividades::ATIVIDADE_NAO_ENTREGUE;
                }
            }
            // Entregou antes ou na data de entrega esperada
            elseif ((int) $atividade->submission_date <= (int) $atividade->duedate) {
                $tipo = dado_entrega_de_atividades::ATIVIDADE_ENTREGUE_NO_PRAZO;
            }
            // Entregou após a data esperada
            else {
                $tipo = dado_entrega_de_atividades::ATIVIDADE_ENTREGUE_FORA_DO_PRAZO;
                $submission_date = ((int) $atividade->submission_date == 0) ? $atividade->timemodified : $atividade->submission_date;
                $datadiff = date_diff(get_datetime_from_unixtime($submission_date), get_datetime_from_unixtime($atividade->duedate));
                $atraso = $datadiff->format("%a");
            }

            $lista_atividades[] = new dado_entrega_de_atividades($tipo, $atividade->assignid, $atraso);
        }
        $estudantes[] = $lista_atividades;
        $lista_atividades = null;
    }

    return(array('Tutor' => $estudantes));
}

function get_header_estudante_sem_atividade_postada($size) {
    $content = array();
    for ($index = 0; $index < $size - 1; $index++) {
        $content[] = '';
    }
    $header['Atividades não resolvidas'] = $content;
    return $header;
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

//
// Lista: Atividades não Avaliadas
//

function get_dados_estudante_sem_atividade_avaliada() {
    return get_dados_estudante_sem_atividade_postada();
}

//
// Síntese: Avaliações em Atraso
//

/**
 * Geração de dados dos tutores e seus respectivos alunos.
 *
 * @return array Array[tutores][aluno][unasus_data]
 */
function get_dados_atividades_nao_avaliadas() {
    $dados = array();
    $list_tutores = get_tutores_menu();
    foreach ($list_tutores as $tutor_id => $tutor) {
        $dados[] = array(new tutor($tutor, $tutor_id),
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

//
// Síntese: atividades concluídas
//

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

function get_table_header_atividades_nota_atribuida() {
    return get_table_header_avaliacao_em_atraso();
}

//
// Uso do Sistema pelo Tutor (horas)
//

/**
 * @TODO arrumar media
 */
function get_dados_uso_sistema_tutor() {
    $lista_tutores = get_tutores_menu();
    $dados = array();
    $tutores = array();
    foreach ($lista_tutores as $tutor_id => $tutor) {
        $media = new dado_media(rand(0, 20));

        $tutores[] = array(new tutor($tutor, $tutor_id),
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

function get_table_header_uso_sistema_tutor() {
    return array('Tutor', 'Jun/Q4', 'Jul/Q1', 'Jul/Q2', 'Jul/Q3', 'Jul/Q4', 'Ago/Q1', 'Media', 'Total');
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

//
// Uso do Sistema pelo Tutor (acesso)
//

function get_dados_acesso_tutor() {
    $dados = array();
    $lista_tutores = get_tutores_menu();

    $tutores = array();
    foreach ($lista_tutores as $tutor_id => $tutor) {
        $tutores[] = array(new tutor($tutor, $tutor_id),
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

function get_table_header_acesso_tutor() {
    return array('Tutor', '15/06', '16/06', '17/06', '18/06', '19/06', '20/06', '21/06');
}

//
// Potenciais Evasões
//

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

//
// Outros ??
//

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

function get_table_header_avaliacao_em_atraso() {
    $header = array();
    $modulos = get_nomes_modulos();
    $header[$modulos[6]] = array('Atividade 1', 'Atividade 2', 'Atividade 3');
    $header[$modulos[7]] = array('Atividade 1', 'Atividade 2', 'Atividade 3');
    $header[''] = array('Consolidado');
    return $header;
}

function get_header_modulo_atividade_geral() {
    $header = array();
    $modulos = get_nomes_modulos();
    $header[$modulos[6]] = array('Atividade 1', 'Atividade 2', 'Atividade 3');
    $header[$modulos[7]] = array('Atividade 1', 'Atividade 2', 'Atividade 3');
    $header[''] = array('Geral');
    return $header;
}