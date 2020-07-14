<?php
/**
 * 可视化系统
 */
namespace Apcu\Controllers;
use App\Http\Controllers\Controller;

//支持跨域访问
header("Access-Control-Allow-Origin:*");

class ViListInitController extends Controller
{
    function __construct($tpl_path = "", $tpl_type = "", $args = [], $base_path = "")
    {
        $this->explord($base_path);
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
        if(!is_readable($path.'/_.ini')){
            $this->exitErr("该页面未配置！");
        }
            $this->info($path,$tpl_path);
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
    private function info($path='',$tpl_path=''){
        $view_data['page_title']=$tpl_path;
        $view_data['tplpath']=$tpl_path;
//        $tmp_data=json_decode(@file_get_contents($path.'/conf.txt'),true);
        $tmp_data = import('IniFile',$path.'/_.ini', "list")->readAll();
//        dd($tmp_data);
        $view_data['url']=$tmp_data['list']['url'];
        $view_data['can_view']=$tmp_data['list']['can_view'];
        $view_data['field']=$tmp_data['list']['field'];
        $view_data['title']=$tmp_data['list']['title'];
        exit(view("sys/public/layout/vi/list/tpl", $view_data));
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
    //导出
    public function explord($base_path){
        if($_GET['isexcel']=='1'){
//            $res=@file_get_contents($base_path.'/'.$_GET['tplpath'].'/conf.txt');
            $ini_data = import('IniFile', $base_path.'/'.$_GET['tplpath'].'/_.ini', "info")->readAll();
            $res=$ini_data['list'];
            if(!empty($_GET['pagenum'])&&!empty($_GET['limit'])){
                $start=((int)($_GET['pagenum'])- 1) * (int)($_GET['limit']);
                $data=@file_get_contents($res['url']."?a=".$start.'&b='.$_GET['limit']);

            }else{
                $data=@file_get_contents($res['url']."?a=1&b=99999999999");

            }
            $_GET['title']=json_decode($res['title'],true);
            $_GET['field']=json_decode($res['field'],true);
            $data=json_decode($data,true);
            $tv=[];
            foreach ($_GET['field'] as $k=>$v){
                $tv[$v]=$_GET['title'][$k];
            }


            //去掉不在列表的数据
            foreach ($data['data'] as $k=>$v){
                foreach ($v as $kk=>$vv) {
                    $i=0;
                    foreach ($tv as $key => $val) {
                        if($kk!=$key){
                            $i++;
                        }
                        if($i>=count($tv)){
                            unset($data['data'][$k][$kk]);
                        }
                    }
                }
            }

            $datas=$data['data'];
            import('Excel')->export($tv,$datas,'列表数据');
            exit;
        }
    }
}
