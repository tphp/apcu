<?php
/**
 * Apcu数据返回
 * @param $args
 */
error_reporting(E_ALL ^ E_NOTICE);
//APCU更新缓存ID设置
define("APCU_SET_TIME_CACHE", "__apcu_set_time_cache__");
if(!is_callable('apcu_fetch')){
    // 如果没有安装Apcu扩展，则退出
    $exit_str = "<div>需安装&nbsp;<a href=\"http://pecl.php.net/package/APCu\" target='_blank'>Apcu缓存</a>&nbsp;扩展</div>";
    exit($exit_str);
}

/**
 * 获取上一次方法调用路径
 */
if(!function_exists('dump_parent')) {
    function dump_parent()
    {
        $db = debug_backtrace();
        if(count($db) > 3){
            $db2 = $db[2];
            dump([
                'file' => $db2['file'],
                'func' => "{$db2['class']} -> {$db2['function']}"
            ]);
        }
    }
}

/**
 * 获取框架文件路径
 * @return mixed|string
 */
if(!function_exists('get_tphp_html_path')) {
    function get_tphp_html_path()
    {
        $ret = env('TPHP_PATH', 'html/');
        $ret = str_replace("\\", "/", $ret);
        $ret = trim($ret, "/") . "/";
        return $ret;
    }
}

if(!function_exists('apcu_ret')){
    function apcu_ret($config = [], $data = null){
        if(is_string($config)){ //配置参数为字符类型分析
            $keyname = trim($config);
        }else{
            $keyname = trim($config[0]);
            isset($config[1]) && $argval = $config[1];
        }

        $fchar = $keyname[0];
        //加减乘除特殊处理、#为系统函数
        if(in_array($fchar, ['+', '-', '*', '/', '#'])){
            $tmp = trim(substr($keyname, 1, strlen($keyname) - 1));
            $keyname = $fchar;
            if(!empty($tmp)){
                if(empty($argval)) {
                    $argval = $tmp;
                }else{
                    array_unshift($argval, $tmp);
                }
            }
        }

        if(empty($keyname)) return $data;

        $keyname = strtolower($keyname); //使配置项不区分大小写
        $funcstr = apcu_fetch($keyname);
        if(empty($funcstr)) return $data;

        //读取调用函数方法名称
        $funcname = apcu_fetch('_sysnote_')[$keyname]['func'];
        if(empty($funcname)) return $data;
        if(!function_exists($funcname)){
            eval("function {$funcname}{$funcstr}");
        }

        $argstr = "";
        if(is_array($argval)) {
            foreach ($argval as $key => $val) {
                $argstr .= ", \$argval[{$key}]";
            }
        }elseif(isset($argval)){
            $argstr .= ", \$argval";
        }
        $retdata = "";
        eval("\$retdata = {$funcname}(\$data{$argstr});");
        return $retdata;
    }
}

/**
 * Apcu参数说明
 * @param array $configs 配置参数
 * @param $data 数据
 * @return null|string
 */
if(!function_exists('apcu')) {
    function apcu($configs = [], $data = null)
    {
        $c = Cache::get(APCU_SET_TIME_CACHE);
        $af = apcu_fetch(APCU_SET_TIME_CACHE);
        $tpl_cache = getenv('TPL_CACHE');
        if (!empty($tpl_cache)) {
            $tpl_cache = strtolower(trim($tpl_cache));
        }
        if (empty($c) || empty($af) || $c !== $af || $tpl_cache === 'false') {
            (new \Tphp\Apcu\Controllers\ApcuController())->getApcu();
        }
        if (empty($configs)) return $data;
        if (is_string($configs)) { //当配置直接为字符串时处理一次任务
            return apcu_ret($configs, $data);
        } else {
            foreach ($configs as $key => $val) {
                if (is_string($key)) {
                    $config = [$key, $val];
                } elseif (is_string($val)) {
                    $config = $val;
                } else {
                    $k = $val[0];
                    $v = [];
                    $ki = 0;
                    foreach ($val as $kk => $vv) {
                        if ($ki > 0) {
                            if (is_string($kk)) {
                                $v[] = [$kk => $vv];
                            } else {
                                $v[] = $vv;
                            }
                        }
                        $ki++;
                    }
                    $config = [$k, $v];
                }
                $data = apcu_ret($config, $data);
            }
            return $data;
        }
    }
}

