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
                display: table-cell;
                vertical-align: middle;
            }

            .content {
                text-align: center;
                display: inline-block;
            }

            #admin-login-container{
                position: absolute;
                top: 20px;
                right: 20px;
                width: 200px;
                height: 150px;
                border: 1px solid grey;
                padding: 20px 50px 50px 50px;
            }

            .msg{
                font-size: small;
                display: block;
                padding: 0px 0px 10px 0px;
            }

            .err{
                color: red;
            }
        </style>
        <link rel="stylesheet" type="text/css" href="{{url('css/client-chat.css')}}" />
    </head>
    <body>
        <div class="container">
            <div class="content">
                <div id="msg-container">
                </div>
                <div id="msg-box">
                    <input type="text" id="msg" name="msg">
                    {{csrf_field()}}
                    <input type="button" id="send-btn" value="Send">
                </div>
            </div>
        </div>
        <div id="admin-login-container">
            <form id="login-form">
                <h4>Executive Login</h4>
                <span class="msg"></span>
                <input type="text" name="username" placeholder="Username" /><br><br>
                <input type="password" name="password" placeholder="Password" /><br><br>
                {{csrf_field()}}
                <button id="login-submit-btn">Login</button>
            </form>
        </div>
    </body>
    <script src="https://code.jquery.com/jquery-3.1.0.min.js" integrity="sha256-cCueBR6CsyA4/9szpPfrX3s49M9vUU5BgtiJj06wt/s=" crossorigin="anonymous"></script>
    <script type="text/javascript" src="{{url('js/client-chat.js')}}"></script>
    <script>
        $(document).ready(function(){
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
            })
        })
    </script>
</html>
