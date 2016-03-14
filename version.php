<?php
defined('MOODLE_INTERNAL') || die;

$plugin->version = 2016031401; // The current plugin version (Date: YYYYMMDDXX)
$plugin->requires = 2014111006; // Requires this Moodle version (2.8.6)
$plugin->component = 'report_unasus'; // Full name of the plugin (used for diagnostics)
$plugin->dependencies = array(
    'local_tutores' => 2014091800,
    'local_ufsc' => 2014091800,
    'local_report_config' => 2016031401,
    'local_relationship' => 2015020100
);

$maturity = MATURITY_STABLE;             // This version's maturity level.
