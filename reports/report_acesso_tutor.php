<?php

defined('MOODLE_INTERNAL') || die;

class report_acesso_tutor extends report_unasus_factory {

    protected function initialize() {
        $this->mostrar_filtro_grupo_tutoria = false;
        $this->mostrar_filtro_tutores = true;
        $this->mostrar_barra_filtragem = true;
        $this->mostrar_botoes_grafico = false;
        $this->mostrar_botoes_dot_chart = false;
        $this->mostrar_filtro_polos = false;
        $this->mostrar_filtro_cohorts = false;
        $this->mostrar_filtro_modulos = false;
        $this->mostrar_filtro_intervalo_tempo = true;
        $this->mostrar_aviso_intervalo_tempo = false;
        $this->mostrar_botao_exportar_csv = true;
    }

    public function render_report_default($renderer) {
        global $CFG;

        if (!$CFG->enablestats) {
            print_error('statistics_not_enabled_error', 'report_unasus');
        }

        echo $renderer->build_page();
    }

    public function render_report_table($renderer) {
        if ($this->datas_validas()) {
            $this->texto_cabecalho = 'Tutores';
            $this->mostrar_barra_filtragem = false;
            echo $renderer->build_report($this);
        }
        $this->mostrar_barra_filtragem = false;
        $this->mostrar_aviso_intervalo_tempo = true;
    }

    public function render_report_csv($name_report) {

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=relatorio ' . $name_report . '.csv');
        readfile('php://output');

        $dados = $this->get_dados();
        $header = $this->get_table_header();

        $fp = fopen('php://output', 'w');

        $data_header = array('Tutores');
        $first_line = array('');

        $months = array_map("Factory::eliminate_html", array_keys($header));
        $count = 0;

        foreach ($header as $h) {
            $n = count($h);
            $first_line[] = $months[$count];

            for ($i = 0; $i < $n; $i++) {
                if (isset($h[$i])) {
                    $element = $h[$i];
                    $data_header[] = $element;
                }
                if ($i < $n - 1) {
                    $first_line[] = '';
                }
            }
            $count++;
        }

        fputcsv($fp, $first_line);
        fputcsv($fp, $data_header);

        foreach ($dados as $dat) {
            foreach ($dat as $d) {
                $output = array_map("Factory::eliminate_html", $d);
                fputcsv($fp, $output);
            }
        }
        fclose($fp);
    }

    public function get_dados() {
        global $DB;
        $diff24Hours = new DateInterval('PT24H');

        $relationship_tutoria = local_tutores_grupos_tutoria::get_relationship_tutoria($this->get_categoria_turma_ufsc());
        $cohort_tutores = local_tutores_grupos_tutoria::get_relationship_cohort_tutores($relationship_tutoria->id);

        // Consulta
        $query = query_acesso_tutor($this->tutores_selecionados);

        $params = array('relationship_id' => $relationship_tutoria->id, 'relationship_cohort_id' => $cohort_tutores->id);
        $result = $DB->get_recordset_sql($query, $params);

        //Para cada linha da query ele cria um ['pessoa']=>['data_entrada1','data_entrada2]
        $group_array = new report_unasus_GroupArray();
        foreach ($result as $r) {
            $dia = $r->calendar_day;
            $mes = $r->calendar_month;
            $ano = $r->calendar_year;
            if ($dia < 10)
                $dia = '0' . $dia;
            if ($mes < 10)
                $mes = '0' . $mes;
            $group_array->add($r->userid, $dia . '/' . $mes . '/' . $ano);
        }
        $dados = $group_array->get_assoc();

        $data_inicio = date_create_from_format('d/m/Y', $this->data_inicio);
        $data_inicio->setTime(0,0,0);

        $data_fim = date_create_from_format('d/m/Y', $this->data_fim);
        $data_fim = $data_fim->add($diff24Hours);
        $data_fim->setTime(0,0,0);

        // Intervalo de dias no formato d/m/Y
        $dias_meses = report_unasus_get_time_interval($data_inicio, $data_fim, 'P1D', 'd/m/Y');

        //para cada resultado da busca ele verifica se esse dado bate no "calendario" criado com o
        //date interval acima
        $result = new report_unasus_GroupArray();
        foreach ($dados as $id => $datas) {
            foreach ($dias_meses as $dia) {
                (in_array($dia, $datas)) ?
                        $result->add($id, new report_unasus_dado_acesso_tutor_render(true)) :
                        $result->add($id, new report_unasus_dado_acesso_tutor_render(false));
            }
        }
        $result = $result->get_assoc();

        $nomes_tutores = local_tutores_grupos_tutoria::get_tutores($this->get_categoria_turma_ufsc());

        //para cada resultado que estava no formato [id]=>[dados_acesso]
        // ele transforma para [tutor,dado_acesso1,dado_acesso2]
        $retorno = array();
        foreach ($result as $id => $values) {
            $dados = array();
            $nome = (array_key_exists($id, $nomes_tutores)) ? $nomes_tutores[$id] : $id;
            array_push($dados, new report_unasus_person($nome, $id, $this->get_curso_moodle()));
            foreach ($values as $value) {
                array_push($dados, $value);
            }
            $retorno[] = $dados;
        }
        return array('Tutores' => $retorno);
    }

    public function get_table_header() {
        $diff24Hours = new DateInterval('PT24H');

        $data_inicio = date_create_from_format('d/m/Y', $this->data_inicio);
        $data_inicio_query = $data_inicio->format('d/m/Y');

        $data_fim = date_create_from_format('d/m/Y', $this->data_fim);
        $data_fim = $data_fim->add($diff24Hours);
        $data_fim_query = $data_fim->format('d/m/Y');

        return report_unasus_get_time_interval_com_meses($data_inicio_query, $data_fim_query, 'P1D', 'd/m/Y', 'd/m/y');
    }

}
