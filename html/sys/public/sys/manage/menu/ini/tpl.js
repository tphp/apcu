$(function() {
    var themes = get_ace_themes();
    layui.use(['element', 'layer'], function () {
        $(".js_view_code").each(function () {
            var _this = $(this);
            var name = _this.attr("data-name");
            var id = _this.attr("data-md5-name");
            var parent = _this.parent().parent();
            parent.append("<pre id='" + id + "' style='display: none' class='code'></pre>");
            _this.click(function () {
                var self = $(this);
                $.ajax({
                    url: code_url,
                    type: 'POST',
                    dataType: 'JSON',
                    data: {
                        name: name
                    },
                    success: function (result) {
                        if(result.code == 1){
                            parent.find("#" + id).html(result.msg);
                            var editor = ace.edit(id);
                            editor.setTheme(themes.dreamweaver);
                            editor.session.setMode("ace/mode/php");
                            editor.setShowPrintMargin(false);
                            editor.setReadOnly(true);
                            var len = editor.session.getLength();
                            $("#" + id).css("display", "").height(len * 18);
                            editor.focus();
                            self.remove();
                        }else{
                            layer.msg(result.msg, {icon: 2, time: 1500});
                        }
                    }
                });
            })
        });

        $(".js_view_next").click(function () {
            var next_url = $(this).attr('data-next-url');
            var obj = self;
            if(parent.is_menu_ini){
                obj = parent;
            }
            var layeropen = obj.layer.open({
                type: 2
                , title: "子目录： " + next_url
                , area: ['800px', '600px']
                , shade: 0.1
                , maxmin: true
                , offset: 'auto'
                , content: 'ini?dir=' + next_url
                , success:function(){
                }
                , btn: ['关闭']
            });
            dbclick_close(layeropen, obj);
            return false;
        });
    });
});