<?php

namespace Apcu\Controllers;

/**
 * 分页生成页面
 * Class PageController
 * @package Apcu\Controllers
 */
class PageController
{
	public static $pages;

	private $pagepath = "sys/page";
	private $typesdef = 'default';

    /**
     * 设置URL保留参数
     * @param $pages
     * @param array $saveargs 保留参数
     * @param string $fragment 锚链接标记
     */
	private function setPageArgs($pages, $saveargs = [], $fragment = ''){
		if(!empty($saveargs)){
			$appends = [];
			if(is_array($saveargs)){
				foreach ($saveargs as $val){
					$val = trim($val);
					!empty($val) && !empty($_GET[$val]) && $appends[$val] = $_GET[$val];
				}
			}else{
				$saveargs = trim($saveargs);
				!empty($saveargs) && !empty($_GET[$saveargs]) && $appends[$saveargs] = $_GET[$saveargs];
			}
			!empty($appends) && $pages->appends($appends);
		}

		if(!empty($fragment) && (is_string($fragment) || is_numeric($fragment))){
			$fragment = trim($fragment);
			!empty($fragment) && $pages->fragment($fragment);
		}
	}

	/**
	 * 获取模板和数据端组合
	 * @param $type 分页模板配置
	 * @return array
	 */
	private function getTypeInfo($type){
		$viewpath = "{$this->pagepath}";
		$deftype = "{$viewpath}.{$this->typesdef}";
		$deftypepath = "{$viewpath}/{$this->typesdef}";
		if(empty($type)) return [$deftypepath, $deftype];
		if(is_string($type) || is_numeric($type)){ //如果是字符串则返回模板路径
			$type = strtolower(trim($type));
			$typetplpath = "{$viewpath}/{$type}";
			$typetpl = "{$viewpath}.{$type}";
			!view()->exists("{$typetpl}.tpl") && $typetpl = $deftype;
		}elseif(is_array($type)){ //如果是数组：第一个数组返回模板路径，第二个数组返回class样式路径
			$type0 = strtolower(trim($type[0]));
			$typetplpath = "{$viewpath}/{$type0}";
			$typetpl = "{$viewpath}.{$type0}";
			!view()->exists("{$typetpl}.tpl") && $typetpl = $deftype;

			$type1 = strtolower(trim($type[1]));
			if(!empty($type1) && $type0 != $type1){
				$typeclass = "{$viewpath}.{$type1}";
				$typeclasspath = "{$viewpath}/{$type1}";
				!view()->exists("{$typeclass}.tpl") && $typeclasspath = $deftypepath;
			}
		}

		if($typetpl == $typeclasspath || empty($typeclasspath)){
			return [$typetplpath, $typetpl];
		}else{
			return [$typetplpath, $typetpl, $typeclasspath];
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
	public function page($type = null, $saveargs = [], $fragment = '', $onEachSide = 0){
		if(empty(self::$pages)) return '';
		$pages = clone self::$pages;
		if(empty($pages)) return [];

		//设置URL保留参数
        empty($saveargs) && $saveargs = [];
        !in_array("psize", $saveargs) && $saveargs[] = "psize";
		$this->setPageArgs($pages, $saveargs, $fragment);

		list($typetplpath, $typetpl, $typeclass) = $this->getTypeInfo($type);
		$tplpath = "{$typetpl}.tpl";
		if($onEachSide <= 0) { //默认为系统条数处理
			$html = $pages->links($tplpath, ['oneachside' => 3]);
		}else{ //自定义分页
			$window = \Illuminate\Pagination\UrlWindow::make($pages, $onEachSide);
			$elements = array_filter([
				$window['first'],
				is_array($window['slider']) ? '...' : null,
				$window['slider'],
				is_array($window['last']) ? '...' : null,
				$window['last'],
			]);

			$html = view($tplpath, [
				'paginator' => $pages,
				'elements' => $elements,
				'oneachside' => $onEachSide
			]);
		}
		unset($pages);
		$config = [
			'html' => $html
		];

		if(!empty($typeclass)){
			$config['class'] = $typeclass;
		}
		$html =  tpl("/".$typetplpath.".html", $config);
        $url_api = $_SERVER['URL_API'];
        if(!empty($url_api) && $_SERVER['HTTP_HOST'] == '127.0.0.1:880'){
            $url_api = trim($url_api, " /");
            if(!empty($url_api)){
                $local_url = "http://" . $_SERVER['HTTP_HOST'];
                if(strpos($url_api, "//") <= 0){
                    $url_api = "http://" . $url_api;
                }
                if($local_url != $url_api){
                    $html = str_replace($local_url, $url_api, $html);
                }
            }
        }
		return $html;
	}

}
