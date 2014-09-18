<?php
defined('MOODLE_INTERNAL') || die;

$plugin->version = 2014091800; // The current plugin version (Date: YYYYMMDDXX)
$plugin->requires = 2013111803.02; // Requires this Moodle version (2.6.3+)
$plugin->component = 'report_unasus'; // Full name of the plugin (used for diagnostics)
$plugin->dependencies = array('local_tutores' => 2014091800, 'local_ufsc' => 2014091800);
