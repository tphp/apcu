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
		ob_start('ob_tpl_css');
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
		ob_start('ob_tpl_js');
		return $code;
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
