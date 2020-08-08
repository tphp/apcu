$(function () {
    layui.use(['form', 'element', 'layer', 'laydate'], function () {
        var main = $('.main');
        var mt = $('.main .text');
        var nav = $(".nav");
        var wd = $(window);
        function resize(){
            var mt_top = nav.height() + 21;
            mt.height(wd.height() - 40 - mt_top);
            main.css({
                paddingTop: mt_top
            });
        }
        $(window).resize(function () {
            resize();
        });
        resize();

        var urlroot = "git";

        //查看GIT状态
        $(".js_git_status").click(function () {
            var htmltxt = $(this).html();
            mt.html('<span style="color:#F00">正在 ' + htmltxt + ' 中...</span>');
            $.ajax({
                type: 'POST',
                url: urlroot,
                data: {
                    type: 'status'
                },
                success: function (html) {
                    mt.html(html);
                }
            });
        });

        $(".js_git_reset").click(function () {
            var htmltxt = $(this).html();
            dbclick_close(layer.confirm("确定 " + htmltxt + " ？<br><span style='color:#F00'>注： 还原后将恢复到最新版本。</span>", {
                shade: 0.1
            }, function(index_out){
                mt.html('<span style="color:#F00">正在 ' + htmltxt + ' 中...</span>');
                $.ajax({
                    type: 'POST',
                    url: urlroot,
                    data: {
                        type: 'reset'
                    },
                    success: function (html) {
                        layer.close(index_out);
                        mt.html(html);
                    }
                });
            }));
        });

        $(".js_git_pull").click(function () {
            var htmltxt = $(this).html();
            dbclick_close(layer.confirm("确定 " + htmltxt + " ？", {
                shade: 0.1
            }, function(index_out){
                mt.html('<span style="color:#F00">正在 ' + htmltxt + ' 中...</span>');
                $.ajax({
                    type: 'POST',
                    url: urlroot,
                    data: {
                        type: 'pull'
                    },
                    success: function (html) {
                        layer.close(index_out);
                        mt.html(html);
                    }
                });
            }));
        });

        $(".js_git_push").click(function () {
            var htmltxt = $(this).html();
            dbclick_close(layer.prompt({title: '输入' + htmltxt + '信息', formType: 2, shade: 0.1}, function(remark, pindex){
                mt.html('<span style="color:#F00">正在 ' + htmltxt + ' 中...</span>');
                layer.close(pindex);
                layer.load(2);
                $.ajax({
                    type: 'POST',
                    url: urlroot,
                    data: {
                        type: 'push',
                        remark: remark
                    },
                    success: function (html) {
                        layer.closeAll('loading');
                        mt.html(html);
                    }
                });
            }));
        });

        //刷新
        $(".js_flush").click(function () {
            window.location.reload();
        });
    });
});