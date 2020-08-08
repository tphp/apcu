<?php

return [
	'type' => 'sql',
	'config' => [
		'table' => 'help',
        'conn' => 'user',
//        'field' => [
//            'id',
//            'username',
//            'status',
//        ],
		'order' =>[
            'sort' => 'asc',
		]
	],
    'layout' => false
];