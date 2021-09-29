<?php

return [
    'columns' => [
        'eidvalue' => [
            'config' => [
                'type' => 'none',
            ],
        ],
        'attributes' => [
            'exclude' => true,
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_eidlogin_domain_model_attribute',
                'foreign_field' => 'eid',
                'foreign_sortby' => 'uid',
            ]
        ],
        'feuid' => [
            'config' => [
                'type' => 'none',
            ],
        ],
        'beuid' => [
            'config' => [
                'type' => 'none',
            ],
        ],
    ],
];
