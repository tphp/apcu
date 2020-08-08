layui.use(['table', 'form'], function(){
    var table = layui.table;
    var sqls = $('#list').attr("data-sqls");
    var sqljson = {};
    if(sqls != undefined){
        sqljson = $.parseJSON(sqls);
    }

    table.render({
        elem: '#list'
        ,cols: [[
            {field: 'table', title: '表', width: 150}
            , {field: 'field', title: '字段', width: 150}
            , {field: 'sql', title: '语句'}
        ]]
        ,data: sqljson
        ,even: true
        ,limit: 10000
        ,height: 'full-' + 100
        ,size: 'sm'
    });

    function diff_do(type){
        __ajax({
            type: "post",
            url: "diff/do",
            data: {
                type: type,
                conn: $("#conn").val()
            },
            success: function (msg) {
                $().urlparam().alert(msg, {icon: 1}).run();
            }
        });
    }

    $(".js_btn_save").click(function () {
        dbclick_close(layer.confirm("确定备份数据库 （" + $("#conn").val() + "） 字段信息？", {
            shade: 0.1
        }, function(){
            diff_do("save");
        }));
        return false;
    });

    var explain_html = $("#id_explain").html();
    $(".js_btn_explain").click(function () {
        var htmltxt = $(this).html();
        dbclick_close(layer.open({
            type: 1
            , title: htmltxt
            , area: ['600px', '400px']
            , shade: 0.1
            , content: explain_html
        }));
        return false;
    });

    $(".js_btn_flush").click(function () {
        $().urlparam().run();
        return false;
    });

    $(".js_btn_rest").click(function () {
        dbclick_close(layer.confirm("<span style='color:#F33'>还原后可能导致数据丢失，请谨慎操作！</span><BR>确定还原数据库 （" + $("#conn").val() + "） 字段信息？", {
            shade: 0.1
        }, function(){
            diff_do("rest");
        }));
        return false;
    });

    $("#conn").parent().find("dl.layui-anim dd").click(function () {
        var _this = $(this);
        var value = _this.attr("lay-value");
        $().urlparam().set("conn", value).run();
    });
});