<?php

return [
    'field' => [
        //'id', 'parent_id',
        'id' => [
            'name' => 'ID',
            'width' => 20,
            'fixed' => true
        ],
        'icon_view' => [
            'name' => ' ',
            'width' => 16,
            'fixed' => true,
            'click' => [
                'url' => 'menu/icon',
                'key' => 'icon'
            ],
            'align' => 'right'
        ],
        "name" => [
            'name' => '菜单名称',
            "order"=>true,
            'edit' => true,
            'width' => 150,
            'title' => true,
            'fixed' => true,
        ],
        'icon' => [
            'name' => '图标代码',
            'edit' => true,
            'width' => 70,
            'fixed' => true
        ],
        'url' => [
            'name' => '模块链接',
            'width' => 40,
            'edit' => true
        ],
        'params' => [
            'name' => 'URL参数',
            'width' => 40,
            'edit' => true
        ],
        'type' => [
            'name' => '类型',
            'width' => 30,
            'fixed' => true,
            'type' => 'select',
            'list' => [
                'html' => '普通',
                'list' => '<span style="color:#F80">列表</span>',
                'handle' => '<span style="color:#08F">编辑</span>',
                'data' => '<span style="color:#907">数据</span>'
            ]
        ],
        'sort' => [
            'name' => '排序',
            'edit' => true,
            'width' => 50,
            'fixed' => true,
            'align' => 'center'
        ],
        'status' => [
            'name' => '是否显示',
            'status' => true,
            'width' => 48,
            'fixed' => true
        ],
    ],
    //编辑或增加
    'handle' => [
        'name',
        'icon',
        'url',
        'params',
        'type',
        'sort' => [
            'batch' => '批量排序', //批量操作(此处无效)
            'value' => 255
            //'batch_only' => '批量排序', //仅批量操作,
            //'verify' => 'required|phone'
        ],
        'status' => [
            'value' => '1',
            'batch' => "修改状态"
        ],
        'create_time',
        'update_time'
    ],
    'handleinfo' => [
        'width' => 800, //宽
        'height' => 480, //高
        //'ismax' => true //是否最大化
    ],
    'oper' => [
        'code' => [
            'name' => '代码',
            'url' => 'code',
            'key' => 'id',
            'ismax' => true,
        ],
//        'copy' => [
//            'name' => '复制',
//            'url' => 'copy',
//            'key' => 'id',
//            'ismax' => false,
//            'confirm' => true
//        ]
    ],
//    //'ispage' => true,
//    //'pagesize' => 5,
    'is' => [
        'checkbox' => true, //选择框
//        'numbers' => true, //序列
        'delete' => true, //删除
//        'deletes' => true, //选择删除
        'add' => true, //选择删除
//        'view' => true, //查看
//        'import' => true, //导入
//        'export' => true, //导出所选
//        'exports' => true, //导出更多数据，默认最大设置1000条数据
    ],
    //树状分层模块
    'tree' => [
        'parent' => 'parent_id', //父节点
        'child' => 'id', //子节点
        'value' => 0 //初始值
    ]
];