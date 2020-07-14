<?php
return function($file_url){
	if(file_exists($file_url)){
		unlink($file_url);
	}

	return $file_url;
};