<?php

defined('MOODLE_INTERNAL') || die;

class report_uso_sistema_tutor extends report_unasus_factory {

    protected function initialize() {
        $this->mostrar_filtro_grupo_tutoria = false;
        $this->mostrar_filtro_tutores = true;
        $this->mostrar_barra_filtragem = true;
        $this->mostrar_botoes_grafico = false;
        $this->mostrar_botoes_dot_chart = true;
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
            $this->texto_cabecalho = null;
            $this->mostrar_barra_filtragem = false;
            echo $renderer->build_report($this);
        }
        $this->mostrar_barra_filtragem = false;
        $this->mostrar_aviso_intervalo_tempo = true;
    }

    public function render_report_graph($renderer, $porcentagem) {
        if ($this->datas_validas()) {
            $this->mostrar_barra_filtragem = false;
            echo $renderer->build_dot_graph($this);
        }
        $this->mostrar_barra_filtragem = false;
        $this->mostrar_aviso_intervalo_tempo = true;
        echo $renderer->build_page();
    }

    //Exactly same function to 'report_acesso_tutor' - refatorar!
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

        $lista_tutores = local_tutores_grupos_tutoria::get_tutores($this->get_categoria_turma_ufsc());

        $query = query_uso_sistema_tutor();

        //Converte a string data pra um DateTime e depois pra Unixtime
        $data_inicio = date_create_from_format('d/m/Y', $this->data_inicio);
        $data_inicio_unix = strtotime($data_inicio->format('Y-m-d'));

        $data_fim = date_create_from_format('d/m/Y', $this->data_fim);
        $data_fim = $data_fim->add($diff24Hours);
        $data_fim->setTime(0,0,0);
        $data_fim_query = $data_fim->format('Y-m-d 00:00:00');

        //Query
        $dados = array();

        foreach ($lista_tutores as $id => $tutor) {
            if (is_null($this->tutores_selecionados) || in_array($id, $this->tutores_selecionados)) {
                $result = $DB->get_recordset_sql($query, array('userid' => $id, 'tempominimo' => $data_inicio_unix, 'tempomaximo' => $data_fim_query));
                foreach ($result as $r) {
                    $dados[$id][$r->dia] = $r;
                }
            }
        }

        // Intervalo de dias no formato d/m
        $intervalo_tempo = $data_fim->diff($data_inicio)->days;
        $dias_meses = report_unasus_get_time_interval($data_inicio, $data_fim, 'P1D', 'd/m/Y');

        //para cada resultado da busca ele verifica se esse dado bate no "calendario" criado com o
        //date interval acima
        $result = new report_unasus_GroupArray();
        foreach ($dados as $id_user => $datas) {
            //quanto tempo ele ficou logado
            $total_tempo = 0;

            foreach ($dias_meses as $dia) {
                if ((array_key_exists($dia, $dados[$id_user]))) {
                    $horas = (float) $dados[$id_user][$dia]->horas;
                    $result->add($id_user, new report_unasus_dado_uso_sistema_tutor_render($horas));
                    $total_tempo += $horas;
                } else {
                    $result->add($id_user, new report_unasus_dado_uso_sistema_tutor_render('0'));
                }
            }

            $result->add($id_user, new report_unasus_dado_uso_sistema_tutor_render(format_float($total_tempo / $intervalo_tempo, 1, '')));
            $result->add($id_user, new report_unasus_dado_uso_sistema_tutor_render($total_tempo));
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

    function get_table_header() {
        $diff24Hours = new DateInterval('PT24H');

        $data_inicio = date_create_from_format('d/m/Y', $this->data_inicio);
        $data_inicio_query = $data_inicio->format('d/m/Y');

        $data_fim = date_create_from_format('d/m/Y', $this->data_fim);
        $data_fim = $data_fim->add($diff24Hours);
        $data_fim_query = $data_fim->format('d/m/Y');

        $double_header = report_unasus_get_time_interval_com_meses($data_inicio_query, $data_fim_query, 'P1D', 'd/m/Y', 'd/m/y');
        $double_header[''] = array('Media');
        $double_header[' '] = array('Total');
        return $double_header;
    }

    function get_dados_grafico() {
        $dados = $this->get_dados();

        $diff24Hours = new DateInterval('PT24H');

        $data_inicio = date_create_from_format('d/m/Y', $this->data_inicio);
        $data_inicio->setTime(0,0,0);

        $data_fim = date_create_from_format('d/m/Y', $this->data_fim);
        $data_fim = $data_fim->add($diff24Hours);
        $data_fim->setTime(0,0,0);

        // Intervalo de dias no formato d/m
        $dias_meses = report_unasus_get_time_interval($data_inicio, $data_fim, 'P1D', 'd/m/Y');

        $dados_grafico = array();
        foreach ($dados['Tutores'] as $tutor) {
            $dados_tutor = array();
            $count_dias = 1;
            foreach ($dias_meses as $dia) {
                $dados_tutor[$dia] = $tutor[$count_dias]->__toString();
                $count_dias++;
            }
            $dados_grafico[$tutor[0]->get_name()] = $dados_tutor;
        }
        return $dados_grafico;
    }


}
