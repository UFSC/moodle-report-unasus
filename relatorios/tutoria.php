<?php

defined('MOODLE_INTERNAL') || die;

class tutoria {

    /**
     * Listagem de estudantes que participam de um curso UFSC e estão relacionados a um tutor
     *
     * @param $curso_ufsc
     * @return array [id][fullname]
     */
    static function get_estudantes_curso_ufsc($curso_ufsc) {
        global $DB;

        $relationship = self::get_relationship_tutoria($curso_ufsc);
        $cohort_estudantes = self::get_relationship_cohort_estudantes($relationship->id);

        $sql = "SELECT DISTINCT u.id, CONCAT(firstname,' ',lastname) AS fullname
                  FROM {user} u
                  JOIN {relationship_members} rm
                    ON (rm.userid=u.id AND rm.relationshipcohortid=:cohort_id)
                  JOIN {relationship_groups} rg
                    ON (rg.relationshipid=:relationship_id AND rg.id=rm.relationshipgroupid)
              ORDER BY u.firstname";

        $params = array('relationship_id' => $relationship->id, 'cohort_id' => $cohort_estudantes->id);
        return $DB->get_records_sql_menu($sql, $params);
    }

    /**
     * Retorna lista de grupos de tutoria de um determinado curso ufsc
     *
     * @param string $curso_ufsc
     * @param array $tutores
     * @return array
     */
    static function get_grupos_tutoria($curso_ufsc, $tutores = null) {
        global $DB;
        $relationship = self::get_relationship_tutoria($curso_ufsc);

        if (is_null($tutores)) {
            $sql = "SELECT rg.*
                      FROM {relationship_groups} rg
                     WHERE rg.relationshipid = :relationshipid
                  GROUP BY rg.id
                  ORDER BY name";
            $params = array('relationshipid'=>$relationship->id);
        } else {
            $tutores_sql = int_array_to_sql($tutores);
            $cohort_tutores = self::get_relationship_cohort_tutores($relationship->id);

            $sql = "SELECT rg.*
                      FROM {relationship_groups} rg
                      JOIN {relationship_members} rm
                        ON (rg.id=rm.relationshipgroupid AND rm.relationshipcohortid=:cohort_id)
                     WHERE rg.relationshipid = :relationshipid
                       AND rm.userid IN ({$tutores_sql})
                  GROUP BY rg.id
                  ORDER BY name";
            $params = array('relationshipid'=>$relationship->id, 'cohort_id' => $cohort_tutores->id);
        }

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Retorna a string que é utilizada no agrupamento por grupos de tutoria
     *
     * O padrão é $nome_do_grupo - Tutor(es) responsaveis
     * @param $curso_ufsc
     * @param $id
     * @return string
     * @throws dml_read_exception
     */
    static function grupo_tutoria_to_string($curso_ufsc, $id) {
        global $DB;

        $relationship = self::get_relationship_tutoria($curso_ufsc);
        $cohort_tutores = self::get_relationship_cohort_tutores($relationship->id);

        $params = array('relationshipid' => $relationship->id, 'cohort_id' => $cohort_tutores->id, 'grupo_id' => $id);

        $sql = "SELECT rg.*
                  FROM {relationship_groups} rg
             LEFT JOIN {relationship_members} rm
                    ON (rg.id=rm.relationshipgroupid AND rm.relationshipcohortid=:cohort_id)
                 WHERE rg.relationshipid = :relationshipid
                   AND rg.id=:grupo_id
              GROUP BY rg.id
              ORDER BY name";

        $grupos_tutoria = $DB->get_records_sql($sql, $params);

        $sql = "SELECT u.id as user_id, CONCAT(u.firstname,' ',u.lastname) as fullname
                  FROM {relationship_groups} rg
             LEFT JOIN {relationship_members} rm
                    ON (rg.id=rm.relationshipgroupid AND rm.relationshipcohortid=:cohort_id)
             LEFT JOIN {user} u
                    ON (u.id=rm.userid)
                 WHERE rg.relationshipid = :relationshipid
                   AND rg.id=:grupo_id
              GROUP BY rg.id
              ORDER BY name";

        $tutores = $DB->get_records_sql($sql, $params);

        $string = '<strong>' . $grupos_tutoria[$id]->name . '</strong>';
        if (empty($tutores)) {
            return $string . " - Sem Tutor Responsável";
        } else {
            foreach ($tutores as $tutor) {
                $string.= ' - ' . $tutor->fullname . ' ';
            }
        }
        return $string;
    }

    /**
     * Retorna o relationship_cohort dos estudantes de um determinado relationship
     * @param $relationship_id
     * @return mixed
     */
    static function get_relationship_cohort_estudantes($relationship_id) {
        global $DB;

        $sql = "SELECT rc.*
              FROM {relationship_cohorts} rc
              JOIN {role} r
                ON (r.id=rc.roleid)
             WHERE relationshipid=:relationship_id
               AND r.shortname IN ('student')";

        $cohort = $DB->get_record_sql($sql, array('relationship_id' => $relationship_id));

        if (!$cohort) {
            print_error('relationship_cohort_estudantes_not_available_error', 'report_unasus', '', null, "Relationship: {$relationship_id}");
        }

        return $cohort;
    }

    /**
     * Retorna o relationship_cohort dos tutores de um determinado relationship
     * @param $relationship_id
     * @return mixed
     */
    static function get_relationship_cohort_tutores($relationship_id) {
        global $DB;

        $sql = "SELECT rc.*
              FROM {relationship_cohorts} rc
              JOIN {role} r
                ON (r.id=rc.roleid)
             WHERE relationshipid=:relationship_id
               AND r.shortname IN ('tutor')";

        $cohort = $DB->get_record_sql($sql, array('relationship_id' => $relationship_id));

        if (!$cohort) {
            print_error('relationship_cohort_tutores_not_available_error', 'report_unasus', '', null, "Relationship: {$relationship_id}");
        }

        return $cohort;
    }

    /**
     * Retorna o relationship que designa os grupos de tutoria de um determinado curso UFSC
     * @param $curso_ufsc
     * @return mixed
     */
    static function get_relationship_tutoria($curso_ufsc) {
        global $DB;

        $ufsc_category = get_category_from_curso_ufsc($curso_ufsc);

        $sql = "SELECT r.id, r.name as nome
              FROM {relationship} r
              JOIN (
                    SELECT ti.itemid as relationship_id
                      FROM {tag_instance} ti
                      JOIN {tag} t
                        ON (t.id=ti.tagid)
                     WHERE t.name='grupo_tutoria'
                   ) tr
                ON (r.id=tr.relationship_id)
              JOIN {context} ctx
                ON (ctx.id=r.contextid)
              JOIN {course_categories} cc
                ON (ctx.instanceid = cc.id AND (cc.path LIKE '/$ufsc_category/%' OR cc.path LIKE '/$ufsc_category'))";

        $relationship = $DB->get_record_sql($sql);

        if (!$relationship) {
            print_error('relationship_tutoria_not_available_error', 'report_unasus');
        }

        return $relationship;
    }

    /**
     * Dado que alimenta a lista do filtro tutores
     *
     * @param $curso_ufsc
     * @return array
     */
    static function get_tutores_curso_ufsc($curso_ufsc) {
        global $DB;

        $relationship = self::get_relationship_tutoria($curso_ufsc);
        $cohort_tutores = self::get_relationship_cohort_tutores($relationship->id);

        $sql = "SELECT DISTINCT u.id, CONCAT(firstname,' ',lastname) AS fullname
              FROM {user} u
              JOIN {relationship_members} rm
                ON (rm.userid=u.id AND rm.relationshipcohortid=:cohort_id)
              JOIN {relationship_groups} rg
                ON (rg.relationshipid=:relationship_id AND rg.id=rm.relationshipgroupid)
              ORDER BY u.firstname";

        $params = array('relationship_id' => $relationship->id, 'cohort_id' => $cohort_tutores->id);
        return $DB->get_records_sql_menu($sql, $params);
    }
}