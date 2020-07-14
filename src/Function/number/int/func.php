<?php
return function($num){
	if(is_string($num) || is_numeric($num)) {
		return (int)$num;
	}else{
		return 0;
	}
};