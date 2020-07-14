<?php

return function($obj = null){
	if(empty($obj)) return null;
	if(is_string($obj)){
		return unserialize($obj);
	}else{
		return null;
	}
};