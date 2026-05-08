<?php

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/report/unasus/locallib.php');
require_once($CFG->dirroot . '/report/unasus/factory.php');
require_once($CFG->dirroot . '/report/unasus/reports/report_acesso_tutor.php');

/**
 * Cobre helpers puros do factory.
 *
 * - report_unasus_date_interval_is_valid (locallib.php)
 * - report_acesso_tutor::get_interval_boundaries / get_days_interval
 *
 * @group report_unasus
 */
class unasus_factory_helpers_testcase extends advanced_testcase {

    // -----------------------------------------------------------------------
    // report_unasus_date_interval_is_valid (locallib.php)
    // -----------------------------------------------------------------------

    public function test_date_interval_normal_range_valid() {
        $this->assertTrue(report_unasus_date_interval_is_valid('01/01/2024', '31/01/2024'));
    }

    public function test_date_interval_inverse_range_invalid() {
        // data_fim < data_inicio → invert == 1 → invalid
        $this->assertFalse(report_unasus_date_interval_is_valid('31/01/2024', '01/01/2024'));
    }

    public function test_date_interval_invalid_format_returns_false() {
        // Formato esperado é d/m/Y; outros formatos não passam pelo report_unasus_date_is_valid
        $this->assertFalse(report_unasus_date_interval_is_valid('2024-01-01', '2024-01-31'));
        $this->assertFalse(report_unasus_date_interval_is_valid('01-01-2024', '31-01-2024'));
        // Data inexistente
        $this->assertFalse(report_unasus_date_interval_is_valid('31/02/2024', '15/03/2024'));
    }

    public function test_date_interval_empty_returns_false() {
        $this->assertFalse(report_unasus_date_interval_is_valid('', ''));
        $this->assertFalse(report_unasus_date_interval_is_valid(null, null));
        $this->assertFalse(report_unasus_date_interval_is_valid('01/01/2024', null));
        $this->assertFalse(report_unasus_date_interval_is_valid(null, '31/01/2024'));
    }

    public function test_date_interval_same_day_valid() {
        // start == end → invert == 0 → valid (intervalo de um único dia)
        $this->assertTrue(report_unasus_date_interval_is_valid('15/06/2024', '15/06/2024'));
    }

    // -----------------------------------------------------------------------
    // report_acesso_tutor::get_interval_boundaries / get_days_interval
    // (REVIEW_10136 — date edge cases)
    //
    // Os métodos sob teste são protected e a classe estende factory, cujo
    // construtor lê parâmetros HTTP e bate em DB. O stub abaixo herda o
    // comportamento mas pula o construtor pesado, definindo apenas as
    // datas necessárias e expondo os helpers como público.
    // -----------------------------------------------------------------------

    public function test_interval_boundaries_adds_one_day_to_data_fim() {
        $stub = new testable_acesso_tutor('10/01/2024', '15/01/2024');
        list($inicio, $fim) = $stub->expose_interval_boundaries();

        $this->assertEquals('2024-01-10', $inicio->format('Y-m-d'));
        // data_fim recebe +1 dia para virar limite aberto do DatePeriod [inicio, fim)
        $this->assertEquals('2024-01-16', $fim->format('Y-m-d'));
        // Hora normalizada para meia-noite (operador '!' em createFromFormat)
        $this->assertEquals('00:00:00', $inicio->format('H:i:s'));
        $this->assertEquals('00:00:00', $fim->format('H:i:s'));
    }

    public function test_interval_boundaries_dst_transition() {
        // Atravessa eventual transição DST (Brasil 2018: fim do horário de verão em
        // 17/18 fev). O incremento P1D do PHP é civil — adiciona 1 dia de calendário
        // independente de a duração real ser 23 ou 25 horas.
        $stub = new testable_acesso_tutor('17/02/2018', '18/02/2018');
        list($inicio, $fim) = $stub->expose_interval_boundaries();

        $this->assertEquals('2018-02-17', $inicio->format('Y-m-d'));
        $this->assertEquals('2018-02-19', $fim->format('Y-m-d'));
    }

    public function test_interval_boundaries_cross_year() {
        $stub = new testable_acesso_tutor('31/12/2023', '02/01/2024');
        list($inicio, $fim) = $stub->expose_interval_boundaries();

        $this->assertEquals('2023-12-31', $inicio->format('Y-m-d'));
        $this->assertEquals('2024-01-03', $fim->format('Y-m-d'));
    }

    public function test_get_days_interval_same_day() {
        // data_inicio == data_fim → DatePeriod[inicio, inicio+1) contém um único dia
        $stub = new testable_acesso_tutor('15/06/2024', '15/06/2024');
        $dias = $stub->expose_days_interval('d/m/Y');

        $this->assertCount(1, $dias);
        $this->assertEquals('15/06/2024', $dias[0]);
    }

    public function test_get_days_interval_full_range() {
        // Sanity check: cinco dias completos
        $stub = new testable_acesso_tutor('10/06/2024', '14/06/2024');
        $dias = $stub->expose_days_interval('d/m/Y');

        $this->assertCount(5, $dias);
        $this->assertEquals('10/06/2024', $dias[0]);
        $this->assertEquals('14/06/2024', $dias[4]);
    }
}

/**
 * Stub de teste — herda report_acesso_tutor para acessar métodos protegidos
 * sem disparar o construtor de report_unasus_factory (que requer DB e HTTP).
 */
class testable_acesso_tutor extends report_acesso_tutor {

    public function __construct($data_inicio, $data_fim) {
        // Intencionalmente NÃO chama parent::__construct — depende de DB e params HTTP.
        $this->data_inicio = $data_inicio;
        $this->data_fim = $data_fim;
    }

    public function expose_interval_boundaries() {
        return $this->get_interval_boundaries();
    }

    public function expose_days_interval($format) {
        return $this->get_days_interval($format);
    }
}
