<?php
if(count($_POST) > 0){
    return [
        'layout' => false,
        'tpl_delete' => true
    ];
}else {
    return [
        'layout' => 'public/layer',
        'tpl_delete' => true
    ];
}