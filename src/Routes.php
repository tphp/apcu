<?php

namespace Tphp\Apcu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tphp\Apcu\DomainsController;
use Illuminate\Support\ServiceProvider;
use Tphp\Apcu\Controllers\TplController;

error_reporting(E_ALL ^ E_NOTICE);

// 默认时区设置为中国上海
date_default_timezone_set(env("TIMEZONE", 'Asia/Shanghai'));

class Routes{
    /**
     * 设置默认配置
     */
    private static function setConfig(){
        $v_paths = config("view.paths");
        if(empty($v_paths)){
            $v_paths = [];
        }
        $tb_path = realpath(base_path(trim(get_tphp_html_path(), "/")));
        if($tb_path){
            $v_paths[] = $tb_path;
        }
        $tphp_path = dirname(__DIR__);
        if(!defined('TPHP_PATH')){
            define('TPHP_PATH', $tphp_path);
        }
        if(!defined('TPHP_TPL_PATH')){
            define('TPHP_TPL_PATH', $tb_path);
        }
        $v_paths[] = $tphp_path."/html";
        config(["view.paths" => $v_paths]);
        $static = env("STATIC", "static/");
        if(empty(config('path.static'))){
            config(["path.static" => env("STATIC", $static)]);
        }
        if(empty(config('path.static_tphp'))){
            config(["path.static_tphp" => env("STATIC_TPHP", $static."tphp/")]);
        }
    }

    /**
     * 重新解析域名
     */
    private static function setDomains(&$domain_routes, $hharr){
        $domains = config("domains");
        if(is_array($domains)){
            foreach ($domains as $key=>$val){
                $keys = explode("|", $key);
                foreach ($keys as $v){
                    if(empty($v)){
                        continue;
                    }
                    $v = strtolower(trim(trim($v), "\."));
                    if(strpos($v, "*") !== false){
                        // 域名泛解析
                        $v_arr = explode(".", $v);
                        foreach ($v_arr as $kk=>$vv){
                            if($vv == '*' && isset($hharr[$kk])){
                                $v_arr[$kk] = $hharr[$kk];
                            }
                        }
                        $v = implode(".", $v_arr);
                    }
                    $domain_routes[$v] = $val;
                }
            }
        }
        config(["domains" => $domain_routes]);
    }

    /**
     * 设置env
     * @param $env
     */
    private static function setEnv($env){
        $set_envs = [];
        foreach ($env as $key=>$val){
            if(is_int($key)){
                if(!is_string($val)){
                    continue;
                }
                $eq_pos = strpos($val, "=");
                if($eq_pos === false || $eq_pos <= 0){
                    continue;
                }
                $set_envs[] = $val;
                continue;
            }
            if(is_string($val) || is_numeric($val)){
                $set_envs[] = "{$key}={$val}";
            }elseif (is_bool($val)){
                if($val){
                    $set_envs[] = "{$key}=true";
                }else{
                    $set_envs[] = "{$key}=false";
                }
            }
        }
        foreach ($set_envs as $se){
            putenv($se);
        }
    }

