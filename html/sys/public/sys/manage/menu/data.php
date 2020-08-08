<?php

$conn = $GLOBALS['DOMAIN_CONFIG']['conn'];
$rconn = 'user';
return [
	'type' => 'sql',
	'config' => [
		'table' => $conn.'_menu',
        'conn' => $rconn,
        'field' => [
            'id', 'parent_id',
            'name',
            'icon_view',
            'icon' => '图标代码',
            'url' => '模块链接',
            'sort' => '排序',
            'status',
            'create_time',
            'update_time'
        ],
		'order' =>[
			'sort' => 'asc',
            'id' => 'desc',
		]
	],
    'layout' => false
];
