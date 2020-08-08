$(function () {
    var js_input = $("input.js_input");
    var name = js_input.attr("name");
    if(name != undefined && name != "") {
        $(".group-body i").click(function () {
            $(".group-body i").removeClass("select");
            js_input.val($(this).attr("title"));
            $(this).addClass("select");
        });

        var value = js_input.attr("value");
        if(value != undefined && value != ""){
            $(".group-body i[title='" + value + "']").addClass("select");
        }
    }

    $('_CLASS_').css("display", "block");

    function resizeset(){
        var gb = $(".group-body");
        var gbwidth = gb.width();
        var len = parseInt(gbwidth / 40);
        var setwidth = gbwidth / len;
        setwidth -= 21;
        gb.find("i").width(setwidth).height(setwidth);
    }

    resizeset();

    $(window).resize(function () {
        resizeset();
    });
});