    /**
     * 设置路由规则
     */
    private static function setRoute(){
        /**
         * 先分配ICON、JS和CSS生成模块
         */
        //公共模板CSS
        Route::get('/static/tpl/css/{md5}.css', function (){return (new TplController())->css();});
        //公共模板JS
        Route::get('/static/tpl/js/{md5}.js', function (){return (new TplController())->js();});
        //默认图标favicon.ico
        Route::any('/favicon.ico', function (){return (new TplController())->ico();});

        /**
         * 自动路由分配
         * 1、对config("domains")中的配置进行路由
         * 2、优先处理config("domains")中的路由配置
         * 3、如果配置中不存在则从Apcu模块中获取对应位置
         */
        $hh = $_SERVER['HTTP_HOST'];
        $hh_info = explode(":", $hh);
        $hh_port = '';
        $hhs = [];
        if(count($hh_info) > 1){
            $hhs[] = $hh;
            list($hh, $hh_port) = $hh_info;
        }
        $hharr = explode(".", $hh);
        $hhstr = "";
        foreach ($hharr as $val){
            if(empty($hhstr)){
                $hhstr = $val;
            }else{
                $hhstr .= ".".$val;
            }
            $hhs[] = $hhstr;
        }
        rsort($hhs);

        $domains = config("domains");
        $domain_routes = [];
        self::setDomains($domain_routes, $hharr);

        $dm = "";
        foreach ($hhs as $val){
            if(isset($domain_routes[$val])){
                $dm = $val;
                break;
            }
        }
        if(!empty($dm) && !empty($domain_routes[$dm])){
            $drd = $domain_routes[$dm];
            $drd_tpl = trim(trim($drd['tpl']), "\\/");
            $dm_data_file = TPHP_TPL_PATH."/{$drd_tpl}/data.php";
            if(is_file($dm_data_file)){
                $ext_data = include $dm_data_file;
                if(!empty($ext_data) && is_array($ext_data)){
                    foreach ($ext_data as $ext_key=>$ext_val){
                        if(empty($ext_val)){
                            continue;
                        }
                        if(in_array($ext_key, [
                            'conn', // 数据库链接标识
                            'backstage', // 是否是后台
                            'title', // 页面标题
                            'color', // 后台主题颜色 ['088', '0aa']
                            'user', // 用户登录数据库，默认为user数据库标识
                            'args', // URL前置参数绑定设置 如 /name/subname
                            'href', // URL链接
                            'layout', //全局布局，优先级最低 如 public/tpl
                            'icon', // 网站图标设置，支持jpeg、png、jpg、ico等，默认：favicon.ico
                            ])){
                            $drd[$ext_key] = $ext_val;
                        }elseif($ext_key == 'routes'){ // 路由设置
                            if(empty($drd[$ext_key])){
                                $drd[$ext_key] = $ext_val;
                            }else{
                                foreach ($ext_val as $evk=>$evv){
                                    if(empty($evv) || !is_array($evv)){
                                        continue;
                                    }
                                    if(empty($drd[$ext_key][$evk])){
                                        $drd[$ext_key][$evk] = [];
                                    }
                                    foreach ($evv as $k=>$v){
                                        if(!empty($v)){
                                            $drd[$ext_key][$evk][$k] = $v;
                                        }
                                    }
                                }
                            }
                        }elseif($ext_key == 'env'){ // 路由设置
                            if(empty($drd[$ext_key])){
                                $drd[$ext_key] = $ext_val;
                            }elseif(is_array($ext_val)){
                                foreach ($ext_val as $evk=>$evv){
                                    if(is_int($evk)){
                                        $drd[$ext_key][] = $evv;
                                    }else{
                                        $drd[$ext_key][$evk] = $evv;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            foreach ($drd as $key=>$val){
                if(is_object($val)){
                    $drd[$key] = $val();
                }
            }
            $drd['key'] = $dm;
            $domain_routes[$dm] = $drd;
            $GLOBALS['DOMAIN_CONFIG'] = $drd;
            $GLOBALS['DOMAINS'] = $domain_routes;
            $tpl = $drd['tpl'];
            if(!empty($tpl) && !defined("BASE_TPL_PATH_TOP")){
                define("BASE_TPL_PATH_TOP", $tpl);
                $routes = $drd['routes'];
                empty($routes) && $routes = [];

                $routesbools = [];
                foreach($routes as $key=>$val) {
                    foreach($val as $k=>$v) {
                        if($routesbools[$k]) break;
                        $routesbools[$k] = true;
                    }
                }

                $env = $drd['env'];
                if(!empty($env) && is_array($env)){
                    self::setEnv($env);
                }


                // 加载Session中间件，使Session缓存起作用
                $middleware = [
                    \Illuminate\Session\Middleware\StartSession::class,
                    \Illuminate\View\Middleware\ShareErrorsFromSession::class,
                ];
                $route_mod = Route::domain($hh);
                if(count($routes) > 0) {
                    //优先根据指定路径
                    $route_mod->group(function () use ($routes, $middleware) {
                        foreach ($routes as $key => $val) {
                            if(in_array($key, ['any', 'delete', 'get', 'options', 'patch', 'post', 'put', 'redirect', 'view'])){
                                foreach ($val as $k => $v) {
                                    Route::$key($k, $v)->middleware($middleware);
                                }
                            }
                        }
                    });
                }
                //优先根据指定路径
                $route_mod->group(function () use ($routesbools, $middleware) {
                    $add_str = "";
                    !$routesbools['/'] && Route::get('/', function (){return (new DomainsController())->tpl();})->middleware($middleware);
                    $ru = str_replace("\\", "/", $_SERVER['REQUEST_URI']);
                    $ru = explode('?', $ru)[0];
                    $ru = explode('#', $ru)[0];
                    $ru = trim(trim($ru, '/'));
                    $ruarr = explode("/", $ru);
                    $rucot = count($ruarr);
                    if($rucot > 0) {
                        for ($i = 1; $i <= $rucot; $i++) {
                            $add_str .= '/{api_name_' . $i . '}';
                        }
                        !$routesbools[$add_str] && Route::any($add_str, function (){return (new DomainsController())->tpl();})->middleware($middleware);
                    }
                });
            }
        }
    }

    /**
     * 路由入口
     * @param null $namespace
     */
    public static function set($namespace=null){
        self::setConfig();
        self::setRoute();
    }
}
