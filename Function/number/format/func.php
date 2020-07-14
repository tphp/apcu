<?php
return function($num, $decimals = 0, $dec_point = '.', $thousands_sep = ','){
	return number_format($num, $decimals, $dec_point, $thousands_sep);
};