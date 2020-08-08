@php
    $max_len = 1;
    foreach($form as $key => $val){
        list($vcot, $vlist, $group_name) = $val;
        if($vcot > $max_len){
            $max_len = $vcot;
        }
    }
    $key_id_ext = 'le';
@endphp
<el-form
        ref="form"
        :model="handle.data"
        label-width="110px"
        size="small"
        v-loading="loading"
        class="css_list_edit_row css_list_edit_cell_{{$max_len}}"
        :rules='get_rules({!! json__encode($rules) !!}, handle.add)'
>
        @foreach($form as $key => $val)
            @php
                list($vcot, $vlist, $group_name) = $val;
            @endphp
            @if(!empty($group_name))
            <div class="clear"></div>
            <el-divider content-position="left">{{$group_name}}</el-divider>
            <div class="clear"></div>
            @endif
            <div class="css_list_edit_row_{{$vcot}}">
            @foreach($vlist as $kk => $vv)
                <div class="row row_{{count($vv)}}">
                @foreach($vv as $k => $v)
                    @php
                        $decimal = 0;
                        $is_show = 'true';
                        $is_default = false;
                        $default_value = null;
                        if(empty($v)){
                            $input_type = 'empty';
                            $label_name = '';
                            $field_type = '';
                            $is_form = false;
                            $is_add = false;
                            $is_edit = false;
                            $is_view = 'false';
                        }else{
                            $input_type = $v['input_type'];
                            $label_name = $v['name'];
                            $data_type = $v['data_type'];
                            $field_type = $v['field_type'];
                            $multiple = false;
                            $verify = $v['verify'];
                            if(isset($verify) && $verify['save_type'] == 'multi' && $field_type === 'string'){
                                $multiple = true;
                            }
                            if($field_type !== 'string'){
                                if($input_type == 'checkbox'){
                                    $input_type = 'radio';
                                }
                            }
                            $config = $v['config'];
                            $scene = '';
                            if(isset($config)){
                                $scene = $config['scene'];
                            }
                            $is_form = $v['form'];
                            $is_add = $v['add'];
                            $is_edit = $v['edit'];
                            if(in_array($field_type, ['date', 'datetime', 'time'])){
                                $input_type = 'init_time';
                            }
                            $field_type_info = $v['field_type_info'];
                            if(!empty($field_type_info) && isset($field_type_info['decimal'])){
                                $decimal = $field_type_info['decimal'];
                                if(!is_numeric($decimal) || $decimal < 0){
                                    $decimal = 0;
                                }
                            }
                            if(!empty($no_shows)){
                                if($no_shows[$k]){
                                    $is_show = 'false';
                                }
                            }
                            if($data_type === 'text'){
                                if(isset($verify)){
                                    $dvt = $verify['default_value_type'];
                                    if(in_array($dvt, ['add', 'edit'])){
                                        $dv_src = $verify['default_value_src'];
                                        $is_at = false;
                                        if(!empty($dv_src)){
                                            $dv_src = trim($dv_src);
                                            if(strpos($dv_src, "@") > 0){
                                                $is_at = true;
                                            }
                                        }
                                        $dv = $verify['default_value'];
                                        $pos = 0;
                                        if($is_at){
                                            if(!empty($dv)){
                                                $dv = trim($dv);
                                                $pos = strpos($dv, "@");
                                                if($pos <= 0){
                                                    $is_at = false;
                                                }
                                            }
                                        }
                                        if($is_at){
                                            $dv_key = trim(substr($dv, 0, $pos));
                                            $dv_value = trim(substr($dv, $pos + 1));
                                            if(empty($dv_value)){
                                                $dv_value = $dv_key;
                                            }
                                            $default_value = [
                                                "key" => $dv_key,
                                                "value" => $dv_value,
                                            ];
                                        }else{
                                            $default_value = [
                                                "key" => $dv,
                                                "value" => $dv,
                                            ];
                                        }
                                        $default_value['type'] = $dvt;
                                        $is_default = true;
                                    }
                                }
                            }
                        }
                        $disabled = '';
                        if(!$is_add && !$is_edit){
                            $disabled = ':disabled="true"';
                        }elseif(!$is_add){
                            $disabled = ':disabled="handle.add"';
                        }elseif(!$is_edit){
                            $disabled = ':disabled="!handle.add"';
                        }
                        $value_str = "{{handle.data.{$k}}}";
                        if($field_type === 'number'){
                            $is_number = true;
                        }else{
                            $is_number = false;
                        }
                        if(mb_strlen($label_name) > 6){
                            $tooltip = '<div class="tooltip_more" v-popover:popover_'.$k.'></div><el-popover ref="popover_'.$k.'" placement="right" width="200" trigger="click" content="'.$label_name.'"></el-popover>';
                        }else{
                            $tooltip = '';
                        }
                    @endphp
                    <el-form-item label="{{$label_name}}" prop="{{$k}}" @if($is_number && in_array($data_type, ['text', 'textarea'])) class="css_number_input" @endif v-show="{{$is_show}} || !handle.add">
                    {!! $tooltip !!}
                    @if($input_type == 'time' || $input_type == 'init_time')
                        @if($scene == 'custom' || $input_type == 'init_time')
                            @php
                                $div = 'date';
                                if($input_type == 'init_time'){
                                    $format = $field_type;
                                }else{
                                    $format = $v['config']['format'];
                                }
                                if($format == 'date_time'){
                                    $format = 'datetime';
                                }
                                if($format == 'datetime'){
                                    $vf = 'yyyy-MM-dd HH:mm:ss';
                                }elseif($format == 'date'){
                                    $vf = 'yyyy-MM-dd';
                                }else{
                                    $vf = 'HH:mm:ss';
                                    $div = 'time';
                                }
                            @endphp
                            <el-{{$div}}-picker value-format="{{$vf}}" v-model="handle.data.{{$k}}" type="{{$format}}" {!! $disabled !!}></el-{{$div}}-picker>
                        @endif
                    @elseif(in_array($input_type, ['radio', 'checkbox']))
                        <list-{{$input_type}} v-model="handle.data.{{$k}}" @if(in_array($data_type, ['value', 'key_value'])) list='{!! json__encode($v['list']) !!}' @else url="{{$url_handle.$k}}" @endif {!! $disabled !!}></list-{{$input_type}}>
                    @elseif(in_array($input_type, ['select', 'selects']))
                        <list-select v-model="handle.data.{{$k}}" @if($multiple) :multiple="true" :tags="true" @endif @if(in_array($data_type, ['value', 'key_value'])) list='{!! json__encode($v['list']) !!}' @else url="{{$url_handle.$k}}" @endif {!! $disabled !!}></list-select>
                    @elseif($input_type == 'link')
                        <link-select v-model="handle.data.{{$k}}" :nexts="handle.data._next_" next_key="{{$k}}" url="{{$url_handle.$k}}" {!! $disabled !!} :top_value="{{$v['config']['top_value']}}"></link-select>
                    @elseif($input_type == 'empty')
                    @elseif($input_type == 'hide')
                    @elseif($data_type == 'status')
                        <list-radio v-model="handle.data.{{$k}}" :load="set_status_value(handle['data'], '{{$k}}')" list='[{"key": "1", "value": "启用"}, {"key": "0", "value": "禁用"}]' {!! $disabled !!}></list-radio>
                    @elseif(in_array($data_type, ['image', 'file']))
                        <el-input v-model="handle.data.{{$k}}" class="css_list_edit_upload" {!! $disabled !!}>
                            @if($data_type == 'image') <upload-image v-model="handle.data.{{$k}}" slot="prepend" :show="handle.show"></upload-image> @endif
                            <upload v-model="handle.data.{{$k}}" action="{{$url_upload.$k}}" :show-file-list="false" slot="append" type="{!! $data_type !!}" format="{!! $v['format'] !!}" file_max_size="{!! $file_max_size !!}" {!! $disabled !!}>
                                <el-button icon="el-icon-upload2" {!! $disabled !!}></el-button>
                            </upload>
                        </el-input>
                    @elseif(in_array($data_type, ['value', 'key_value']))
                        <list-checkbox v-model="handle.data.{{$k}}" list='{!! json__encode($v['list']) !!}' {!! $disabled !!}></list-checkbox>
                    @elseif($data_type == 'textarea')
                        <el-input class="textarea" type="textarea" autosize v-model="handle.data.{{$k}}" {!! $disabled !!} @if($is_number) @input='set_input_number(handle.data, "{{$k}}", $event, {{$decimal}})' @endif></el-input>
                    @elseif($data_type == 'editor')
                        <editor class="editor" v-model="handle.data.{{$k}}" url='{!! $url !!}' :format='{!! json__encode($file_format) !!}' file_max_size="{!! $file_max_size !!}" {!! $disabled !!}></editor>
                    @elseif($is_default)
                        <disable-input v-model="handle.data.{{$k}}" :is_add="handle.add" :info='{!! json__encode($default_value) !!}'></disable-input>
                    @else
                        <el-input v-model="handle.data.{{$k}}" {!! $disabled !!} @if($is_number) @input='set_input_number(handle.data, "{{$k}}", $event, {{$decimal}})' @endif></el-input>
                    @endif
                    </el-form-item>
                @endforeach
                </div>
            @endforeach
            </div>
        @endforeach
</el-form>
<div class="clear"></div>
