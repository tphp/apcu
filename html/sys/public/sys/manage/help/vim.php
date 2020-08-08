<?php
return [
    'field' => [
        'id' => [
            'name' => 'ID',
            'width' => 20,
            'fixed' => true
        ],
        "name" => [
            'name' => '文档名称',
            "order"=>true,
            'edit' => true,
            'title' => true,
            'search' => true
        ],
        'remark' => [
            'name' => '文档说明',
            'width' => 20
        ],
        'sort' => [
            'name' => '排序',
            'width' => 50,
            'fixed' => true,
            'edit' => true,
        ],
        'status' => [
            'name' => '状态',
            'status' => true,
            'width' => 45,
            'fixed' => true,
            'search' => true
        ],
    ],
    //编辑或增加
    'handle' => [
        'name',
        'remark' => ['type' => 'markdown', 'height' => "600"],
        'title' => ['group' => 'SEO', 'json' => 'seo'],
        'keywords' => ['group' => 'SEO', 'json' => 'seo'],
        'description' => ['group' => 'SEO', 'json' => 'seo'],
        'sort' => ['group' => 'SEO', 'value' => 255],
        'status' => ['group' => 'SEO', 'value' => 1],
        'create_time',
        'update_time'
    ],

    'handleinfo' => [
        'width' => 800, //宽
        'height' => 500, //高
        'ismax' => true
    ],
    'is' => [
        'delete' => true, //删除
        'add' => true,
    ],
    //树状分层模块
    'tree' => [
        'parent' => 'parent_id', //父节点
        'child' => 'id', //子节点
        'value' => 0 //初始值
    ]
];