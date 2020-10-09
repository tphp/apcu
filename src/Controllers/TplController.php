<?php

namespace Tphp\Apcu\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Tphp\Apcu\Controllers\JSPacker;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use think\Request;

class TplController extends Controller {

	public function __construct() {
		$tplbase = get_tphp_html_path();
		$this->tplbase = base_path($tplbase); //TPL根目录
		$this->tpl_cache = env("TPL_CACHE");
		if(!is_bool($this->tpl_cache)) $this->tpl_cache = false;
	}

	/**
	 * 模板字符转换
	 * @param $filepath 文件路径
	 * @param string $class 模板名
	 * @return mixed|string
	 */
	private function getFileTextIn($filepath, $class = ""){
		$flag = "_CLASS_"; //模板使用标记
		$chgstr = "#_0&197~"; //替换符号，确保文件中不会使用到的字符串
		$str = trim(FileController::readFile($filepath));
		$str = str_replace("$".$flag, $chgstr, $str); //当文件中存在$_CLASS_字符串则输出为_CLASS_
		$str = str_replace($flag, ".".$class, $str);
		$str = str_replace($chgstr, $flag, $str);
		return $str;
	}

	private function getFileText($list, $type = 'css', $tplname = ""){
		if(empty($list)) return "";
		$str = "";
		$remark_l = "/************  ";
		$remark_r = "  ************/";
		$tphp_path = TPHP_PATH . "/html/";
		if($type == 'css'){
			foreach ($list as $key=>$val){
				if(empty($tplname)){
					$class = str_replace(".", "_", $val);
				}else{
					$class = str_replace(".", "_", $tplname);
				}
                $tstr = "";
				$class = str_replace("/", "_", $class);
				$val_path = str_replace(".", "/", $val);
                $filepath = $tphp_path.$val_path."/tpl.css";
                if(is_file($filepath)){
                    $tstr .= $this->getFileTextIn($filepath, $class)."\n\n";
                }
                $filepath = $tphp_path.$val_path."/tpl.scss";
                if(is_file($filepath)){
                    $tstr .= scss($this->getFileTextIn($filepath, $class));
                }
				$filepath = $this->tplbase.$val_path."/tpl.css";
				if(is_file($filepath)){
					$tstr .= $this->getFileTextIn($filepath, $class)."\n\n";
				}
				$filepath = $this->tplbase.$val_path."/tpl.scss";
				if(is_file($filepath)){
					$tstr .= scss($this->getFileTextIn($filepath, $class));
				}
				if(!empty($tstr)) {
					if (!$this->tpl_cache) {
						if (empty($tplname)) {
							$tstr = "{$remark_l}{$val}{$remark_r}\n" . $tstr;
						} else {
							$tstr = "{$remark_l}top: {$val}{$remark_r}\n" . $tstr;
						}
					}
					$str .= $tstr;
				}
			}
		}else{
			foreach ($list as $key=>$val){
				if(empty($tplname)){
					$class = str_replace(".", "_", $val);
				}else{
					$class = str_replace(".", "_", $tplname);
				}
				$class = str_replace("/", "_", $class);
                $val_path = str_replace(".", "/", $val);
                $jsstr = "";
                $filepath = $tphp_path . $val_path . "/tpl.js";
                if(is_file($filepath)) {
                    $jsstr .= $this->getFileTextIn($filepath, $class);
                }
                $filepath = $this->tplbase . $val_path . "/tpl.js";
                if(is_file($filepath)) {
                    $jsstr .= $this->getFileTextIn($filepath, $class);
                }
				$jsstr = trim($jsstr, ";");
				!empty($jsstr) && $jsstr = $jsstr.";";
				if(!empty($jsstr)) {
					if ($this->tpl_cache) {
                        $tstr = trim($jsstr).";";
                    }else{
                        $tstr = $jsstr."\n\n";
						if (empty($tplname)) {
							$tstr = "{$remark_l}{$val}{$remark_r}\n" . $tstr;
						} else {
							$tstr = "{$remark_l}down: {$val}{$remark_r}\n" . $tstr;
						}
					}
					$str .= $tstr;
				}
			}
		}

		$str = trim($str)."\n\n";
		if(!empty($tplname)){
			$str .= "\n\n";
		}
		return $str;
	}

