<?php
return function(){
    $menu_ids = $this->userinfo['menu_ids'];
    $menu_ids_gt = [];
    $menu_ids_lt = [];
    foreach ($menu_ids as $val){
        if($val >= 0){
            $menu_ids_gt[] = $val;
        }else{
            $menu_ids_lt[] = $val;
        }
    }
    try{
        $db = $this->db($GLOBALS['DOMAIN_CONFIG']['conn']."_menu", "user")->where("status", "=", "1")->select("id", "parent_id", "name", "icon", "url", "params", "type", "sort", "description")->orderBy('sort', 'asc')->orderBy('id', 'desc');
        if(!empty($menu_ids_gt)){
            $db->whereIn('id', $menu_ids_gt);
        }
        $menulist = $db->get();
    }catch (Exception $e){
        $this->flushCache();
        redirect("/sys/user/login")->send();
//        EXITJSON([
//            "code" => 200,
//            "info" => "响应成功",
//            "data" => [
//                [
//                    "id" => -1,
//                    "parent_id" => 0,
//                    "name" => "菜单加载错误，刷新页面后重新生成菜单！",
//                ]
//            ]
//        ]);
    }

    $menuarr = json_decode(json_encode($menulist, true), true);

    if(empty($menu_ids) || !empty($menu_ids_lt)) {
        $rdata = tpl("add.data");
        if (!empty($rdata) && is_array($rdata)) {
            if(empty($menu_ids)){
                foreach ($rdata as $val) {
                    $menuarr[] = $val;
                }
            }elseif(!empty($menu_ids_lt)){
                foreach ($rdata as $val) {
                    if (in_array($val['id'], $menu_ids_lt)) {
                        $menuarr[] = $val;
                    }
                }
            }
        }
    }

    $menukv = [];
    $menu_cp = [];
    foreach ($menuarr as $key=>$val){
        $pid = $val['parent_id'];
        $menukv[$pid] ++;
        $menu_cp[$val['id']] = $pid;
        $url = $val['url'];
        $type = $val['type'];
        unset($menuarr[$key]['type']);
        $params = $val['params'];
        unset($menuarr[$key]['params']);
        if(!empty($url)){
            if(!empty($type) && $type != 'html'){
                $url .= ".{$type}";
            }
            if(empty($params)){
                $params = "?_mid_=".$val['id'];
            }else{
                $params .= "&_mid_=".$val['id'];
            }
            if(strpos($url, "?") > 0){
                $url .= "&" . ltrim($params, "?");
            } else {
                $url .= $params;
            }
            $menuarr[$key]['url'] = $url;
        }
    }

    $menu_level = [];
    foreach ($menu_cp as $key=>$val){
        $i = 0;
        $k = $val;
        while(isset($menu_cp[$k])){
            $i ++;
            $k = $menu_cp[$k];
        }
        $menu_level[$key] = $i;
    }

    $data = [];
    foreach ($menuarr as $key=>$val){
        if($menu_level[$val['id']] > 2) continue;
        if($menu_level[$val['id']] >= 2 || !isset($menukv[$val['id']])){
            $val['target'] = "iframe";
        }else{
            $val['target'] = "expand";
        }
        $data[] = $val;
    }

    EXITJSON([
        "code" => 200,
        "info" => "响应成功",
        "data" => $data
    ]);
};
