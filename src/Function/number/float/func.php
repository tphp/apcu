<?php
return function($num, $bit = 2, $is_format = true, $is_round = false){
	if(!is_string($num) && !is_numeric($num)) {
	    return 0;
	}

    $num = $num * 1;
    list($int, $float) = explode(".", $num."");
    $fl = 5;
    if(strlen($float) > $fl && $float[$fl] > 0) {
        if($float[$fl] > 5) {
            $float = ('0.00000' . (10 - $float[$fl])) * 1;
        }else{
            $float = 0;
        }
        if($float > 0) {
            $num < 0 ? $num = $num * 1 - $float : $num = $num * 1 + $float;
        }
    }

	if($is_round || strtolower($is_round) == 'true') {
        $numstr = sprintf("%.{$bit}f", $num);
    }else{
	    if($bit > 0) {
            list($int, $float) = explode(".", $num."");
            empty($int) && $int = '0';
            $flen = strlen($float);
            if($flen > $bit){
                $float = substr($float, 0, $bit);
            }else {
                $float = str_pad($float, $bit, "0");
            }
            $numstr = $int . "." . $float;
        }
    }

    if($is_format === true || strtolower($is_format) == 'true'){
        if($numstr[0] == '-'){
            $start = "-";
        }else{
            $start = "";
        }
        list($int, $float) = explode(".", $numstr);
        $int = trim($int, '-');
        $int = number_format($int, "0", ".", ",");
        $numstr = $start.$int.".".$float;
    }

    return $numstr;
};