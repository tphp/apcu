<!DOCTYPE HTML>
@php
    $static_tphp = "/".config('path.static_tphp');
@endphp
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>错误信息</title>
</head>
<body>
<link rel="stylesheet" href="{{url($static_tphp.'oa/css/err.css')}}"/>
<div class="prompt_container" >
    <div class="prompt_frame">
        <div class="prompt_centerL">
            <div class="prompt_centerR">
                <div class="prompt_centerC">
                    <div class="prompt_container clearfloat">
                        <div>
                            <div class="prompt_content_error"></div>
                            <div class="prompt_content_right">
                                <div class="prompt_content_inside">
                                    <div class="msgtitle">
                                        {{$msg}}
                                        <span class="prompt_content_timer">
											请尝试其他查询条件
										</span>
                                    </div>
                                    <div class="msgcontent">
                                        <div>可能原因：</div>
                                        <div class="msgtip">
                                            <ul>
                                                <li>没有查看权限</li>
                                                <li>记录为空</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
