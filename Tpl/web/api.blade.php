<!DOCTYPE html>
<html>
@define $api_url = url($api_path)
@define $static = url(config('path.static'))."/api/"
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>API : {{$api_path}}</title>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
    <link rel="stylesheet" href="{{$static.'css/bootstrap.min.css'}}">
    <link rel="stylesheet" href="{{$static.'css/font-awesome.min.css'}}">
    <link rel="stylesheet" rev="stylesheet" href="{{$static.'css/style.css'}}" media="all"/>
    <link rel="stylesheet" href="{{$static.'css/AdminLTE.min.css'}}">
    <link rel="stylesheet" href="{{$static.'css/skin-black-light.css'}}">
    <link rel="stylesheet" href="{{$static.'css/json.css'}}">
    <link rel="stylesheet" href="{{$static.'css/BeAlert.css'}}">
    <script type="text/javascript">
        baseuri = "{{$static}}";
        apiurl = "{{$api_url}}.json";
    </script>
    <script src="{{$static.'js/jquery-1.12.3.min.js'}}" type="text/javascript"></script>
    <script src="{{$static.'js/BeAlert.js'}}" type="text/javascript"></script>
    <script src="{{$static.'js/marked.js'}}" type="text/javascript"></script>
    <script src="{{$static.'js/prettify.js'}}" type="text/javascript"></script>
    <script src="{{$static.'js/json.js'}}"></script>
</head>
<body class="hold-transition skin-black-light fixed sidebar-mini">
<div class="wrapper">
    <header class="main-header">
        <a href="" class="logo">
            <span class="logo-mini">
				API
            </span>
            <span class="logo-lg">
                API接口调试
            </span>
        </a>
        <nav class="navbar navbar-static-top" role="navigation">
            <table style="width: 100%">
                <tr>
                    <td width="20px">
                        <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
                            <span class="sr-only">Toggle navigation</span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                            <span class="icon-bar"></span>
                        </a>
                    </td>
                    <td>
                        <input type="text" class="url_show" readonly="readonly"/>
                    </td>
                    <td width="200px">
                        <div class="navbar-custom-menu">
                            <ul class="nav navbar-nav">
                                <li>
                                    <a href="javascript:void(0)" id="url_copy">复制路径</a>
                                </li>
                                <li>
                                    <a href="{{$api_url}}.json" target="_blank">API</a>
                                </li>
                                <li>
                                    <a href="{{$api_url}}.html" target="_blank">模板</a>
                                </li>
                            </ul>
                        </div>
                    </td>
                </tr>
            </table>

            <div id="ControlsRow" style="width: 100%">
                <input class="button white medium js_get_post" type="Button" value="获取数据"/>
                <input class="button white medium js_get_post_copy" type="Button" value="复制"/>
                <span id="TabSizeHolder">
                    缩进量
                    <select id="TabSize" onchange="TabSizeChanged()">
                        <option value="1">1</option>
                        <option value="2" selected="true">2</option>
                        <option value="3">3</option>
                        <option value="4">4</option>
                        <option value="5">5</option>
                        <option value="6">6</option>
                    </select>
                </span>
                <label for="QuoteKeys"><input type="checkbox" id="QuoteKeys" onclick="QuoteKeysClicked()" checked="true"/>引号</label>&nbsp;
                <a href="javascript:void(0);" onclick="SelectAllClicked()">全选</a>&nbsp;
                <span id="CollapsibleViewHolder">
                    <label for="CollapsibleView">
                        <input type="checkbox" id="CollapsibleView" onclick="CollapsibleViewClicked()" checked="true"/>显示控制
                    </label>
                </span>
                <span id="CollapsibleViewDetail">
                    <a href="javascript:void(0);" onclick="ExpandAllClicked()">展开</a>
                    <a href="javascript:void(0);" onclick="CollapseAllClicked()">叠起</a>
                    <a href="javascript:void(0);" onclick="CollapseLevel(3)">2级</a>
                    <a href="javascript:void(0);" onclick="CollapseLevel(4)">3级</a>
                    <a href="javascript:void(0);" onclick="CollapseLevel(5)">4级</a>
                    <a href="javascript:void(0);" onclick="CollapseLevel(6)">5级</a>
                    <a href="javascript:void(0);" onclick="CollapseLevel(7)">6级</a>
                    <a href="javascript:void(0);" onclick="CollapseLevel(8)">7级</a>
                    <a href="javascript:void(0);" onclick="CollapseLevel(9)">8级</a>
                </span>
            </div>
        </nav>
    </header>

    <aside class="main-sidebar">
        <section class="sidebar">
            <ul class="sidebar-menu">
                <li class="header">
                    <div class="name"><i class="fa fa-circle-o"></i><span>GET</span></div>
                    <div class="body get_values">
                        @if(empty($api_config['get']))
                            <label><span>没有参数</span></label>
                        @else
                            @foreach($api_config['get'] as $key=>$val)
                                <label>
                                    <span>{{$key}}@if(!empty($val[0])) ({{$val[0]}}) @endif：</span>
                                    <input type="text" name="{{$key}}" value="{{$val[1]}}"/>
                                </label>
                            @endforeach
                        @endif
                    </div>
                </li>
                <li class="header">
                    <div class="name"><i class="fa fa-circle-o"></i><span>POST</span></div>
                    <div class="body post_values">
                        @if(empty($api_config['post']))
                            <label><span>没有参数</span></label>
                        @else
                            @foreach($api_config['post'] as $key=>$val)
                                <label>
                                    <span>{{$key}}@if(!empty($val[0])) ({{$val[0]}}) @endif：</span>
                                    <input type="text" name="{{$key}}" value="{{$val[1]}}"/>
                                </label>
                            @endforeach
                        @endif
                    </div>
                </li>
                <li class="header">
                    <div class="name"><i class="fa fa-circle-o" style="color:#999;"></i><span style="color:#999;">API文档说明</span>
                    </div>
                    <div class="body" style="color:#999;">
                        @if(!empty($api_config['title']))
                            <div>【{{$api_config['title']}}】</div>
                        @endif
                        @if(empty($api_config['remark']))
                            <div style="margin-top: 10px;">暂无说明</div>
                        @else
                            <div style="margin-top: 10px;">{{$api_config['remark']}}</div>
                        @endif
                    </div>
                </li>
            </ul>
        </section>
    </aside>

    <div class="content-wrapper" style="background: #fff">
        <div class="HeadersRow">
            <textarea id="RawJson"></textarea>
        </div>
        <div id="Canvas" class="Canvas"></div>
    </div>

    <a id="gotop" href="#">
        <span>▲</span>
    </a>
</div>

<script src="{{$static.'js/bootstrap.min.js'}}"></script>
<script src="{{$static.'js/jquery.slimscroll.min.js'}}"></script>
<script src="{{$static.'js/app.min.js'}}"></script>
<script src="{{$static.'js/api.js'}}"></script>
</body>
</html>
