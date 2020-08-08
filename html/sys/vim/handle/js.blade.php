<script>
    var static_path = $("body").attr("data-static-path");
    var g_fieldstr = $('body').attr('data-field');
    var g_field = {};
    if(g_fieldstr == undefined || g_fieldstr == ''){
        g_field = {};
    }else{
        g_field = eval('(' + g_fieldstr + ')');
    }
</script>
@if($types['article'])
<script src="{{url($static_tphp.'js/ueditor/ueditor_api.js')}}"> </script>
<script src="{{url($static_tphp.'js/ueditor/lang/zh-cn/zh-cn.js')}}"></script>
<script>
    $(function() {
        layui.use(['form', 'layedit', 'laydate'], function() {
            $("textarea.js_ueditor").each(function () {
                var _this = $(this);
                var height = _this.height();
                var id = _this.attr('id');
                var config = {};
                if(height > 0){
                    config = {initialFrameHeight:height};
                }
                if(_this.val() == ''){
                    var fname = _this.attr("name");
                    if(g_field[fname] != undefined){
                        _this.val(g_field[fname]);
                    }
                }
                UE.getEditor(id, config);
            });
        });
    });
</script>
@endif
@if($types['markdown'])
<script src="{{url($static_tphp.'js/markdown/editormd.min.js')}}"> </script>
<script>
    $(function() {
        function iframe_div() {
            layui.use(['form', 'element'], function () {
                handle();
            });
        }

        if(parent.$(".layui-layer-loading").size() > 0) {
            var timeout = "";
            function iframe_show() {
                clearTimeout(timeout);
                if (parent.$(".layui-layer-loading").size() <= 0) {
                    iframe_div();
                } else {
                    timeout = setTimeout(iframe_show, 10);
                }
            }
            iframe_show();
        }else{
            iframe_div();
        }

        var lfi = $(".layui-form-item");
        var md_size = 0;
        var mdeditors = [];
        var others = [];
        var default_h = 300;
        var tab_h;
        if($("ul.layui-tab-title").size() > 0){
            tab_h = 25;
        }else{
            tab_h = 60;
        }
        function md_resize() {
            var win_h = $(window).height();
            var other_h = 0;
            for(var o in others){
                other_h += others[o].height() + 15;
            }
            var set_h = win_h - other_h - tab_h;
            if(md_size > 0){
                set_h = parseInt(set_h / md_size);
            }
            if(set_h < default_h){
                set_h = default_h;
            }
            for(var md in mdeditors){
                try{
                    mdeditors[md].height(set_h);
                }catch (e) {
                    // TODO
                }
            }
        }
        function md_resize_width() {
            for(var md in mdeditors){
                try{
                    mdeditors[md].width("calc(100% - 2px)");
                }catch (e) {
                    // TODO
                }
            }
        }

        function handle() {
            layui.use(['form', 'layedit', 'laydate', 'element'], function () {
                lfi.each(function () {
                    var _this = $(this);
                    var js_markdown = _this.find(".js_markdown");
                    if(js_markdown.size() > 0) {
                        var id = js_markdown.attr('id');
                        var mdeditor = editormd(id, {
                            width: "calc(100% - 2px)",
                            height: default_h,
                            syncScrolling: "single",
                            path: "{{$static_tphp}}js/markdown/lib/",
                            toolbarAutoFixed: false,
                            imageUpload : true,
                            imageFormats : ["jpg", "jpeg", "gif", "png", "bmp", "webp"],
                            imageUploadURL : "/sys/upload/markdown",
                            onfullscreen : function() {
                                md_resize_width();
                            },
                            onfullscreenExit : function() {
                                md_resize_width();
                            }
                        });
                        mdeditors.push(mdeditor);
                        md_size ++;
                    }else{
                        others.push(_this);
                    }
                });
                if(md_size > 0) {
                    md_resize();
                    $(window).resize(function () {
                        md_resize();
                    });
                }
            });
        }
    });
</script>
@endif
@if($types['time'])
<script>
    layui.use(['form', 'laydate', 'element'], function() {
        var laydate = layui.laydate;
        $(".js_input_time").each(function () {
            laydate.render({
                elem: '#' + $(this).attr("id"),
                type: 'datetime'
            });
        });
    });
</script>
@endif
@if($types['selects'] && !$is_ie)<script src="{{url($static_tphp.'layui/lay/modules/formselects-v4.js')}}" type="text/javascript" charset="utf-8"></script>@endif
@if($types['trees'])
<script>
    layui.config({
        base: static_path + 'layui/lay/modules/',
    }).extend({
        authtree: 'authtree',
    });
    layui.use(['jquery', 'authtree', 'form', 'layer', 'element'], function(){
        var $ = layui.jquery;
        var authtree = layui.authtree;
        $(".js_input_trees").each(function () {
            var id = $(this).attr("id");
            var json = JSON.parse($(this).attr("data-json"), true);
            var idstr = "#" + id;
            authtree.render(idstr, json, {inputname: id + '[]', layfilter: 'lay-check-auth', autowidth: true, openall: true});
        });
    });
