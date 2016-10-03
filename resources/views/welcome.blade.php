<!DOCTYPE html>
<html>
    <head>
        <title>Laravel</title>

        <style>
            html, body {
                height: 100%;
            }

            body {
                margin: 0;
                padding: 0;
                width: 100%;
                display: table;
            }

            .container {
                text-align: center;
                display: inline-block;
                vertical-align: middle;
                display: none;
                margin-right: 20px;
                float:right;
                margin-top: 20px;
            }

            .content {
                text-align: center;
                display: inline-block;
            }

            #admin-login-container{
                width: 200px;
                height: 110px;
                border: 1px solid grey;
                padding: 20px 50px 50px 50px;
                display: inline-block;
                float: right;
                margin-right: 20%;
                margin-top: 20px;
            }

            .msg{
                font-size: small;
                display: block;
                padding: 0px 0px 10px 0px;
            }

            .err{
                color: red;
            }

            #start-chat-btn{
                margin: 20px 0px 0px 20px;
            }

            #send-btn{
                display: inline-block;
                vertical-align: top;
                margin-top: 8px;
            }
        </style>
        <link rel="stylesheet" type="text/css" href="{{url('css/client-chat.css')}}" />
        <link rel="stylesheet" type="text/css" href="{{url('css/jquery.mCustomScrollbar.css')}}" />
    </head>
    <body>
        <button id="start-chat-btn">Start Chat!</button>
        <div class="container">
            <div class="content">
                <div id="msg-container">
                </div>
                <div id="msg-box">
                    <textarea id="msg" name="msg" rows="2" style="resize: none;"></textarea>
                    {{csrf_field()}}
                    <input type="button" id="send-btn" value="Send">
                </div>
            </div>
        </div>
        <div id="admin-login-container">
            <form id="login-form">
                <strong>Executive Login</strong>
                <span class="msg"></span>
                <input type="text" name="username" placeholder="Username" /><br><br>
                <input type="password" name="password" placeholder="Password" /><br><br>
                {{csrf_field()}}
                <button id="login-submit-btn">Login</button>
            </form>
        </div>
    </body>
    <script src="https://code.jquery.com/jquery-3.1.0.min.js" integrity="sha256-cCueBR6CsyA4/9szpPfrX3s49M9vUU5BgtiJj06wt/s=" crossorigin="anonymous"></script>
    <script type="text/javascript" src="{{url('js/jquery.mCustomScrollbar.concat.min.js')}}"></script>
    <script>
        $(document).ready(function(){
            $('#msg-container').mCustomScrollbar({
                theme: 'dark'
            });

            $('#login-submit-btn').click(function(e){
                e.preventDefault();
                var $msg = $('.msg');
                $msg.removeClass('err');
                $msg.html('Please wait...');
                $.ajax({
                    url: 'admin/doLogin',
                    method: 'post',
                    data: $('#login-form').serialize()
                }).done(function(response){
                    window.location.href = 'admin/chat';
                }).fail(function(responseObj){
                    $msg.html(JSON.parse(responseObj.responseText)).addClass('err');
                });
            });
        });
    </script>
    <script type="text/javascript" src="{{url('js/client-chat.js')}}"></script>
</html>
