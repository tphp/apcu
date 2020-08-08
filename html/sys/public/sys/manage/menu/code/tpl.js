
$(function() {
    layui.use(['element', 'layer'], function () {
        var themes = get_ace_themes();
        var save_url = $("body").attr("data-base-url") + "?id=" + $(".js_btn_right").attr('data-id');

        function iframe_div() {
            var editors = {};
            $('.js_code').each(function () {
                var _this = $(this);
                var type = _this.attr('data-type');
                var id = _this.attr('id');
                var editor = ace.edit(id);
                editor.setTheme(themes.dreamweaver);
                editor.session.setMode("ace/mode/" + type);
                editor.setShowPrintMargin(false);
                editor.setOption('scrollPastEnd', 0.5);
                editor.focus();
                editors[id] = editor;
            });

            var sys_function_path = $(".js_btn_change").attr('data-sys-function-path');
            $(".js_btn_change").each(function () {
                var _this = $(this);
                var url = _this.attr("data-url");
                if(url != undefined && url != ""){
                    _this.click(function () {
                        var layeropen = layer.open({
                            type: 2
                            , title: "配置函数查询 - 路径：/vendor/tphp/apcu/function 或 " + sys_function_path
                            , area: ['800px', '600px']
                            , shade: 0.1
                            , maxmin: true
                            , offset: 'auto'
                            , content: url
                            , success:function(){
                            }
                            , btn: ['关闭']
                        });
                        dbclick_close(layeropen);
                        layer.full(layeropen);
                    });
                }
            });

            $(".layui-tab-title li").click(function () {
                $(".js_btn_change").css("display", "none");
                $(".js_btn_change[data-id='" + $(this).attr('data-id') + "']").css("display", "inline");
            });

            $('body').keydown(function(e) {
                //Ctrl + s 保存
                if (e.keyCode == 83 && e.ctrlKey) {
                    $(".js_btn_save").trigger("click");
                    return false;
                }else if(e.altKey){
                    if(e.keyCode == 90 || e.keyCode == 88) { //Alt + z | Alt + x 切换
                        var js_code = $(".layui-tab-content .layui-show .js_code");
                        var id = js_code.attr('id');
                        var li = $(".layui-tab-title li[data-id=" + id + "]");
                        var lichg;
                        if (e.keyCode == 90) {
                            lichg = li.prev();
                            var lilen = $(".layui-tab-title li").size();
                            if (lichg.size() <= 0) {
                                lichg = $(".layui-tab-title li:nth-child(" + lilen + ")");
                            }
                        } else {
                            lichg = li.next();
                            if (lichg.size() <= 0) {
                                lichg = $(".layui-tab-title li:nth-child(1)");
                            }
                        }
                        lichg.trigger("click");
                        var lid = lichg.attr('data-id');
                        editors[lid].focus();
                    }else if(e.keyCode == 81){ //Alt + q 关闭窗口
                        parent.layer.closeAll();
                    }
                    return false;
                }
            });

            function rszie() {
                var height = $(window).height() - $(".layui-tab-title").height() - 52;
                $('.layui-tab-item').height(height);
            }

            $(window).resize(function () {
                rszie();
            });
            rszie();

            //关闭窗口
            $(".js_btn_close").click(function () {
                if(parent.$ != undefined){
                    parent.$(".layui-layer-close").trigger('click');
                }
            });

            //刷新
            $(".js_btn_flush").click(function () {
                window.location.reload()
            });

            function save(data){
                __ajax({
                    url: save_url,
                    type: 'POST',
                    dataType: 'JSON',
                    data: data,
                    success: function (msg) {
                        layer.msg(msg, {icon:1, time: 1500});
                    }
                });
            }

            //保存当前
            $(".js_btn_save").click(function () {
                var js_code = $(".layui-tab-content .layui-show .js_code");
                var id = js_code.attr('id');
                var eds = {};
                eds[id] = editors[id].getValue();
                save(eds);
            });

            //保存所有
            $(".js_btn_save_all").click(function () {
                var eds = {};
                for(var id in editors) {
                    eds[id] = editors[id].getValue();
                }
                save(eds);
            });

            $(".js_btn_save_view").click(function () {
                window.open($(this).attr('data-url'));
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
    });
});