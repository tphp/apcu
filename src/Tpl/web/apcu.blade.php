<html>
<head>
    <title>Acpu更新程序</title>
    <script src="{{ url(config('path.static').'js/jquery/jquery.js') }}"></script>
</head>
<body>
<div>
    <button class="JS_btn">更新Apcu</button>
</div>
<div class="JS_info"></div>
<script>
    var portjsons = {!!$portjsons!!};
    var portlen = portjsons.length;
    var host = "http://{{$host}}/apcu/";
    $(function(){
        var js_info = $(".JS_info");

        function setInfo(htmlarr){
            var str = "";
            for(var i in htmlarr){
                if(typeof(htmlarr[i]) != 'undefined' && htmlarr[i] != ''){
                    str += htmlarr[i];
                }
            }
            js_info.html(str);
        }

        $(".JS_btn").click(function(){
            js_info.html("");
            var str = "";
            var url = "";
            var tmparr = [];
            for(var i in portjsons){
                tmparr[parseInt(i) + 1] = "";
            }

            for(var i in portjsons){
                url = host + portjsons[i];
                if(i == 0) url = url + "?msg=true";
                $.ajax({
                    url : url,
                    port : parseInt(i) + 1,
                    success : function(data){
                        var portstr = this.port;
                        if(portstr < 10) portstr = '0' + portstr;
                        str = "<div>";
                        str += portstr + "/" + portlen + " ： " + data;
                        str += "</div>";
                        tmparr[this.port] = str;
                        setInfo(tmparr);
                    }
                });
            }
        });
    });
</script>
</body>
</html>