</script>
@endif
@if($types['image'] || $types['file'])
<script src="{{url($static_tphp.'js/jquery/upload/vendor/jquery.ui.widget.js')}}"></script>
<script src="{{url($static_tphp.'js/jquery/upload/jquery.iframe-transport.js')}}"></script>
<script src="{{url($static_tphp.'js/jquery/upload/jquery.fileupload.js')}}"></script>
<script>
    $(function () {
        layui.use(['form', 'laydate', 'element'], function() {
            function getImageWidth(url,callback){
                var img = new Image();
                img.src = url;

                // 如果图片被缓存，则直接返回缓存数据
                if(img.complete){
                    callback(img.width, img.height);
                }else{
                    // 完全加载完毕的事件
                    img.onload = function(){
                        callback(img.width, img.height);
                    }
                }
            }

            var chars = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
            function getRadom(n) {
                var res = "";
                for(var i = 0; i < n; i++) {
                    var id = Math.ceil(Math.random() * 35);
                    res += chars[id];
                }
                return res;
            }

            $(".js_btn_image_or_file").unbind().click(function () {
                var _this = $(this);
                var parent = _this.parent();
                var ipt = parent.find(".js_image_or_file");
                var iss = parent.parent().find(".js_image_show");
                var issa = iss.find(">a");
                var isss = iss.find(">span");
                var file_upload = parent.find(".file_upload");
                var isss_text = isss.html();
                var type = _this.attr("data-type");
                if(type == 'image') { //上传图片
                    file_upload.fileupload({
                        dataType: 'json',
                        //设置进度条
                        progressall: function () {
                            layer.load(1);
                        },
                        //上传完成之后的操作，显示在img里面
                        done: function (e, data) {
                            layer.closeAll('loading');
                            if(data.result.code == 0){
                                isss.html(isss_text);
                                if(ipt.val().trim() === '') {
                                    iss.hide();
                                }
                                layer.msg(data.result.msg, {icon: 2});
                            }else {
                                var texts = data.result.data;
                                var i = 0;
                                for (var key in texts) {
                                    var text = texts[key];
                                    if (i <= 0) {
                                        parent.find(".js_image_or_file").val(text);
                                        issa.attr('href', text).find("img").attr('src', text + "?t=" + getRadom(6));

                                        getImageWidth(text, function (m_w, m_h) {
                                            if (m_w > 0 && m_h > 0) {
                                                iss.show();
                                                isss.html(m_w + " x " + m_h + " pixels");
                                            }else{
                                                iss.hide();
                                            }
                                        });
                                    }
                                    $("#" + key).val(text);
                                    i++;
                                }
                            }
                        },
                        fail: function () {
                            layer.closeAll('loading');
                            if(ipt.val().trim() === '') {
                                iss.hide();
                            }
                            layer.msg("上传错误，请重试！", {icon: 2});
                        }
                    });
                }else{ //上传文件
                    file_upload.fileupload({
                        dataType: 'json',
                        //设置进度条
                        progressall: function () {
                            layer.load(1);
                        },
                        //上传完成之后的操作，显示在img里面
                        done: function (e, data) {
                            if(data.result.code == 0){
                                layer.msg(data.result.msg, {icon: 2});
                            }else {
                                var texts = data.result.data;
                                for (var key in texts) {
                                    var text = texts[key];
                                    parent.find(".js_image_or_file").val(text);
                                    break;
                                }
                            }
                            layer.closeAll('loading');
                        },
                        fail: function () {
                            layer.closeAll('loading');
                            layer.msg("上传错误，请重试！", {icon: 2});
                        }
                    });
                }
                file_upload.trigger('click');
                return false;
            });

            function setImage(obj, is_init_url) {
                var name = $(obj).attr("name");
                var iof = $(".js_image_or_file[name='" + name + "']");
                var dval = iof.attr("data-value");
                var val = "";
                if(is_init_url) {
                    val = g_field[name];
                }else{
                    val = iof.val();
                }
                iof.attr("data-value", val);
                var iss = iof.parent().parent().find(".js_image_show");
                if(val != dval) {
                    var issa = iss.find(">a");
                    var isss = iss.find(">span");
                    iss.hide();
                    if(val != ''){
                        issa.attr('href', val).find("img").attr('src', val + "?t=" + getRadom(6));
                        getImageWidth(val, function (m_w, m_h) {
                            if (m_w > 0 && m_h > 0) {
                                iss.show();
                                isss.html(m_w + " x " + m_h + " pixels");
                                issa.show();
                            }
                        });
                    }
                }
            }
            $(".js_btn_image_or_file").each(function () {
                var _this = $(this);
                if(_this.attr('data-type') == 'image'){
                    var jiof = _this.parent().find(".js_image_or_file");
                    jiof.each(function () {
                        setImage(this, true);
                    });
                    jiof.keyup(function () {
                        setImage(this);
                    }).mouseup(function () {
                        setImage(this);
                    });
                }
            });
        });
    });
</script>
@endif
@if($types['dir'])
    <script src="{{url($staticadmin.'js/dir.js')}}"></script>
    <script>
        $(function () {
            layui.use(['tree', 'util'], function(){
                $(".js_btn_dir").unbind().click(function () {
                    var _t = $(this);
                    var _t_parent = _t.parent();
                    var _t_top = _t_parent.parent();
                    var _t_lfl = _t_top.find(".layui-form-label");
                    var _t_jnr = _t_lfl.find(".js_name_remark");
                    var _t_title = '';
                    if(_t_jnr.size() > 0){
                        _t_title = _t_jnr.html();
                    }else{
                        _t_title = _t_lfl.html();
                    }
                    if(typeof dir_fun === 'function'){
                        dir_select_tree(_t, _t_parent.find("input"), undefined, _t_title, undefined, undefined, undefined, dir_fun);
                    }else{
                        dir_select_tree(_t, _t_parent.find("input"), undefined, _t_title);
                    }
                });
            });
        });
    </script>
@endif