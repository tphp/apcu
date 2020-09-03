@php
    $static_tphp = config('path.static_tphp')
@endphp
<div class="layui-collapse" style="border-top: none; border-left: none; border-right: none">
@foreach($_ as $key=>$val)
    <div class="layui-colla-item">
        <h2 class="layui-colla-title" style="color:#000; background-color: #FFF;">{{$val['_name_']}}</h2>
        <div class="layui-colla-content layui-show">
        @php
            $val_args = $val['_args_'];
            $val_flag = $val['_flag_'];
            $filepath = "/".$sysnote[$val_flag]['path']
        @endphp
        @if(!empty($val_flag))
            <div class="css-main-inner">
                <p>调用名称： {{$val_flag}}</p>
                <p>路径： {{$filepath}}</p>
                @if(is_string($val_args))
                    <p>{{$val_args}}</p>
                @elseif(is_array($val_args))
                    @foreach($val_args as $kk=>$vv)
                        @if(is_numeric($kk))
                            <p>{{$vv}}</p>
                        @elseif(is_string($vv))
                            <p>{{$kk}}: {{$vv}}</p>
                        @elseif(is_array($vv))
                            <p>{{$kk}}: </p>
                            @foreach($vv as $kkk=>$vvv)
                                <p>&nbsp;&nbsp;&nbsp;&nbsp;{{$kkk}}: @if(is_bool($vvv)) @if($vvv) true @else false @endif @else {{$vvv}} @endif</p>
                            @endforeach
                        @endif
                    @endforeach
                @endif
                <p><a class="js_view_code" data-name="{{$val_flag}}" data-md5-name="{{substr(md5($val_flag), 12, 8)}}" href="javascript:;">点击查看代码</a></p>
            </div>
        @endif
        @if(!empty($val['_next_']) && is_array($val['_next_']))
            <div class="layui-collapse">
            @foreach($val['_next_'] as $k=>$v)
                @php
                    $v_args = $v['_args_'];
                    $v_flag = $v['_flag_'];
                    $filepath = "/".$sysnote[$v_flag]['path']
                @endphp
                @if(!empty($v_flag))
                    <div class="layui-colla-item">
                        <h2 class="layui-colla-title">{{$v['_name_']}} @if(!empty($v['_next_'])) <a class="js_view_next" data-next-url="{{$filepath}}" href="javascript:;">更多&gt;&gt;</a> @endif</h2>
                        <div class="layui-colla-content">
                            <p>调用名称： {{$v_flag}}</p>
                            <p>路径： {{$filepath}}</p>
                            @if(is_string($v_args))
                                <p>{{$v_args}}</p>
                            @elseif(is_array($v_args))
                                @foreach($v_args as $kk=>$vv)
                                    @if(is_numeric($kk))
                                        <p>{{$vv}}</p>
                                    @elseif(is_string($vv))
                                        <p>{{$kk}}: {{$vv}}</p>
                                    @elseif(is_array($vv))
                                        <p>{{$kk}}: </p>
                                        @foreach($vv as $kkk=>$vvv)
                                            <p>&nbsp;&nbsp;&nbsp;&nbsp;{{$kkk}}: @if(is_bool($vvv)) @if($vvv) true @else false @endif @else {{$vvv}} @endif</p>
                                        @endforeach
                                    @endif
                                @endforeach
                            @endif
                            <p><a class="js_view_code" data-name="{{$v_flag}}" data-md5-name="{{substr(md5($v_flag), 12, 8)}}" href="javascript:;">点击查看代码</a></p>
                        </div>
                    </div>
                @endif
            @endforeach
            </div>
        @endif
        </div>
    </div>
@endforeach
</div>
<script>
    var code_url = "{{$code_url}}";
    var is_menu_ini = true;
</script>
<script src="{{url($static_tphp.'js/ace/ace.js')}}"></script>
<script src="{{url($static_tphp.'js/ace/ext-language_tools.js')}}"></script>
