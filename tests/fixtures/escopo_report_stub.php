<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Report double for the role scope tests.
 *
 * @package    report_unasus
 * @copyright  2026 UFSC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Minimal report double: what the role scope reads and writes.
 *
 * @package    report_unasus
 * @copyright  2026 UFSC
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_unasus_escopo_report_stub {
    // The names mirror the public properties of report_unasus_factory.
    // phpcs:disable moodle.NamingConventions.ValidVariableName.MemberNameUnderscore
    /** @var int[]|null Tutoring groups selected. */
    public $tutores_selecionados = null;
    /** @var int[]|null Orientation groups selected. */
    public $orientadores_selecionados = null;
    // phpcs:enable
    /** @var context Report context. */
    protected $context;
    /** @var int Class category id. */
    protected $categoriaturma;

    /**
     * Constructor.
     *
     * @param context $context Report context.
     * @param int $categoriaturma Class category id.
     */
    public function __construct($context, $categoriaturma) {
        $this->context = $context;
        $this->categoriaturma = $categoriaturma;
    }

    /**
     * Returns the report context.
     *
     * @return context
     */
    public function get_context() {
        return $this->context;
    }

    /**
     * Returns the class category id.
     *
     * @return int
     */
    public function get_categoria_turma_ufsc() {
        return $this->categoriaturma;
    }
}