/**
 * 显示Tpl代码
 * @param string $tpl Tpl模板路径
 * @param array $config 数据配置
 * @param string $runname 运行名称，run为模板，runJson为接口
 * @return mixed
 */

if(!function_exists('_tpl_')) {
    function _tpl_($tpl = '', $config = [], $runname = "run", $isarray = false)
    {
        if(!defined('BASE_TPL_PATH')){
            return (new \Tphp\Apcu\DomainsController($tpl, $config))->tpl();
        }
        $tpl = str_replace("\\", "/", $tpl);
        if (!class_exists('Tpl')) {
            apcu(['class_tpl']); //加载Tpl类
        }
        $tmproottpl = Tpl::$roottpl; //嵌套调用循环
        $obj = new Tpl($tpl, $tmproottpl);

        (empty($config) || !is_array($config)) && $config = [];

        if (!empty($config)) {
            $tmp_get = [];
            $tmp_post = [];
            if (!empty($config['get'])) { //GET参数模拟处理
                $tmp_get = $_GET;
                foreach ($config['get'] as $key => $val) {
                    $_GET[$key] = $val;
                }
            }

            if (!empty($config['post'])) { //POST参数模拟处理
                $tmp_post = $_POST;
                foreach ($config['post'] as $key => $val) {
                    $_POST[$key] = $val;
                }
            }

            $obj->addConfig($config);
            $ret = $obj->$runname($isarray);
            !empty($config['get']) && $_GET = $tmp_get;
            !empty($config['post']) && $_POST = $tmp_post;
        } else {
            $ret = $obj->$runname($isarray);
            if (is_array($ret)) {
                foreach ($ret as $key => $val) {
                    if ($key === '_' && empty($val)) {
                        return "";
                    }
                }
            }
        }
        if (!empty($tmproottpl)) {
            Tpl::$roottpl = $tmproottpl;
        }
        if (is_array($ret)) {
            if ($ret[0] === -100100) {
                if (!$isarray || in_array($obj->tpl_type, ['data', 'json'])) {
                    $ret = $ret[1];
                }
            } elseif ($ret[0] !== true) {
                $ret[] = $obj;
            }

        }
        return $ret;
    }
}

/**
 * 显示Tpl代码
 * @param string $tpl Tpl模板路径
 * @param array $config 数据配置
 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|mixed|string
 */
if(!function_exists('tpl')) {
    function tpl($tpl = '', $config = [], $isarray = false)
    {
        return _tpl_($tpl, $config, "run", $isarray);
    }
}

// 不执行TPL代码
if(!function_exists('x_tpl')) {
    function x_tpl()
    {
        return '';
    }
}

/**
 * API接口程序
 * @param string $tpl Tpl模板路径
 * @param array $config 数据配置
 * @return mixed
 */
if(!function_exists('tpl_api')) {
    function tpl_api($tpl = '', $config = [])
    {
        return _tpl_($tpl, $config, 'runJson');
    }
}

/**
 * 获取接口数据
 * @param string $tpl
 * @return array
 */
if(!function_exists('tpl_api_data')) {
    function tpl_api_data($tpl = '')
    {
        $tpl = str_replace("\\", "/", $tpl);
        if (!class_exists('Tpl')) {
            apcu(['class_tpl']); //加载Tpl类
        }
        return (new Tpl($tpl))->getDataForArgsArray();
    }
}

/**
 * 获取data.php文件配置
 * @param string $tpl
 * @return array|mixed
 */
if(!function_exists('tpl_data_file')) {
    function tpl_data_file($tpl = '')
    {
        $tpl = str_replace("\\", "/", $tpl);
        if (!class_exists('Tpl')) {
            apcu(['class_tpl']); //加载Tpl类
        }
        return (new Tpl($tpl))->getDataFile("tpl_data_file");
    }
}

/**
 * 获取data.php文件配置
 * @param string $tpl
 * @return array|mixed
 */
