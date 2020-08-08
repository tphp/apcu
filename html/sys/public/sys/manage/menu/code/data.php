<?php
$conn = $GLOBALS['DOMAIN_CONFIG']['conn'];
$rconn = 'user';
return [
	'type' => 'sqlfind',
	'config' => [
		'table' => $conn.'_menu',
        'conn' => $rconn,
        'field' => [
            'id',
            'name',
            'url',
            'type',
        ],
        'where' => ['id', '=', $_GET['id']]
	],
    'layout' => 'public/handle'
];