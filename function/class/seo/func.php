<?php

/**
 * 页面SEO重置
 */
return function($data){
	if(!class_exists('Seo')) {
		class __Seo {
			private static $config = null;

			//修改头部信息SEO设置TDK
			public static function seoRet($html) {
				preg_match("!<head>(.*?)</head>!ius",$html,$m);
				$config = self::$config;
				$flagk = "_##k##_"; //替换关键词标识
				$flagd = "_##d##_"; //替换描述标识
				//如果页面本来就没有head头，则直接返回
				if(!isset($m[0]) || $m[0]=="")
					return $html;

				$head = $m[1];
				if(!empty($config['title']))
				{
					$title = "<title>{$config['title']}</title>";
					if(preg_match('!<title>.*?</title>!',$head))
					{
						$head = preg_replace("!<title>.*?</title>!ui",$title,$head,1);
					}
					else
					{
						$head = $title.$head;
					}
				}

				if(!empty($config['keywords']))
				{
					$keywords = "<meta name='keywords' content='{$config['keywords']}' />";
					if(preg_match("!<meta\s.*?name=['\"]keywords!ui",$head))
					{
						$head = preg_replace("!<meta\s.*?name=['\"]keywords.*?/?>!ui",$flagk,$head,1);
					}
					else
					{
						$head = preg_replace("!</title>!ui",'</title>'.$flagk,$head,1);
					}
				}

				if(!empty($config['description']))
				{
					$description = "<meta name='description' content='{$config['description']}' />";
					if(preg_match("!<meta\s.*?name=['\"]description!ui",$head))
					{
						$head = preg_replace("!<meta\s.*?name=['\"]description.*?/?>!ui",$flagd,$head,1);
					}
					else
					{
						$head = preg_replace("!".$flagk."!ui", $flagk.$flagd,$head,1);
					}
				}

				if(!empty($config['keywords'])) $head = preg_replace("!".$flagk."!ui", $keywords, $head, 1);
				if(!empty($config['description'])) $head = preg_replace("!".$flagd."!ui", $description, $head, 1);
				$head = "<head>{$head}</head>";
				return preg_replace("!<head>(.*?)</head>!ius",$head,$html);
			}


			//设置头部TDK
			public static function seo($config, $use_bool=true) {
				if($use_bool){
					$config_info = self::$config;
					if(empty($config['title'])) $config['title'] = $config_info['title'];
					if(empty($config['keywords'])) $config['keywords'] = $config_info['keywords'];
					if(empty($config['description'])) $config['description'] = $config_info['description'];

					if(!empty($config['title']) && !empty($config_info['title'])){
						$config['title'] = $config['title'].'_'.$config_info['title'];
					}
				}
				self::$config = $config;
				ob_start('self::seoRet');
			}

			function __destruct(){
				ob_end_flush();
			}
		}
	}
	return $data;
};
