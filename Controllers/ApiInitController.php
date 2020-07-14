<?php
/**
 * API统一接口入口
 */
namespace Apcu\Controllers;
use App\Http\Controllers\Controller;

//支持跨域访问
header("Access-Control-Allow-Origin:*");

class ApiInitController extends Controller
{
    function __construct($tpl_path = "", $tpl_type = "", $args = [], $base_path = "")
    {
        empty($tpl_type) && $tpl_type = 'html';
        if(!in_array($tpl_type, ['html', 'info'])){
            exit("404错误 未找到页面");
        }
        $this->tpl_type = $tpl_type;
        $path = $base_path."/".$tpl_path;
        if(!is_readable($path)){
            $this->exitErr("无效接口路径！");
        }

        $ini_path = $path."/_.ini";
        if(!is_file($ini_path)){
            $this->exitErr("接口配置有误！");
        }

        $ini_file = import("IniFile", $ini_path);
        $ini_data = $ini_file->readAll();
        $ini_info = $ini_data['info'];
        $this->path = $path;
        $this->ini_file = $ini_file;
        $this->xfile = $ini_file->xFile();
        $this->ini_data = $ini_data;
        $this->ini_info = $ini_info;
        $url = $_SERVER['URL_API']."/".$tpl_path;
        $this->url = $url;
        $this->url_demo = $url;
        $durl = trim($ini_info['url']);
        if(!empty($durl)){
            if($durl[0] == '?'){
                $durl = ltrim($durl, '?');
                !empty($durl) && $durl = "?" . $durl;
            }
            $this->url_demo .= $durl;
        }

        $post_type = $ini_info['post_type'];
        empty($post_type) && $post_type == 'get';

        if($tpl_type == 'html') {
            if ($post_type == 'get') {
                if (count($_POST) > 0) {
                    EXITJSON(0, "只允许 GET 传递！");
                }
                $data = $this->getKeyToLower($_GET);
            } elseif ($post_type == 'post') {
                if (count($_POST) <= 0) {
                    EXITJSON(0, "只允许 POST 传递！");
                }
                $data = $this->getKeyToLower($_POST);
            } elseif ($post_type == 'both') {
                $data = $this->getKeyToLower($_GET);
                foreach ($_POST as $key => $val) {
                    $key = strtolower(trim($key));
                    $data[$key] = $val;
                }
            } else {
                EXITJSON(0, "无效提交模式！");
            }
            $this->data = $data;
        }else{
            $this->info();
        }
    }

    /**
     * 错误退出
     * @param $msg
     */
    private function exitErr($msg){
        $tpl_type = $this->tpl_type;
        if($tpl_type == 'info'){
            exit($msg);
        }
        EXITJSON(0, $msg);
    }

    /**
     * 帮助信息
     */
    private function info(){
        $ini_data = $this->ini_data;
        $info = $ini_data['info'];
        if($info['status'] != 1){
            $this->exitErr("接口已禁用！");
        }
        $dev = $info['dev'];
        $view_data = [];
        $post_type_upper = strtoupper($info['post_type']);
        $post_type_upper == 'BOTH' ? $post_type = "全部" : $post_type = $post_type_upper;
        $view_data['title'] = $info['dir_name'];
        $view_data['type'] = $post_type;
        $view_data['remark'] = $info['remark'];
        $view_data['url'] = $this->url;
        $view_data['url_demo'] = $this->url_demo;
        $argslist = [];
        $ddata = $ini_data[$dev];
        $result_de = json_decode($ddata['result'], true);
        if(is_null($result_de)){
            $result = $ddata['result'];
        }else{
            $result= json_encode($result_de, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        $view_data['result'] = $result;
        if($dev == 'sql'){
            try {
                $_args = json_decode($ddata['args'], true);
                $args = [];
                $args_no = [];
                if(is_array($_args)) {
                    $gets = [];
                    $posts = [];
                    foreach ($_args as $key=>$val){
                        if(is_array($val)){
                            !empty($val['get']) && $gets[$key] = $val['get'];
                            !empty($val['post']) && $posts[$key] = $val['post'];
                        }else{
                            $args_no[] = $key;
                        }
                    }
                    if($post_type_upper == 'GET'){
                        $args = $gets;
                    }elseif($post_type_upper == 'POST'){
                        $args = $posts;
                    }else{
                        $args = $posts;
                        foreach ($gets as $key=>$val){
                            !isset($args[$key]) && $args[$key] = $val;
                        }
                    }
                }
                if(!empty($args_no)){
                    $args['其他参数'] = implode(', ', $args_no);
                }
                if(!empty($args)){
                    $argslist['传递参数'] = $args;
                }
            }catch (\Exception $e){
                //
            }
        }elseif($dev == 'api'){
            $headers = $this->getArrayKeyValue($ddata['headers']);
            if(!empty($headers)){
                if($ddata['oauth_status'] == '1'){
                    unset($headers['Authorization']);
                }else {
                    isset($headers['Authorization']) && $headers['Authorization'] = "***";
                }
                !empty($headers) && $argslist['头部信息'] = $headers;
            }

            $posts = $this->getArrayKeyValue($ddata['posts']);
            if(!empty($posts)){
                $argslist['传递参数 ('.$ddata['post_type'].')'] = $posts;
            }
        }
        $view_data['argslist'] = $argslist;
        exit(view("sys/public/layout/api", $view_data));
    }

    /**
     * 获取JSON格式
     * @param $json_str
     * @return array
     */
    private function getArrayKeyValue($json_str){
        $ret = [];
        try {
            $json = json_decode($json_str, true);
            foreach ($json as $js){
                $ret[$js['key']] = $js['value'];
            }
        }catch (\Exception $e){
            //
        }
        return $ret;
    }

    /**
     * 数组键值小写
     * @param $data
     * @return array
     */
    private function getKeyToLower($data){
        $new_data = [];
        if(!is_array($data)) return $new_data;
        foreach ($data as $key=>$val){
            $key = strtolower(trim($key));
            $new_data[$key] = $val;
        }
        return $new_data;
    }

    public function __last(&$retdata){
        if(is_array($retdata)){
            $this->keyToLowers($retdata);
        }
        if($this->tpl_type == 'html') {
            $ini_info = $this->ini_info;
            $dev = $ini_info['dev'];
            if ($dev == 'sql') {
                EXITJSON(0, "使用接口访问！");
            } elseif ($dev == 'api') {
                EXITJSON(1, "success");
            } elseif ($dev != 'code') {
                EXITJSON(0, "无效开发模式！");
            }
        }
    }
}
