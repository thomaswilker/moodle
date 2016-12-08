<?php

$functions = array(
    'block_oua_help_tour_disable' => array( // External services description.
        'classname'    => 'block_oua_help_tour\external',
        'methodname'   => 'disable_help_tour',
        'classpath'    => '',
        'description'  => 'Set the help tour to never display again.',
        'type'         => 'write',
        'capabilities' => '',
         'ajax'        => true,
    ),
    'block_oua_help_tour_disable_for_session' => array( // External services description.
                                            'classname'    => 'block_oua_help_tour\external',
                                            'methodname'   => 'disable_help_tour_for_session',
                                            'classpath'    => '',
                                            'description'  => 'Set the help tour to not display again for this moodle session.',
                                            'type'         => 'write',
                                            'capabilities' => '',
                                            'ajax'        => true,
    ),
);
