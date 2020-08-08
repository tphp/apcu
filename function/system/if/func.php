<?php

return function($data, $setdata = "", $if = "", $value = "", $trueset = "", $falseset = ""){
    $funlen = func_num_args();
    if(empty($if) || $funlen <= 4) return $data;
    if(empty($setdata)) $setdata = $data;
    $if = trim($if);
    $bool = false;
    $val = "";
    if(is_string($value) || is_numeric($value) || is_bool($value)) {
        $val = $value;
    }elseif(is_array($value) && !empty($value)){
        if(count($value) == 1){
            $val = $value[0];
        }else{
            $val = $value;
        }
    }

    if(is_string($val) || is_numeric($val) || is_bool($val)) {
        switch ($if) {
            case '>' :
                $bool = ($setdata > $val);
                break;
            case '>=' :
                $bool = ($setdata >= $val);
                break;
            case '=' :
            case '==' :
                $bool = ($setdata == $val);
                break;
            case '!' :
            case '!=' :
                $bool = ($setdata != $val);
                break;
            case '<' :
                $bool = ($setdata < $val);
                break;
            case '<=' :
                $bool = ($setdata <= $val);
                break;
            case '&&' :
                $bool = ($setdata && $val);
                break;
            case '||' :
                $bool = ($setdata || $val);
                break;
        }
    }elseif(is_array($val)){
        switch ($if) {
            case '>' :
                $bool = ($setdata > max($val));
                break;
            case '>=' :
                $bool = ($setdata >= max($val));
                break;
            case '=' :
            case '==' :
            case '||' :
                $bool = in_array($setdata, $val);
                break;
            case '!' :
            case '!=' :
                $bool = !in_array($setdata, $val);
                break;
            case '<' :
                $bool = ($setdata < min($val));
                break;
            case '<=' :
                $bool = ($setdata <= min($val));
                break;
            case 'in' : //范围之内
                if(count($val) >= 2){
                    $bool = ($setdata >= $val[0] && $setdata <= $val[1]);
                }
                break;
            case 'out' : //范围之外
                if(count($val) >= 2){
                    $bool = ($setdata < $val[0] && $setdata > $val[1]);
                }
                break;
        }
    }

    if($bool){
        return $trueset;
    }else if($funlen >= 6){
        return $falseset;
    }

	return $data;
};