if(!function_exists('tpl_class')) {
    function tpl_class($tpl = '')
    {
        $tpl = str_replace("\\", "/", $tpl);
        if (!class_exists('Tpl')) {
            apcu(['class_tpl']); //加载Tpl类
        }
        return new Tpl($tpl);
    }
}

/**
 * 输出分页HTML代码
 * @param int $type 分页类型
 * @param array $showargs 保留参数
 * @param string $fragment 锚链接标记
 * @param int $onEachSide 分页中间显示条数，系统默认为3条，当值小于或等于0时使用系统默认值
 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|mixed|string
 */
if(!function_exists('page')) {
    function page($type = null, $saveargs = [], $fragment = '', $onEachSide = 0)
    {
        return (new \Tphp\Apcu\Controllers\PageController())->page($type, $saveargs, $fragment, $onEachSide);
    }
}

if(!function_exists('seo')) {
    function seo($config, $use_bool = true)
    {
        if (!class_exists('__Seo')) {
            apcu(['class_seo']); //加载Tpl类
        }
        return __Seo::seo($config, $use_bool);
    }
}

if(!function_exists('scss')) {
    function scss($str)
    {
        if (!class_exists('Scssc')) {
            apcu(['class_scssc']); //加载Tpl类
        }
        $scss = new Scssc();
        return $scss->css_decompress($scss->compile($str));
    }
}

if(!function_exists('page404')) {
    function page404($msg = "404 Page Error!")
    {
        exit($msg);
    }
}

if(!function_exists('success')) {
    function success($msg = "操作成功", $data = [])
    {
        return ['_IS_OPERATE_JSON_' => true, 'data' => [1, $msg, $data]];
    }
}

if(!function_exists('fail')) {
    function fail($msg = "操作失败")
    {
        return ['_IS_OPERATE_JSON_' => true, 'data' => [0, $msg]];
    }
}

if(!function_exists('message')) {
    function message($code = 0, $msg = "", $data = [])
    {
        return ['_IS_OPERATE_JSON_' => true, 'data' => [$code, $msg, $data]];
    }
}

/**
 * 中文繁体转换
 * @param $html
 * @return null|string
 */
if(!function_exists('__ob_trad')) {
    function __ob_trad($html)
    {
        return apcu_ret('trad', $html);
    }
}

/**
 * 空对象
 */
if(!function_exists('EXITJSON')) {
    function EXITJSON()
    {
        try {
            header('Content-Type:application/json charset=utf-8');
        } catch (Exception $e) {
            // TODO
        }
        $argnums = func_num_args();
        $args = func_get_args();
        if ($argnums <= 0) {
            $args = [1, "操作成功"];
        } elseif ($argnums == 1) {
            $args0 = $args[0];
            if (is_string($args0)) {
                exit(json_encode($args0, JSON_UNESCAPED_UNICODE));
            } elseif (is_array($args0) || is_object($args0)) {
                exit(json_encode($args0, JSON_UNESCAPED_UNICODE));
            }
            $args[1] = "操作成功";
        }
        $obj = [];
        foreach ($args as $key => $val) {
            if ($key == 0) {
                $obj['code'] = $val;
            } elseif ($key == 1) {
                if (is_array($val)) {
                    $obj['msg'] = json_encode($val, JSON_UNESCAPED_UNICODE);
                } else {
                    if (empty($val)) $val = "";
                    $obj['msg'] = $val;
                }
            } elseif ($key == 2) {
                $obj['data'] = $val;
            } elseif ($key == 3) {
                $obj['url'] = $val;
            }
        }
        $json = json_encode($obj, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            $json = json_encode([
                'code' => 0,
                'msg' => '数据解析失败'
            ], JSON_UNESCAPED_UNICODE);
        }
        exit($json);
    }
}

/**
 * 获取import中的文件及参数传递
 * @return null
 */
