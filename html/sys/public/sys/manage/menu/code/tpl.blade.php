{{--{!! dd($info) !!}--}}
@define $static_tphp = config('path.static_tphp')
@if(empty($_['url']))
    <div class="red" style="margin: 20px;">请设置模块链接后再操作！</div>
@else
    <div class="layui-tab layui-tab-card">
        <ul class="layui-tab-title">
            @foreach($info as $key=>$val)<li @if($key == 'init')class="layui-this"@endif data-id="{{$key}}">{{$val['name']}}</li>@endforeach
        </ul>
        <div class="layui-tab-content">
            @foreach($info as $key=>$val) <div class="layui-tab-item @if($key == 'init') layui-show @endif"><pre class="js_code" id="{{$key}}" data-file="{{$val['file']}}" data-type="{{$val['type']}}">{{ $val['value'] }}</pre></div> @endforeach
        </div>
    </div>
    <div class="js_btn_left">
        <button class="layui-btn layui-btn-primary layui-btn-sm js_btn_flush">刷新</button>
        <button class="layui-btn layui-btn-primary layui-btn-sm js_btn_close">关闭 (Alt+q)</button>
        <button class="layui-btn layui-btn-primary layui-btn-sm js_btn_change" data-id="ini" data-url="{{$ini_url}}" data-sys-function-path="{{$sys_function_path}}">配置函数查询</button>
        <span style="color:#666; margin-left: 10px;">左右切换：Alt+z/x</span>
    </div>
    <div class="js_btn_right" data-id="{{$id}}">
        <button class="layui-btn layui-btn-normal layui-btn-sm js_btn_save">保存当前 (Ctrl+S)</button>
        <button class="layui-btn layui-btn-primary layui-btn-sm js_btn_save_all">保存全部</button>
        <button class="layui-btn layui-btn-primary layui-btn-sm js_btn_save_view" data-url="{{$url_show}}">查看页面</button>
    </div>
    <script src="{{url($static_tphp.'js/ace/ace.js')}}"></script>
    <script src="{{url($static_tphp.'js/ace/ext-language_tools.js')}}"></script>
@endif
