<?php

return function($obj = null){
	if(empty($obj)) return null;
	return json_decode($obj, true);
};