<?php

$conn = $GLOBALS['DOMAIN_CONFIG']['conn'];
$rconn = 'user';
return [
	'type' => 'sql',
	'config' => [
		'table' => $conn.'_role',
        'conn' => $rconn,
		'order' =>[
		    'sort' => 'asc',
            'id' => 'desc',
		]
	],
    'layout' => false
];