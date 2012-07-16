<?php

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    'report/unasus:view' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
        )
    )
);


