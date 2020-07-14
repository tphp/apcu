<?php
return function($str, $len = 0, $string = "0") {
	return str_pad($str, $len, $string, STR_PAD_LEFT);
};