<?php

namespace Tphp\Apcu;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Response;

/**
 * 初始化路径设置
 * Class DomainsPath
 * @package App\Http\Controllers
 */
class DomainsPath
{

    function __construct($tpl='')
    {
        config(["database.default_src" => config('database.default')]);
        $tpl_base = TPHP_TPL_PATH . "/";
        $tpl_base_vendor = dirname(__DIR__) . "/html/";
        $top_path = "";
        if (defined("BASE_TPL_PATH_TOP")) {
            $top_path = trim(trim(BASE_TPL_PATH_TOP, "/")) . "/";
        }
        list($this->tpl_path, $this->tpl_type, $this->args) = $this->getTplPath($tpl);
        $config = $GLOBALS['DOMAIN_CONFIG'];
        $is_backstage = false;
        if (isset($config['backstage']) && $config['backstage'] === true) {
            $is_backstage = true;
        }
        if (empty($this->tpl_path)) {
            //内部初始化文件
            if ($is_backstage) {
                $this->tpl_path = "sys/index";
            } else {
                $this->tpl_path = "index";
            }
        }elseif(!$is_backstage){
            $tparr = explode("/", $this->tpl_path);
            if(count($tparr) > 0 && trim(strtolower($tparr[0])) == 'sys'){
                EXITJSON(0, "无权访问 sys 目录!");
            }
        }
        $tpl_in_path = $tpl_base . $top_path;
        $out_path = "sys/public/";
        if (!is_readable($tpl_in_path . $this->tpl_path)) {
            $top_path = $out_path;
        }
        $tpl_out_path = $tpl_base_vendor . $out_path;
        define("BASE_TPL_PATH", $top_path);
        $this->base_tpl_path = $top_path;
        $conn = $config['conn'];
        if (isset($config['user']) && !empty($config['user'])) {
            $user = $config['user'];
            if (is_string($user)) {
                $userinfo = config("database.connections.{$user}");
                if (!empty($userinfo) && is_array($userinfo)) {
                    $cuser = config("database.connections.user");
                    foreach ($userinfo as $key => $val) {
                        $cuser[$key] = $val;
                    }
                    config(["database.connections.user" => $cuser]);
                }
            }
        }
        if (!empty($conn)) {
            config(["database.default" => $conn]);
        }
        //内部初始化文件
        if ($is_backstage) {
            //如果是后台则使用通用配置设置
            $real_path = $tpl_out_path . "init/backstage.php";
        } else {
            $real_path = $tpl_in_path . "_init.php";
            //如果内部初始化文件未找到则找外部初始化文件
            if (!file_exists($real_path)) $real_path = $tpl_out_path . "_init.php";
        }
        if (file_exists($real_path)) {
            include_once $real_path;
        }
    }

    /**
     * 获取TPL路径
     * @param $request
     * @return string
     */
    private function getTplPath($tpl='')
    {
        if(empty($tpl)) {
            $request = Request();
            $a = [];
            for ($i = 1; $i < 10; $i++) {
                $api_name = "api_name_{$i}";
                $arg = $request->$api_name;
                if (empty($arg) || trim($arg) == "") {
                    break;
                }
                $a[] = $arg;
            }
            $tpl = implode("/", $a);
        }

        $pos = strrpos($tpl, ".");
        if ($pos <= 0) {
            $type = "html";
        } else {
            $type = substr($tpl, $pos + 1);
            $tpl = substr($tpl, 0, $pos);
        }

        $args = $GLOBALS['DOMAIN_CONFIG']['args'];
        $args = str_replace("\\", "/", $args);
        $args = trim(trim($args, '/'));
        $argsinfo = [];
        if(!empty($args)){ //URL路径args传递
            $argsarr = explode("/", $args);
            $argsnew = [];
            foreach ($argsarr as $val){
                $val = trim($val);
                !empty($val) && $argsnew[] = $val;
            }
            if(!empty($argsnew)){
                $tplarr = explode("/", $tpl);
                $tplnew = [];
                foreach ($tplarr as $val){
                    $val = trim($val);
                    !empty($val) && $tplnew[] = $val;
                }
                if(count($tplnew) < count($argsnew)){
                    exit("参数传递错误，URL参数代码：/".implode("/", $argsnew)." 当前TPL： {$tpl}");
                }
                $argsv = [];
                $delkey = [];
                $tplval = [];
                foreach ($argsnew as $key=>$val){
                    $argsv[$val] = $tplnew[$key];
                    $delkey[] = $key;
                    $tplval[] = $tplnew[$key];
                }
                foreach ($delkey as $val){
                    unset($tplnew[$val]);
                }
                $tpl = implode("/", $tplnew);
                empty($tpl) && $tpl = 'index';
                $argsinfo['info'] = $argsv;
                $argsinfo['url'] = "/".implode("/", $tplval);;
            }
        }

        return [$tpl, $type, $argsinfo];
    }
}

class DomainsController extends Controller
{

