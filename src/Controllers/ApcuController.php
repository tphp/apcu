<?php

namespace Tphp\Apcu\Controllers;
class ApcuController
{

	public function __construct()
	{
		$os = strtolower(PHP_OS);
		if($os == 'linux'){
			$this->iswin = false;
		}else{
			$this->iswin = true;
		}
        $this->xstring = import('XString');
	}

	/**
	 * 获取进程端口
	 */
	private function getPorts(){
		exec("ps -aux | grep php-cgi | grep -v grep", $list);
		$ports = [];
		foreach ($list as $key=>$val){
			$ts = $this->xstring->getSubStr($val, "php-cgi");
			$ts = $this->xstring->getSubStr($ts, ":");
			!empty($ts) && $ports[] = $ts;
		}

		sort($ports);

		return $ports;
	}

	/**
	 * 首页
	 * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
	 */
	public function indexUpdate(){
		if($this->iswin){
			$this->updateApcu();
			echo "Apcu已更新";
		}else{
			$ports = $this->getPorts();
			return $ports;
		}
	}

	/**
	 * 当程序运行时不存在方法时自动更新
	 * @return mixed
	 */
	public function getApcu(){
        $time = \Cache::get(APCU_SET_TIME_CACHE);
	    empty($time) && $time = time();
		apcu_clear_cache();
		// TPHP系统函数
        $this->funcpath = dirname(dirname(__DIR__))."/function";
        list($ret, $retfunc, $msgs) = $this->getFunc($this->funcpath);
        // 用户自定义函数
        $this->funcpath = TPHP_TPL_PATH."/sys/function";
        if(is_dir($this->funcpath)){
            list($ret, $retfunc, $msgs) = $this->getFunc($this->funcpath, $ret, $retfunc, $msgs);
        }
		unset($ret['class']); //class不展示到菜单列表
		apcu_store('_sysmenu_', $ret);
		apcu_store('_sysnote_', $retfunc);
		apcu_store(APCU_SET_TIME_CACHE, $time);
        \Cache::forever(APCU_SET_TIME_CACHE, $time);
		return $msgs;
	}

	/**
	 * 更新Apcu
	 */
	private function updateApcu(){
		$msgs = $this->getApcu();
		if(!empty($msgs) && ($_GET['msg'] == 'true' || $this->iswin)){
			echo "<BR><BR><div style='font-size: 12px;'>";
			foreach ($msgs as $val){
				echo $val."<BR>";
			}
			echo "</div><BR>";
		}
	}

	public function portRet(){
        $request = Request();
		$this->updateApcu();
		if($this->iswin){ //当为window时不只更新一次
			return "Apcu已更新";
		}else {
			$port = $request->port;
			return "端口为<span style='color:#F00'>{$port}</span>的php-cgi已更新!";
		}
	}

	private function getHtml($url){
		$ch = curl_init($url) ;
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true) ; // 获取数据返回
		curl_setopt($ch, CURLOPT_BINARYTRANSFER, true) ; // 在启用 CURLOPT_RETURNTRANSFER 时候将获取数据返回
		curl_setopt($ch, CURLOPT_TIMEOUT, 1);
		$html = curl_exec($ch) ;
		curl_close($ch);
		return $html;
	}

	/**
	 * 逐个php-cgi进程更新
	 */
	public function update(){
        $request = Request();
		if($this->iswin){ //当为window时只更新一次
			$this->updateApcu();
			echo "Apcu已更新";
		}else {
			$host = "http://".$_SERVER['HTTP_HOST']."/apcu/";
			$ports = $this->getPorts();

			$portslen = count($ports);

			foreach ($ports as $k=>$v) {
				$data = $this->getHtml($host.$v);
				if(empty($data)){
					$request->port = $v;
					$data = $this->portRet($request);
				}

				if($k < 10){
					$port = "0".$k;
				}else{
					$port = $k;
				}
				echo "{$port}/{$portslen} ： {$data}<BR>";
			}
		}
	}


	private function getFunc($path, &$ret = [], &$retfunc = [], &$msgs = []){
		$tfiles = [];
		$dirs = [];
		foreach(scandir($path) as $val) {
			if($val == '.' || $val == '..') continue;
			if(is_dir($path.'/'.$val)) {
				$dirs[] = $val;
			} else {
				$tfiles[] = $val;
			}
		}

		if(!empty($tfiles)) { //文件类型
			$keyflag = "";
			$func = null;
			$keyname = "";
			$note = "";
			$args = "";
			foreach ($tfiles as $val) {
				$pt = $path.'/'.$val;
				if ($val == 'name') {
					$keyname = FileController::readFile($pt);
				}elseif($val == 'ini.php'){
					$tarr = include $pt;
					$keyname = $tarr['name'];
					$keyflag = $tarr['flag'];
					$note = $tarr['note'];
					$args = $tarr['args'];
				}elseif($val == 'func.php'){
					$func = include $pt;
				}
			}
			$keyflag = strtolower($keyflag);
			if(!empty($keyflag) && !empty($func)){
				apcu_store($keyflag, $this->getClosure($func));
			}

			if(!empty($keyname)){
				$indpath = str_replace($this->funcpath, "", $path);
				$indpath = trim($indpath, '/');
				$indarr = explode("/", $indpath);
				$indcot = count($indarr);
				if($indcot > 0){
					$ind = "";
					for($i = 0; $i < $indcot - 1; $i ++){
						$ind .= "['{$indarr[$i]}']['_next_']";
					}

					$ind .= "['{$indarr[$indcot - 1]}']";
					eval("\$ret{$ind}['_name_'] = \$keyname;");
					if(!empty($args)) eval("\$ret{$ind}['_args_'] = \$args;");
					if(!empty($keyflag)){
						eval("\$ret{$ind}['_flag_'] = \$keyflag;");
						if(empty($retfunc[$keyflag])){
							$retfunc[$keyflag] = [
								'path' => $indpath,
								'func' => 'f_'.substr(md5($indpath), 8, 16)
							];
							if(!empty($note)) $retfunc[$keyflag]['note'] = $note;
						}else{
							$msgs[] = $retfunc[$keyflag]['path']."和{$indpath}的标志'{$keyflag}'重复！";
						}
					}
				}
			}
		}

		foreach ($dirs as $val){ //文件夹类型
			$this->getFunc($path.'/'.$val, $ret, $retfunc, $msgs);
		}

		return [$ret, $retfunc, $msgs];
	}

	/**
	 * 输出匿名函数代码
	 * @param $closure
	 */
	private function getClosure($closure) {
		try {
			$func = new \ReflectionFunction($closure);
		} catch (\ReflectionException $e) {
			echo $e->getMessage();
			return;
		}

		$start = $func->getStartLine() - 1;
		$end =  $func->getEndLine() - 1;
		$filename = $func->getFileName();

		$code = implode("", array_slice(file($filename),$start, $end - $start + 1));
		$code = $this->xstring->getSubStr($code, "function");
		return $code;
	}
}
