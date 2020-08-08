@if($is_error)
    <div id="{{$app_list_name}}" class="css_app_list ext_error" v-show="show">{!! $error_msg !!}</div>
@else
    @include("sys.public.layout.tpl.vue.list.tpl")
@endif
@php
    $ob_json = [
        'prop' => null,
        'order' => null,
    ];
    $form_info['edit'] > 0 ? $edit_name = '编辑' : $edit_name = '查看';
@endphp
<script>
    list_exts['{{$ext_key}}'] = {
        field: {!! json_encode($list, true) !!},
        url: '{!! $url !!}',
        url_more: '{!! $url_more !!}',
        rules: {!! json_encode($list_rules, true) !!},
        order_by_default: {!! json_encode($ob_json, true) !!},
        edit_name: '{{$edit_name}}'
    };
</script>