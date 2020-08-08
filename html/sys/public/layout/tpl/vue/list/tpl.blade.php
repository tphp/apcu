@php
    empty($search[0]) ? $is_search = false : $is_search = true;
@endphp

@if(!empty($list))
@php
    $list_count = count($list);
    $list_inc = 0;
    if(is_array($ext)){
        $ext_total = count($ext);
    }else{
        $ext_total = 0;
    }
    $btn_cot = 0;
    if($ext_total > 0){
        $btn_cot++;
    }
    if($form_info['view'] > 0){
        $btn_cot++;
    }
    if($is_delete){
        $btn_cot++;
    }
    $oper_width = 36 * $btn_cot + 20;
    $form_info['edit'] > 0 ? $edit_name = '编辑' : $edit_name = '查看';
@endphp
<div id="{{$app_list_name}}" class="css_app_list" v-show="show">
    <div class="css_list_batch">
        @if($form_info['add'] > 0) <el-button size="small" type="primary" icon="el-icon-plus" @click="add">新增</el-button> @endif
        <el-button size="small" icon="el-icon-refresh" @click="get_list">刷新</el-button>
    </div>
    <el-pagination
            @size-change="page_size"
            @current-change="page_current"
            :current-page="pagination.page"
            :page-sizes="[10, 20, 50, 100]"
            :page-size="pagination.size"
            layout="total, sizes, prev, pager, next, jumper"
            :total="pagination.total">
    </el-pagination>
    <div class="clear"></div>
    <el-table
            :data="datas"
            border
            ref="table"
            style="width: 100%"
            :max-height="table_height"
            v-loading="loading"
            size="small"
            stripe
            highlight-current-row
            @row-click="row_click"
            :default-sort = "order_by_default"
            @sort-change="order_by"
    >
        <template v-if="is_batch"><el-table-column type="selection" width="55" align="center"></el-table-column></template>
        <el-table-column type="index" fixed width="50" align="center"></el-table-column>
        @foreach($list as $key=>$val)
            @php
                $list_inc ++;
                $w = $val['width'];
                empty($w) && $w = 200;
                $w -= 20;
                $title = $val['name'];
                $title_len = mb_strlen($title);
                $w_len = intval($w / 14);
                $tooltip = $title;
                if($title_len > $w_len){
                    $title = mb_substr($title, 0, $w_len - 2)."...";
                }
                $value_base = 'scope.row["'.$key.'"]';
                $value_str = '{{'.$value_base.'}}';
                $edits = 'scope.row["_edit_"]["'.$key.'"]';
                $edits_str = '{{'.$edits.'}}';
                $is_edit = $val['edit'];
                $input_type = $val['input_type'];
                $data_type = $val['data_type'];
                $field_type = $val['field_type'];
                $align = $val['align'];
                $v_data_type = $val['v_data_type'];
                if($align == 'auto'){
                    if(in_array($data_type, ['status', 'time', 'image'])){
                        $align = 'center';
                    }elseif($field_type == 'number'){
                        if(in_array($data_type, ['text', 'textarea', 'editor'])){
                            $align = 'right';
                        }
                    }
                }
                $time_format = $val['time_format'];
                if(in_array($data_type, ['status', 'image', 'file'])|| $is_edit){
                    $is_template = true;
                }else{
                    $is_template = false;
                }
                if($field_type === 'number'){
                    $is_number = 'true';
                }else{
                    $is_number = 'false';
                }
                $ft_info = $val['field_type_info'];
                if(isset($ft_info) && isset($ft_info['decimal'])){
                    $decimal = $ft_info['decimal'];
                }else{
                    $decimal = 0;
                }
                if($v_data_type === 'string'){
                    $v_html = $value_base;
                }elseif($v_data_type === 'number'){
                    $v_html = "get_number_sep({$value_base}, $decimal)";
                }else{
                    $v_html = "get_number_sep({$value_base}, $decimal, true)";
                }
                $is_order = false;
                $verify = $val['verify'];
                if(empty($verify)){
                    $is_order = true;
                }else{
                    if($verify['save_style'] !== 'json'){
                        $is_order = true;
                    }
                }
            @endphp
            <el-table-column
                    prop="{{$key}}"
                    label="{{$title}}"
                    align="{{$align}}"
                    @if($is_order) sortable="custom" @endif
                    @if($list_inc < $list_count) width="{{$w}}" @else min-width="{{$w}}" @endif
                    @if($val['fixed']) fixed @endif
                    @if(!empty($tooltip)) :tooltip="true" placement="top-start" content="<div class='table-tr-tooltip'>{{$tooltip}}</div>" @endif
                    @if($is_edit) class-name="edit" @endif
            >
            <template slot="header" slot-scope="scope">
                <el-tooltip class="item" effect="dark" content="{{$tooltip}}" placement="top">
                    <span>{{$title}}</span>
                </el-tooltip>
            </template>
            @if($is_template)
            <template slot-scope="scope">
                @if($data_type == 'status')
                    @if($is_edit)
                        <el-switch v-model='{!! $value_base !!}' :active-value="1" :inactive-value="0" @change="list_edit($event, scope.$index, scope.row, '{{$key}}', 'status')"></el-switch>
                    @else
                        <div v-if='{!! $value_base !!} === 1 || {!! $value_base !!} === "1"' class="green-color">启用</div>
                        <div v-else-if='{!! $value_base !!} === 0 || {!! $value_base !!} === "0" || {!! $value_base !!} === ""' class="red-color">禁用</div>
                        <div v-else>{{ $value_str }}</div>
                    @endif
                @elseif($data_type == 'image')
                    <upload-image v-model='{!! $value_base !!}' slot="prepend" :title='{!! $value_base !!}' v-show='{!! $value_base !!} !== null && {!! $value_base !!} !== undefined && {!! $value_base !!}.trim() !== ""'></upload-image>
                @elseif($data_type == 'file')
                    <el-link :href='{!! $value_base !!}' size="small" :underline="false" target="_blank">{!! $value_str !!}</el-link>
                @elseif($is_edit)
                    <template v-if='{!! $edits !!}'>
                    @if(in_array($time_format, ['date', 'time', 'datetime']))
                        @php
                            $div = 'date';
                            if($time_format == 'date'){
                                $fmt = 'yyyy-MM-dd';
                            }elseif($time_format == 'time'){
                                $div = 'time';
                                $fmt = 'H:mm:ss';
                            }else{
                                $fmt = 'yyyy-MM-dd HH:mm:ss';
                            }
                        @endphp
                        <el-{{$div}}-picker
                                v-model='{!! $value_base !!}'
                                value-format="{{$fmt}}"
                                type="{{$time_format}}"
                                :picker-options='picker(scope.$index, scope.row, "{{$key}}")'
                                style="text-align: {{$align}}"
                                align="left"
                                @blur='set_list_input($event, scope.$index, scope.row, "{{$key}}", false, false)'
                        >
                        </el-{{$div}}-picker>
                    @else
                        <textarea
                                mediatype="text/html"
                                v-autosize
                                @if($field_type === 'number') @input='input_number($event, scope.row, "{{$key}}", {{$decimal}})' @endif
                                v-model='{!! $value_base !!}'
                                @blur='set_list_input($event, scope.$index, scope.row, "{{$key}}", false, {!! $is_number !!})'
                                @keyup.esc="input_esc(scope.$index, '{{$key}}')"
                        ></textarea>
                    @endif
                    </template>
                    <pre v-html='{!! $v_html !!}'></pre>
                @endif
            </template>
            @else
            <template slot-scope="scope"><pre v-html='{!! $value_base !!}'></pre></template>
            @endif
            </el-table-column>
        @endforeach
        @if($btn_cot > 0)
        <el-table-column fixed="right" label="操作" width="{{$oper_width}}" align="center">
            <template slot-scope="scope">
                @if($ext_total > 0) <el-button @click="more(scope.row)" type="text" size="small">更多</el-button> @endif
                @if($form_info['view'] > 0) <el-button @click="edit(scope.$index, scope.row)" type="text" size="small">{{$edit_name}}</el-button> @endif
                @if($is_delete) <el-button class="text-hover-red" @click="remove(scope.$index, scope.row)" type="text" size="small">删除</el-button> @endif
            </template>
        </el-table-column>
        @endif
    </el-table>
    @if($is_delete)
    <div class="css_list_batch css_list_batch_floor">
        <el-switch v-model="is_batch" active-text="批量操作" size="small"></el-switch>
        <el-link :underline="false" @click="removes" type="danger" v-show="is_batch" style="margin-left: 10px;">删除所选</el-link>
    </div>
    @endif
    <el-pagination
            @size-change="page_size"
            @current-change="page_current"
            :current-page="pagination.page"
            :page-sizes="[10, 20, 50, 100]"
            :page-size="pagination.size"
            layout="total, sizes, prev, pager, next, jumper"
            :total="pagination.total"
            style="margin-top: 8px"
    >
    </el-pagination>
    <div class="clear"></div>
    <el-dialog
            :title="handle.title"
            :visible.sync="handle.show"
            :before-close="handle_close"
            :close-on-click-modal="false"
    >
        <div id="{{$app_list_name}}_edit" class="css_app_list_edit">
        @include("sys.public.layout.tpl.vue.list.edit")
        </div>
        <span slot="footer" class="dialog-footer">
            @if($form_info['add'] > 0 || $form_info['edit'] > 0)
                <div v-if="handle.add">
                    @if($form_info['add'] > 0)
                        <el-button style="float: left" @click="add_clear" size="small">清 空</el-button>
                        <el-button type="primary" @click="add_submit" size="small" :disabled="handle.disabled">新 增</el-button>
                        <el-button @click="handle.show = false" size="small">取 消</el-button>
                    @else
                        <el-button @click="handle.show = false" size="small">关 闭</el-button>
                    @endif
                </div>
                <div v-else>
                    @if($form_info['edit'] > 0)
                        <el-button type="primary" @click="edit_submit" v-if="!handle.add" size="small" :disabled="handle.disabled">保 存</el-button>
                        <el-button @click="handle.show = false" size="small">取 消</el-button>
                    @else
                        <el-button @click="handle.show = false" size="small">关 闭</el-button>
                    @endif
                </div>
            @else
                <el-button @click="handle.show = false" size="small">关 闭</el-button>
            @endif
        </span>
    </el-dialog>
</div>
@endif