	/**
	 * css压缩
	 * @param $buffer
	 * @return mixed
	 */
	private function cssZip($buffer) {
		$buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
		$buffer = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $buffer);
		return $buffer;
	}

    private static function obTplCss($code){
        header('Content-type:text/css');
        return $code;
    }

    private static function obTplJs($code){
        header('Content-type:application/x-javascript');
        return $code;
    }

	public function css(){
        $request = Request();
		$md5 = $request->md5;
		$ini = Cache::get("css_t_{$md5}");
		$thistplarr = Cache::get("css_t_{$md5}_type");
		$thistplname = Cache::get("{$md5}_tpl");
		if(defined("BASE_TPL_PATH")){
            $thistplname = BASE_TPL_PATH."_".$thistplname;
        }elseif(defined("BASE_TPL_PATH_TOP")){
            $thistplname = BASE_TPL_PATH_TOP."_".$thistplname;
        }
		if(empty($ini) && empty($thistplarr)) throw new NotFoundHttpException;

		if($this->tpl_cache){
			$cacheid = "css_{$md5}";
			$codes = Cache::get($cacheid);
			if($codes['tag']){
				$code = $codes['code'];
			}else{
				$codetop = $this->getFileText($thistplarr, 'css', $thistplname);
				$code = $codetop.$this->getFileText($ini);
				$code = $this->cssZip($code);
				Cache::put($cacheid, [
					'tag' => true,
					'code' => $code
				], 60 * 60);
			}
		}else{
			$codetop = $this->getFileText($thistplarr, 'css', $thistplname);
			$code = $codetop.$this->getFileText($ini);
		}
		ob_start('self::obTplCss');
		return $code;
	}

	public function js(){
        $request = Request();
		$md5 = $request->md5;
		$ini = Cache::get("js_t_{$md5}");
		$thistplarr = Cache::get("js_t_{$md5}_type");
		$thistplname = Cache::get("{$md5}_tpl");
        if(defined("BASE_TPL_PATH")){
            $thistplname = BASE_TPL_PATH."_".$thistplname;
        }elseif(defined("BASE_TPL_PATH_TOP")){
            $thistplname = BASE_TPL_PATH_TOP."_".$thistplname;
        }
		if(empty($ini) && empty($thistplarr)) throw new NotFoundHttpException;

		if($this->tpl_cache){
			$cacheid = "js_{$md5}";
			$codes = Cache::get($cacheid);
			if($codes['tag']){
				$code = $codes['code'];
			}else{
				$codedown = $this->getFileText($thistplarr, 'js', $thistplname);
				$code = $this->getFileText($ini, 'js').$codedown;
				$js = new JSPacker($code);
				$code = $js->pack();
				Cache::put($cacheid, [
					'tag' => true,
					'code' => $code
				], 60 * 60);
			}
		}else{
			$codedown = $this->getFileText($thistplarr, 'js', $thistplname);
			$code = $this->getFileText($ini, 'js').$codedown;
		}
		ob_start('self::obTplJs');
		return $code;
	}

	// 获取相对路径
	private function getRelativePath($urla, $urlb){
        $a_dirname = dirname($urla);
        $b_dirname = dirname($urlb);
        $a_dirname = trim($urla,"/");
        $b_dirname = trim($urlb,"/");
        $a_arr = explode("/", $a_dirname);
        $b_arr = explode("/", $b_dirname);
        $count = 0;
        $num = min(count($a_arr) ,count($b_arr));
        for ($i = 0; $i < $num; $i ++)
        {
            if ($a_arr[$i] == $b_arr[$i]){
                unset($a_arr[$i]);
                $count ++;
            }
            else{
                break;
            }
        }
        $relativepath=str_repeat("../", $count).implode("/", $a_arr);
        return $relativepath;
    }

    /**
     * 删除软连接，兼容Windows和Linux
     * @param $path
     */
    private function unLink($path){
        if(PHP_OS == 'WINNT'){
            $path = str_replace("/", "\\", $path);
            if(is_file($path)){
                unlink($path);
            }else{
                @rmdir($path);
            }
        }else{
            unlink($path);
        }
    }
	
	// 全局插件软连接
	public function plugins($user='', $dir=''){
	    if(empty($user) || empty($dir)){
	        abort(404);
        }
        $sys_plugins_path = TPHP_TPL_PATH."/sys/plugins/{$user}/{$dir}/static";
        $user_path = public_path("/static/plugins/{$user}/");
        $link_file = $user_path.$dir;
        $xfile = import('XFile');
        $readlink_path = '';
        try{
            $readlink_path = readlink($link_file);
        }catch (\Exception $e){
            // TODO
        }
	    if(!is_dir($sys_plugins_path)){
            if(!empty($readlink_path)){
                $this->unLink($link_file);
            }
            abort(404);
        }

	    $gitignore = public_path('/static/plugins/.gitignore');
	    if(!is_file($gitignore)){
            $xfile->write($gitignore, "*\n!.gitignore");
            if(!is_file($gitignore)){
                abort(501, '无权限创建文件');
            }
        }
	    if(!is_dir($user_path)){
            $xfile->mkDir($user_path);
            if(!is_dir($user_path)){
                abort(501, '无权限创建文件夹');
            }
        }
        if(is_file($link_file)){
            $xfile->delete($link_file);
        }
	    if(is_dir($sys_plugins_path)){
	        $relative_path = $this->getRelativePath($sys_plugins_path, $link_file);
            if(PHP_OS == 'WINNT'){
                // Windows系统必须转化为反斜杠，否则有可能目录访问出错
                $relative_path = str_replace("/", "\\", $relative_path);
            }
	        if(empty($readlink_path)){
                symlink($relative_path, $link_file);
            }elseif($relative_path != $readlink_path){
                $this->unLink($link_file);
                symlink($relative_path, $link_file);
            }else{
                abort(404);
            }
            // 创建软连接后继续重定向当前页面
            redirect($_SERVER['REQUEST_URI'])->send();
        }else{
            abort(404);
        }
    }

	//图标设置
	public function ico(){
	    $icon_path = $GLOBALS['DOMAIN_CONFIG']['icon'];
	    if(!empty($icon_path)){
            $icon_path = trim(trim($icon_path), '/\\');
        }
        if(empty($icon_path)){
            $icon_path = "static/icon/favicon.ico";
        }
        $pos = strrpos($icon_path, ".");
	    $ext = "";
	    if($pos > 0){
	        $ext = strtolower(substr($icon_path, $pos + 1));
        }
        if(!in_array($ext, ['png', 'jpg', 'png', 'ico'])){
	        return;
        }
        $public_path = rtrim(public_path(), '/\\')."/";
	    $file = $public_path.$icon_path;
	    if(!is_file($file)){
	        return;
        }
        if($ext === 'ico'){
            $ext = 'x-icon';
        }elseif($ext == 'jpg'){
            $ext = 'jpeg';
        }
        header('Content-type: image/'.$ext);
        readfile($file);
    }
}
