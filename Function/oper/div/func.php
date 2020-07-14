<?php
return function($data, $num = 0){
	if($num == 0) return 0;
	return $data / $num;
};