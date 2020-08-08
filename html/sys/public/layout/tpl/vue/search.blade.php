@php
    $search_list = $search[0];
    $sl_cot = 0;
    foreach ($search_list as $sl){
        $sl_cot += count($sl);
    }
    $f_width = 460;
    $form_width = $f_width;
    $f_group_num = 1;
    $pre_width = "100%";
    if($sl_cot > 20){
        $mode = 8;
        $f_group_num = 3;
        $form_width *= $f_group_num;
    }elseif($sl_cot > 10){
        $mode = 12;
        $f_group_num = 2;
        $form_width *= $f_group_num;
        $pre_width = "66.66%";
    }else{
        $mode = 24;
    }
@endphp
@if(!empty($search_list))
<div id="app_search">
    <el-form
            ref="form"
            :model="data"
            label-width="110px"
            size="small"
            class="css_search_group_{{$f_group_num}}"
    >
    @php
        $inc = 1;
        $inc_group = 1;
    @endphp
    @foreach($search_list as $key => $val)
        @if(!in_array($key, ['default', ''])) <transition name="search_group"><div class="subtitle" v-show="show">{{$key}}</div></transition> @endif
        @foreach($val as $k => $v)
        @php
            $v_type = $v['type'];
            if($inc > 2){
                $show = 'v-show="show"';
            }else{
                $show = '';
            }
            if($f_group_num > 1){
                if(in_array($v_type, ['value', 'key_value', 'table', 'api'])){
                    if($inc_group > 1){
                        $inc ++;
                        if($inc > 2){
                            $show = 'v-show="show"';
                        }
                    }
                    $inc ++;
                    $inc_group = 1;
                    $is_last = true;
                }else if($inc_group >= $f_group_num){
                    $inc ++;
                    $inc_group = 1;
                    $is_last = true;
                }else{
                    $inc_group ++;
                    $is_last = false;
                }
            }else{
                $inc ++;
                $is_last = false;
            }
            $label_name = $v['name'];
            if(mb_strlen($label_name) > 6){
                $tooltip = '<div class="tooltip_more" v-popover:popover_'.$k.'></div><el-popover ref="popover_'.$k.'" placement="right" width="200" trigger="click" content="'.$label_name.'"></el-popover>';
            }else{
                $tooltip = '';
            }
            $field_type = $v['field_type'];
        @endphp
        <transition name="search">
        @if($v_type == 'time')
            <el-form-item label="{{$label_name}}" style="max-width: {{$f_width}}px;" {!! $show !!}>
                {!! $tooltip !!}
                <el-col :span="11">
                    <el-date-picker style="width: 100%;" value-format="yyyy-MM-dd" v-model="data.{{$k}}.start" type="date" :picker-options="{disabledDate: time_start_end(data.{{$k}}.start, data.{{$k}}.end, true)}"></el-date-picker>
                </el-col>
                <el-col class="line" :span="2" style="text-align: center;">-</el-col>
                <el-col :span="11">
                    <el-date-picker style="width: 100%;" value-format="yyyy-MM-dd" v-model="data.{{$k}}.end" type="date" :picker-options="{disabledDate: time_start_end(data.{{$k}}.start, data.{{$k}}.end, false)}"></el-date-picker>
                </el-col>
            </el-form-item>
        @elseif($v_type == 'status')
            <el-form-item label="{{$label_name}}" style="max-width: {{$f_width}}px;" {!! $show !!}>
                {!! $tooltip !!}
                <list-radio v-model="data.{{$k}}" list='[{"key": "", "value": "所有"}, {"key": "1", "value": "启用"}, {"key": "0", "value": "禁用"}]'></list-radio>
            </el-form-item>
        @elseif(in_array($v_type, ['image', 'file']))
            <el-form-item label="{{$label_name}}" style="max-width: {{$f_width}}px;" {!! $show !!}>
                {!! $tooltip !!}
                <list-radio v-model="data.{{$k}}" list='[{"key": "", "value": "所有"}, {"key": "1", "value": "已上传"}, {"key": "0", "value": "未上传"}]'></list-radio>
            </el-form-item>
        @elseif(in_array($v_type, ['value', 'key_value', 'table', 'api']))
            <el-form-item label="{{$label_name}}" style="max-width: {{$form_width}}px; @if($f_group_num > 1) width: {{$pre_width}}; @endif" class="clear" {!! $show !!}>
                {!! $tooltip !!}
                @if($v['input_type'] == 'link')
                <link-select v-model="data.{{$k}}" :search="true" url="{{$url_handle.$k}}" :top_value="{{$v['top_value']}}"></link-select>
                @elseif($v['input_type'] == 'select')
                <list-select v-model="data.{{$k}}" @if(in_array($v_type, ['value', 'key_value'])) list='{!! json__encode($v['list']) !!}' @else url="{{$url_handle.$k}}" @endif :multiple="true"></list-select>
                @else
                <list-checkbox v-model="data.{{$k}}" @if(in_array($v_type, ['value', 'key_value'])) list='{!! json__encode($v['list']) !!}' @else url="{{$url_handle.$k}}" @endif></list-checkbox>
                @endif
            </el-form-item>
        @else
            <el-form-item @if($field_type === 'number') class="css_number_input" @endif label="{{$label_name}}" style="max-width: {{$f_width}}px;" {!! $show !!}>
                {!! $tooltip !!}
                @if($v['is_area'])
                    <el-col :span="11">
                        <el-input v-model="data.{{$k}}.start" @input='set_input_number(data.{{$k}}, "start", $event)' @keyup.enter.native="search"></el-input>
                    </el-col>
                    <el-col class="line" :span="2" style="text-align: center;">-</el-col>
                    <el-col :span="11">
                        <el-input v-model="data.{{$k}}.end" @input='set_input_number(data.{{$k}}, "end", $event)' @keyup.enter.native="search"></el-input>
                    </el-col>
                @elseif($field_type === 'number')
                    <el-input v-model="data.{{$k}}" @input='set_input_number(data, "{{$k}}", $event)' @keyup.enter.native="search"></el-input>
                @else
                    <el-input v-model="data.{{$k}}" @keyup.enter.native="search"></el-input>
                @endif
            </el-form-item>
        @endif
        </transition>
        @if($is_last) <div class="clear"></div> @endif
        @endforeach
    @endforeach
        <div class="clear"></div>
        <el-form-item>
            <div class="btn_show">
                @if($inc > 3)
                    <el-button type="text" @click="show = !show;">
                        <template v-if="show">收起 <span class="el-icon-arrow-up"></span></template>
                        <template v-else>展开 <span class="el-icon-arrow-down"></span></template>
                    </el-button>
                @endif
            </div>
            <el-button type="primary" icon="el-icon-search" @click="search">搜索</el-button>
            <el-button icon="el-icon-refresh" @click="reset">重置</el-button>
        </el-form-item>
        <div class="clear"></div>
        <div class="btn_show btn_show_bottom"></div>
    </el-form>
</div>
@endif
