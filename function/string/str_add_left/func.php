<?php
return function($str, $addstr) {
    empty($str) && $str = "";
    empty($addstr) && $addstr = "";
	return $addstr.$str;
};