	function __construct($tpl='') {
        if(empty($GLOBALS['DOMAIN_PATH'])){
            $GLOBALS['DOMAIN_PATH'] = new DomainsPath($tpl);
        }
        $domains_path = $GLOBALS['DOMAIN_PATH'];
        foreach ($domains_path as $key=>$val){
            $this->$key = $val;
        }
        if(!defined('SESSION_ID')){
            session_start();
            $sid = session_id();
            define('SESSION_ID', $sid);
            define('SESSION_ID_MD5', substr(md5($sid), 8, 16));
            session_destroy();
        }
        $this->__sys_path = dirname(__DIR__)."/sys/";
	}

    /**
     * 建立动态CSS或JS
     * @param $html
     * @return string
     */
	private static function obRebuildStatic($html){
        $dfs = $GLOBALS['DATA_FILE_STATIC'];
        $css = $dfs['css'];
        if(empty($css)){
            $css = [];
        }
        if(!empty($dfs['css_md5'])){
            $css[] = url('/static/tpl/css/'.$dfs['css_md5'].'.css');
        }

        $js = $dfs['js'];
        if(empty($js)){
            $js = [];
        }
        if(!empty($dfs['js_md5'])){
            $js[] = url('/static/tpl/js/'.$dfs['js_md5'].'.js');
        }
        $html_lower = strtolower($html);

        $pos_head = strpos($html_lower, '</head>');
        $pos_body = strrpos($html_lower, '</body>');
        $t_str = "";
        if($pos_head > 0){
            $t_str = "\t";
            $html_left = substr($html, 0, $pos_head);
            if($pos_body > 0){
                $html_mid = substr($html, $pos_head, $pos_body - $pos_head);
                $html_right = substr($html, $pos_body);
            }else{
                $html_mid = substr($html, $pos_head);
                $html_right = "";
            }
        }else{
            $html_left = "";
            if($pos_body > 0){
                $html_mid = substr($html, 0, $pos_body);
                $html_right = substr($html, $pos_body);
            }else{
                $html_mid = $html;
                $html_right = "";
            }
        }

        $top_str = "";
        if(!empty($css)){
            foreach ($css as $c){
                $top_str .= $t_str.'<link rel="stylesheet" href="'.$c.'" />'."\n";
            }
        }
        $down_str = "";
        if(!empty($js)){
            empty($html_right) && $down_str .= "\n";
            foreach ($js as $j){
                if($j[0] == '@'){
                    $j = ltrim($j, '@');
                    $top_str .= $t_str.'<script src="'.$j.'"></script>'."\n";
                }else{
                    $down_str .= '<script src="'.$j.'"></script>'."\n";
                }
            }
        }
	    $html = $html_left.$top_str.$html_mid.$down_str.$html_right;
	    return $html;
    }

    /**
     * 返回JSON页面格式
     * @param $html
     * @return string
     */
    private static function obExitJson($html)
    {
        $html_de = json_decode($html, true);
        if (empty($html_de)) {
            return $html;
        } else {
            try {
                header('Content-Type:application/json charset=utf-8');
            } catch (Exception $e) {
                // TODO
            }
            return json_encode($html_de, JSON_UNESCAPED_UNICODE);
        }
    }

