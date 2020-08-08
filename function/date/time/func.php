<?php

return function($date = null){
	if(empty($date)) return time();

	if(is_numeric($date)) return $date;

	return strtotime($date);
};