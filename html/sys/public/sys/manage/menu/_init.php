<?php

return function (){
    if($this->isPost()) {
        $url = trim($_POST['url']);
        $is_http = strtolower(substr($url, 0, 7)) == 'http://' || strtolower(substr($url, 0, 8)) == 'https://';
        if(!empty($url)) {
            if(!$is_http) {
                $tmpstr = $url;
                $posarr = ['.', '?', '#'];
                $posi = -1;
                foreach ($posarr as $val) {
                    $pos = strpos($url, $val);
                    if ($pos !== false) {
                        if ($posi < 0) {
                            $posi = $pos;
                        } elseif ($pos < $posi) {
                            $posi = $pos;
                        }
                    }
                }
                if ($posi > 0) {
                    $url = substr($url, 0, $posi);
                }
                $url = str_replace("\\", "/", $url);
                $url = strtolower(trim(trim($url), '/'));
            }
            if(empty($url)){
                $this->setPostValue("url", "");
            } else {
                if(!$is_http){
                    $urlarr = explode("/", $url);
                    if ($urlarr[0] == 'sys') {
                        EXITJSON(0, '不能使用 sys 系统默认目录！');
                    }
                    $url = "/" . $url;
                }
                $tmpstr != $url && $this->setPostValue("url", $url);
            }
        }
        if(!empty($_POST['params'])) {
            $params = $_POST['params'];
            $tmpstr = $params;
            $params = trim(trim($params), "?&");
            $params = trim($params);
            if($params[0] != '#'){
                if($is_http){
                    if(strpos($url, "?") > 0){
                        $params = "&" . $params;
                    }else{
                        $params = "?" . $params;
                    }
                } else {
                    $params = "?" . $params;
                }
            }
            $params != $tmpstr && $this->setPostValue("params", $params);
        }
    }
};