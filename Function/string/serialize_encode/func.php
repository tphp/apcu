<?php

return function($obj = null){
	if(empty($obj)) return null;
	if(is_array($obj)){
		return serialize($obj);
	}else{
		return null;
	}
};