<?php

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    'report/unasus:view_tutoria' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => array()
    ),

    'report/unasus:view_all' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => array()
    ),

    'report/unasus:view_orientacao' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => array()
    )

);


