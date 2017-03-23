<?php
$capabilities = array(
    'block/classmanager:manage' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => array(
            'teacher' => CAP_PROHIBIT,
            'editingteacher' => CAP_PROHIBIT,
            'manager' => CAP_ALLOW
        )
    ),
);