if(!function_exists('import')) {
    function import()
    {
        $argnums = func_num_args();
        if ($argnums <= 0) return null;
        $args = func_get_args();
        $args0 = str_replace('/', '.', $args[0]);
        $args0 = str_replace('\\', '.', $args0);
        $classes = explode(".", $args0);
        $classname = $classes[count($classes) - 1];
        $classpath = implode("/", $classes);
        unset($args[0]);

        if (!class_exists($classname)) {
            $tpath = dirname(dirname(__DIR__)) . "/html/sys/import/{$classpath}";
            if (file_exists($tpath . ".php")) {
                $filepath = $tpath . ".php";
            } elseif (file_exists($tpath . ".class.php")) {
                $filepath = $tpath . ".class.php";
            } else {
                $b_path = base_path(get_tphp_html_path());
                $tpath = $b_path . $GLOBALS['DOMAIN_PATH']->base_tpl_path . "import/{$classpath}";
                if (file_exists($tpath . ".php")) {
                    $filepath = $tpath . ".php";
                } elseif (file_exists($tpath . ".class.php")) {
                    $filepath = $tpath . ".class.php";
                } else {
                    $tpath = $b_path . "sys/import/" . $classpath;
                    if (file_exists($tpath . ".php")) {
                        $filepath = $tpath . ".php";
                    } elseif (file_exists($tpath . ".class.php")) {
                        $filepath = $tpath . ".class.php";
                    } else {
                        return null;
                    }
                }
            }
            include_once $filepath;
        }
        if (!class_exists($classname)) {
            return null;
        }
        if (empty($args)) return new $classname();
        $argstr = "";
        foreach ($args as $key => $val) {
            if (empty($argstr)) {
                $argstr = "\$args[{$key}]";
            } else {
                $argstr .= ", \$args[{$key}]";
            }
        }
        $fun = [];
        eval("\$fun = new {$classname}({$argstr});");
        return $fun;
    }
}

// 载入PHP文件路径
if(!function_exists('import_load_dir')) {
    function import_load_dir($base_dir, $load_dirs)
    {
        $base_dir = rtrim(trim($base_dir), '/\\');
        if (!is_dir($base_dir)) {
            return false;
        }
        if (!empty($lnd) && isset($lnd[$base_dir])) {
            return false;
        }
        $base_dir .= "/";
        if (empty($load_dirs) || !is_array($load_dirs)) {
            return false;
        }
        foreach ($load_dirs as $key => $val) {
            if (!is_string($val)) {
                continue;
            }
            $val = str_replace("\\", "/", rtrim($val, '/\\'));
            $php_file = $base_dir . $val;
            $is_file = false;
            if (is_file($php_file)) {
                $is_file = true;
            } else {
                if (is_file($php_file . ".php")) {
                    $php_file .= '.php';
                    $is_file = true;
                } elseif (is_file($php_file . ".class.php")) {
                    $php_file .= '.class.php';
                    $is_file = true;
                }
            }
            if ($is_file) {
                include_once $php_file;
            }
        }
    }
}

if(!function_exists('_get_browser')) {
    function _get_browser()
    {
        $sys = $_SERVER['HTTP_USER_AGENT'];  //获取用户代理字符串
        if (stripos($sys, "Firefox/") > 0) {
            preg_match("/Firefox\/([^;)]+)+/i", $sys, $b);
            $exp[0] = "Firefox";
            $exp[1] = $b[1];  //获取火狐浏览器的版本号
        } elseif (stripos($sys, "Maxthon") > 0) {
            preg_match("/Maxthon\/([\d\.]+)/", $sys, $aoyou);
            $exp[0] = "傲游";
            $exp[1] = $aoyou[1];
        } elseif (stripos($sys, "MSIE") > 0) {
            preg_match("/MSIE\s+([^;)]+)+/i", $sys, $ie);
            $exp[0] = "IE";
            $exp[1] = $ie[1];  //获取IE的版本号
        } elseif (stripos($sys, "OPR") > 0) {
            preg_match("/OPR\/([\d\.]+)/", $sys, $opera);
            $exp[0] = "Opera";
            $exp[1] = $opera[1];
        } elseif (stripos($sys, "Edge") > 0) {
            //win10 Edge浏览器 添加了chrome内核标记 在判断Chrome之前匹配
            preg_match("/Edge\/([\d\.]+)/", $sys, $Edge);
            $exp[0] = "Edge";
            $exp[1] = $Edge[1];
        } elseif (stripos($sys, "Chrome") > 0) {
            preg_match("/Chrome\/([\d\.]+)/", $sys, $google);
            $exp[0] = "Chrome";
            $exp[1] = $google[1];  //获取google chrome的版本号
        } elseif (stripos($sys, 'rv:') > 0 && stripos($sys, 'Gecko') > 0) {
            preg_match("/rv:([\d\.]+)/", $sys, $IE);
            $exp[0] = "IE";
            $exp[1] = $IE[1];
        } else {
            $exp[0] = "未知浏览器";
            $exp[1] = "";
        }
        return [$exp[0], $exp[1]];
    }
}

