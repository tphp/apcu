@if(!$is_post)
<div class="nav">
    <button class="layui-btn layui-btn-primary layui-btn-xs layui-git js_git_status">查看GIT状态</button>
    <button class="layui-btn layui-btn-primary layui-btn-xs layui-git js_git_pull">GIT下拉</button>
    <button class="layui-btn layui-btn-primary layui-btn-xs js_flush">刷新</button>
</div>
<div class="main">
    <div class="text">
@endif
@foreach($list as $text)
    <p>{!! $text !!}</p>
@endforeach
@if(!$is_post)
    </div>
</div>
@endif