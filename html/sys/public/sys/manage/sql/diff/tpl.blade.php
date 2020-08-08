<form class="layui-form" action="">
    <div class="layui-form-item">
        <div class="lfi-in">
            <label class="layui-form-label">数据库</label>
            <div class="layui-input-inline" style="width: 200px;">
                <select id="conn" lay-skin="switch" lay-filter="switchTest">
                    @foreach($dblist as $val)
                    <option value="{{$val}}" @if($conn == $val) selected="selected" @endif>{{$val}}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="layui-inline">
            <button class="layui-btn layui-btn-primary layui-btn-xs js_btn_save">备份字段信息</button>
            <button class="layui-btn layui-btn-primary layui-btn-xs js_btn_rest">还原字段信息</button>
            <button class="layui-btn layui-btn-primary layui-btn-xs js_btn_explain">使用说明</button>
            <button class="layui-btn layui-btn-primary layui-btn-xs js_btn_flush">刷新</button>
        </div>
    </div>
</form>
<div id="id_explain" style="display: none">
    <div style="padding: 10px; background-color: #F8F8F8; min-height: calc(100% - 20px);">
        <div class="layui-row layui-col-space15">
            <div class="layui-col-md12">
                <div class="layui-card">
                    <div class="layui-card-header">操作说明</div>
                    <div class="layui-card-body">
                        <div>支持类型： Mysql、 Sqlserver、 PostgreSQL、 SQLite</div>
                        <div>备份字段信息： 将字段结构保存到文件</div>
                        <div>还原字段信息： 获取文件备份信息并还原到数据库</div>
                        <div>仅字段同步，表数据保持不变</div>
                        <div style="color: #F33;">不支持字段名称更改，相当于删除字段后新建字段，该字段的数据无法保存，谨慎使用需要更改字段名称操作。</div>
                    </div>
                </div>
            </div>
            <div class="layui-col-md12">
                <div class="layui-card">
                    <div class="layui-card-header">Mysql</div>
                    <div class="layui-card-body">
                        Mysql8版本字段长度和其他版本有所差异，同步时长度显示不一致，但不影响使用。
                    </div>
                </div>
            </div>
            <div class="layui-col-md12">
                <div class="layui-card">
                    <div class="layui-card-header">Sqlserver</div>
                    <div class="layui-card-body">
                        当表存在且数据不为空时不支持设置自增字段
                    </div>
                </div>
            </div>
            <div class="layui-col-md12">
                <div class="layui-card">
                    <div class="layui-card-header">SQLite</div>
                    <div class="layui-card-body">
                        还原字段信息时确保SQLite文件未被锁定（文件正在使用中），否则将会出现死锁现象导致不能进行操作。
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="layui-table-view" style="margin: 10px; border: none">
    @if($status)
        <table class="layui-hide" id="list" data-sqls='{{json__encode($sqls)}}'></table>
    @else
        {{$msg}}
    @endif
</div>