if(!function_exists('is_ie')) {
    function is_ie()
    {
        list($name) = _get_browser();
        return $name == 'IE';
    }
}

/**
 * json_encode转换，适用于HTML中的JSON参数传递
 * @param string $obj
 * @return mixed|string
 */
if(!function_exists('json__encode')) {
    function json__encode($obj = '')
    {
        if (empty($obj)) return '{}';
        if (is_array($obj) || is_object($obj)) {
            $obj = json_encode($obj, JSON_UNESCAPED_UNICODE);
        } else {
            $obj = trim($obj);
        }
        $obj = str_replace("'", "&apos;", $obj);
        return $obj;
    }
}
if(!function_exists('mb_substr_chg')) {
    function mb_substr_chg($str, $length = 200, $is_chg = false)
    {
        if ($is_chg) {
            $str = mb_substr($str, 0, $length);
            $stack = [];
            $strlen = strlen($str);
            $newstr = "";
            for ($i = 0; $i < $strlen; $i++) {
                $si = $str[$i];
                if ($si == '<') {
                    $i++;
                    if ($i < $strlen) {
                        if ($str[$i] == "/") {
                            array_pop($stack);
                            for (; $i < $strlen; $i++) {
                                if ($str[$i] == '>') {
                                    break;
                                }
                            }
                        } else {
                            $iname = "";
                            $is_ok = true;
                            $is_rem_one = false;
                            $is_rem_two = false;
                            for (; $i < $strlen; $i++) {
                                if ($str[$i] == '"') {
                                    $is_rem_two = !$is_rem_two;
                                } elseif ($str[$i] == "'") {
                                    $is_rem_one = !$is_rem_one;
                                }
                                if ($str[$i] == '>') {
                                    break;
                                } elseif ($str[$i] == ' ') {
                                    $is_ok = false;
                                } elseif ($is_ok) {
                                    $iname .= $str[$i];
                                }
                            }
                            $is_push = true;
                            if ($str[$i] == '>') {
                                if ($str[$i - 1] == '/') {
                                    $is_push = false;
                                }
                            } else {
                                if ($i >= $strlen) {
                                    $is_rem_one && $str .= "'";
                                    $is_rem_two && $str .= '"';
                                    $str .= '>';
                                }
                            }
                            $is_push && array_push($stack, $iname);
                        }
                    }
                } else {
                    $newstr .= $si;
                    $i++;
                }
            }
            foreach ($stack as $s) {
                $str .= "</{$s}>";
            }
        } else {
            $str = preg_replace("/(<(?:\/*)[^>]*>)/i", "", $str);
            $str = mb_substr($str, 0, $length);
            $str = str_replace("<", "&lt;", $str);
            $str = str_replace(">", "&gt;", $str);
        }
        return $str;
    }
}

//设置cookie
if(!function_exists('__set_cookie')) {
    function __set_cookie($name, $value = "", $expire = 0)
    {
        if (empty($name)) {
            return;
        }
        if ($expire <= 0) {
            $expire = time() + 100 * 365 * 24 * 60 * 60;
        } else {
            $expire += time();
        }
        setcookie($name, $value, $expire, '/');
    }
}

//获取cookie
if(!function_exists('__get_cookie')) {
    function __get_cookie($name, $value = "")
    {
        if (empty($name)) {
            return "";
        }
        if (!isset($_COOKIE[$name])) {
            return $value;
        }
        return $_COOKIE[$name];
    }
}

if(!function_exists('trad')) {
    function trad()
    {
        ob_start("__ob_trad");
    }
}
