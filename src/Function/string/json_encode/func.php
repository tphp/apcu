<?php

return function($obj = null){
	if(empty($obj)) return null;
	if(is_object($obj)){
		return json_encode($obj);
	}elseif(is_array($obj)){
		return json_encode($obj, true);
	}else{
		return null;
	}
};