<?php

return function ($data){
    foreach ($data as $key=>$val){
        $url = $val['url'];
        $type = $val['type_value'];
        $params = $val['params'];
        if(!empty($url)) {
            if (!empty($type) && $type != 'html') {
                $url .= "." . $type;
            }
            if (!empty($url)) {
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
            }
        }
        if(empty($url)) {
            $data[$key]['name'] = "&nbsp;{$val['name']}";
        }else{
            $data[$key]['name'] = "&nbsp;<a href='{$url}' target='_blank'>{$val['name']}</a>";
        }
    }
    return $data;
};
