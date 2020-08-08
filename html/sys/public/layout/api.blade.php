<!DOCTYPE HTML>
@php
    $static_tphp = "/".config('path.static_tphp');
    $liburl = $static_tphp."api/dev/info/";
@endphp
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>@if(!empty($title)) {{$title}} - @endif API在线文档</title>
    <link href="{{$liburl}}api.css" rel="stylesheet" type="text/css"/>
    <script language="javascript" src="{{$static_tphp}}js/jquery/jquery.min.js"></script>
    <script language="javascript" src="{{$liburl}}jquery.dimensions.js"></script>
</head>
<body>
<div class="tit">
    <div id="titcont">
        API在线文档<span class="sma">URL: <a href="{{$url}}" target="_blank" style="color:#0FF; text-decoration: none;">{{$url}}</a></span>
    </div>
</div>
<div id="cont">
    @foreach($argslist as $key=>$val)
        <div class='fun'>
            <div class='lineface'>{{$key}}</div>
            <div class='says'>
                @foreach($val as $k=>$v)
                    <div>{{$k}} : {{$v}}</div>
                @endforeach
            </div>
        </div>
    @endforeach
    @if(!empty($remark))
        <div class='fun'>
            <div class='lineface'>接口说明</div>
            <div class='says'>
                <pre>{!! $remark !!}</pre>
            </div>
        </div>
    @endif
    <div class='fun'>
        <div class='lineface'>标题： {{$title}}</div>
        <span class='le'>示例URL:<em> <a href='{{$url_demo}}' target='_blank'>{{$url_demo}}</a> </em></span>
        <span class='ri'>提交方式:<em> {{$type}}</em></span>
        @if(!empty($result))
        <div class='says'>返回结构示例：
            <pre class="intersays">{!! empty($result) || $result == 'null' ? '无数据实例' : $result !!}</pre>
        </div>
        @endif
    </div>

</div>
</body>
</html>
