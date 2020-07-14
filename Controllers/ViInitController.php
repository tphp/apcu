<?php
/**
 * 可视化系统
 */
namespace Apcu\Controllers;
use App\Http\Controllers\Controller;

//支持跨域访问
header("Access-Control-Allow-Origin:*");

class ViInitController extends Controller
{
    function __construct($tpl_path = "", $tpl_type = "", $args = [], $base_path = "")
    {
        $this->tpl_path = $tpl_path;
        empty($tpl_type) && $tpl_type = 'html';
        if(!in_array($tpl_type, ['html', 'info'])){
            exit("404错误 未找到页面");
        }
        $this->tpl_type = $tpl_type;
        $path = $base_path."/".$tpl_path;
        if(!is_readable($path)){
            $this->exitErr("无效路径！");
        }

        $ini_path = $path."/_.ini";
        if(!is_file($ini_path)){
            $this->exitErr("配置有误！");
        }

        $ini_file = import("IniFile", $ini_path);
        $ini_data = $ini_file->readAll();
        $ini_info = $ini_data['info'];
        $this->ini_data = $ini_data;
        if($ini_info['status'] != 1){
            $this->exitErr("该视图未启用！");
        }

        if($tpl_type == 'html') {
            $dir = $ini_data['ini']['dir'];
            $tpl_real_path = "www/vi/tpl/".$dir;
            $class = "/".$tpl_real_path.".html";
            if(!defined('VI_TPL_IS_LOAD')) {
                define('VI_TPL_IS_LOAD', 'is_load');
                define('VI_TPL_IS_LOAD_STR', tpl($class));
            }
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
        $dev = $info['dev'];
        $view_data = [];
        $post_type = strtoupper($info['post_type']);
        $post_type == 'BOTH' && $post_type = "全部";
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
                $args = json_decode($ddata['args'], true);
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

    public function __last(){
        if(defined('VI_TPL_IS_LOAD_STR')) {
            $idata = $this->ini_data;
            $idatai = $idata['ini'];
            $dir = $idata['ini']['dir'];
            $dfile = $this->tplbase."www/vi/tpl/".$dir."/data.php";
            $dfield = [];
            if(file_exists($dfile)){
                $data = include $dfile;
                if(isset($data['field'])){
                    $dfield = $data['field'];
                    if(!is_array($dfield)){
                        $dfield = [];
                    }
                }
            }
            $ifield = [];
            if(isset($idatai['fields'])) {
                $ifield = json_decode($idatai['fields'], true);
                if(!is_array($ifield)){
                    $ifield = [];
                }
            }

            $fields = [];
            foreach ($dfield as $key=>$val){
                if(is_numeric($val)){
                    $val = "{$val}";
                }elseif(!is_string($val)){
                    $val = "";
                }
                if(is_string($key)){
                    $keyname = $key;
                }elseif(!empty($val)){
                    $keyname = $val;
                }
                if(isset($ifield[$keyname])){
                    $fields[$keyname] = [
                        'name' => $val,
                        'field' => $ifield[$keyname]
                    ];
                }
            }

            $url_base = getenv("URL_VI");
            if(!empty($url_base)){
                $url_base = rtrim($url_base, "/") . "/";
            }
            exit(view("sys/public/layout/vi/tpl", [
                'tpl_html_show' => VI_TPL_IS_LOAD_STR,
                'title' => $idatai['title'],
                'fields' => json_encode($fields, JSON_UNESCAPED_UNICODE),
                'url_base' => $url_base
            ]));
        }
    }
}
