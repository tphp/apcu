<?php

return [
	'type' => 'sql',
	'config' => [
		'table' => 'admin',
        'conn' => 'user',
        'field' => [
            'id',
            'username',
            'status',
        ],
        'where' => [
            ['id', '<>', '1']
        ],
		'order' =>[
            'id' => 'desc',
		]
	],
    'layout' => false
];