	/**
	 * 载入加载模块
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function tpl(){
	    $this->is_rebuild_html = false;
        list($html, list($cookies, $cookies_forget)) = $this->_tpl_($this->tpl_path, $this->tpl_type);
        $retrp = Response::make($html);
        if(is_array($cookies)) {
            //cookies设置
            foreach ($cookies as $val) {
                list($k, $v, $exp) = $val;
                if ($exp <= 0) {
                    $cookie = Cookie::forever($k, $v);
                } else {
                    $cookie = Cookie::make($k, $v, $exp);
                }
                $retrp->withCookie($cookie);
            }
            //cookies删除
            foreach ($cookies_forget as $val) {
                $retrp->withCookie(Cookie::forget($val));
            }
        }
        $obj = $this->obj;
        if($this->is_rebuild_html && !empty($obj)) {
            // 必须先运行获取配置
            $css = $obj->getCss();
            $js = $obj->getJs();

            // 再根据配置进行设置CSS或JS
            $dfs = $GLOBALS['DATA_FILE_STATIC'];
            if(empty($dfs)){
                $dfs = [];
            }
            if(!empty($css)){
                $dfs['css_md5'] = $css;
            }
            if(!empty($js)){
                $dfs['js_md5'] = $js;
            }
            if(!empty($dfs)){
                $GLOBALS['DATA_FILE_STATIC'] = $dfs;
                ob_start('self::obRebuildStatic');
            }
        }
        return $retrp;
	}

	//数据库操作
    public function db($table = "", $conn = ""){
        if(empty($conn)){
            $conn = config('database.default');
        }

        if(empty($table)) {
            $db = \DB::connection($conn);
        }else{
            $db = \DB::connection($conn)->table($table);
        }
        return $db;
    }

    /**
     * 获取模板文件
     * @param string $tpl
     * @param string $type
     * @param array $config
     * @return array
     */
	protected function _tpl_($tpl = '', $type = 'html', $config = []){
	    $tplful = $tpl.".".$type;
		empty($config) && $config = [];
        $argsinfo = $this->args;
		!empty($argsinfo) && $config['args'] = $argsinfo;
        $this_path = base_path(get_tphp_html_path().$this->base_tpl_path.$this->tpl_path);
        if(!is_dir($this_path)){
            $this_path = dirname(__DIR__)."/html/".$this->base_tpl_path.$this->tpl_path;
        }
		//首先在data.php中取得layout,其次是domain.php中选定
        $dc = $GLOBALS['DOMAIN_CONFIG'];
        $data_file = $this_path.'/data.php';
        if(file_exists($data_file)){
            $data = include $data_file;
            empty($data) && $data = [];
            $GLOBALS['DATA_FILE_INFO'][$data_file] = $data;
        }else{
            $data = [];
        }

        if(isset($data['layout'])){
            $layout = $data['layout'];
        }else if(isset($dc['layout'])){
            $layout = $dc['layout'];
            !isset($config['layout']) && $config['layout'] = $layout;
        }else{
            $layout = "";
        }
        $list = tpl($tplful, $config, true);
        if(!is_array($list)){
            if(is_string($list)){
                abort(404, $list);
            }
            return $list;
        }

        if(count($list) == 2 && $list[0] == -100100){
            $l1 = $list[1];
            if(is_array($l1)){
                $l1 = json_encode($l1, JSON_UNESCAPED_UNICODE);
            }
            ob_start("self::obExitJson");
            return [$l1, []];
        }

        list($__tpl__, $retdata, $cookiesinfo, $exitjson, $obj) = $list;
		if(!empty($exitjson)){
            ob_start("self::obExitJson");
		    return [$exitjson, $cookiesinfo];
        }
        $oconf = $obj->config;
		if(isset($oconf['layout'])){
            $layout = $oconf['layout'];
        }
        $this->obj = $obj;
        $type = $obj->tpl_type;
		if($layout !== false && !in_array($type, ['list', 'testlist'])) {
		    empty($layout) && $layout = $this->tpl_path;
            $layout = trim($layout);
            $layout_bool = false;
            if(strlen($layout) > 0 && $layout[0] == '/'){
                $lo = trim($layout, '/');
                if(view()->exists($lo)){
                    $layout = $lo;
                    $layout_bool = true;
                }else{
                    $lo .= "/tpl";
                    if(view()->exists($lo)){
                        $layout = $lo;
                        $layout_bool = true;
                    }
                }
            }
            if(!$layout_bool){
                $layouts = [
                    $this->base_tpl_path . "layout/" . $layout,
                    $this->base_tpl_path . "layout/public",
                    "sys/public/layout/{$layout}",
                    TPHP_PATH."/sys/public/layout/{$layout}",
                ];
                if(!in_array($type, ['add', 'edit', 'handle'])){
                    $layouts[] = "sys/public/layout/public";
                    $layouts[] = TPHP_PATH."/sys/public/layout/public";
                }
                foreach ($layouts as $val){
                    if(view()->exists($val)){
                        $layout = $val;
                        $layout_bool = true;
                        break;
                    }else{
                        $val .= "/tpl";
                        if(view()->exists($val)){
                            $layout = $val;
                            $layout_bool = true;
                            break;
                        }
                    }
                }
            }

            // 标题
            if(isset($data['title'])){
                $title = $data['title'];
            }elseif(isset($dc['title'])){
                $title = $dc['title'];
            }
            empty($title) && $title = "";

            // 关键词
            if(isset($data['keywords'])){
                $keywords = $data['keywords'];
            }elseif(isset($dc['keywords'])){
                $keywords = $dc['keywords'];
            }
            empty($keywords) && $keywords = "";

            // 描述
            if(isset($data['description'])){
                $description = $data['description'];
            }elseif(isset($dc['description'])){
                $description = $dc['description'];
            }
            empty($description) && $description = "";

			if ($layout_bool) {
			    $args_url = $argsinfo['url'];
			    empty($args_url) && $args_url = "";
                is_array($__tpl__) && $__tpl__ = "";
                $retdata['__tpl__'] = $__tpl__;
                !empty($title) && $retdata['title'] = $title;
                !empty($keywords) && $retdata['keywords'] = $keywords;
                !empty($description) && $retdata['description'] = $description;
                $retdata['tpl_type'] = $this->tpl_type;
                $retdata['tpl_path'] = $this->tpl_path;
                $retdata['tpl_base'] = $this->base_tpl_path;
                $retdata['_DC_'] = $GLOBALS['DOMAIN_CONFIG'];
                $retdata['args_url'] = $args_url;
                $ov = $obj->viewdata;
                if(!empty($ov) && is_array($ov)) {
                    foreach ($ov as $key => $val) {
                        !isset($retdata[$key]) && $retdata[$key] = $val;
                    }
                }
                $this->is_rebuild_html = true;
				return [view($layout, $retdata), $cookiesinfo];
			}
		}
        if(is_array($__tpl__)){
            $__tpl__ = json_encode($__tpl__, true);
        }else{
            $this->is_rebuild_html = true;
        }
		return [$__tpl__, $cookiesinfo];
	}
}
