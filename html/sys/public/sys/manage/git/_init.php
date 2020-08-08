<?php

return function (){
    $api_url = base_path();
    if(!is_dir($api_url."/.git")){
        exit("未生成GIT版本控制！");
    }
    $type = "";
    $is_post = false;
    if($this->isPost()){
        $is_post = true;
        $type = $_POST['type'];
        if(!in_array($type, ['pull', 'push', 'reset'])){
            $type = "";
        }
    }

    if(empty($type) || $type == 'status') {
        exec("cd {$api_url} && git status", $list);
    }elseif($type == 'pull'){
        exec("cd {$api_url} && git pull", $list);
    }
    $this->setView("list", $list);
    $this->setView("is_post", $is_post);
};