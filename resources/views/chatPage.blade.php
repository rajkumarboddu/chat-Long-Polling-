<html>
<head>
    <style>

    html, body{
        display: block;
        width: 100%;
        margin: 0px;
        padding: 0px;
    }

    #chat-box-container{
        display: inline-block;
        width: 80%;
        height: 100%;
    }

    #cons-container{
        display: inline-block;
        width: 20%;
        border-left: 1px solid grey;
        position: fixed;
        right: 0px;
        top: 0px;
        height: 100%;
        padding: 5px;
    }

    #chat-box{
        position: fixed;
        right: 22%;
        top: 10%;
        display: none;
    }

    #msg-container{
        min-height: 350px;
        max-height: 350px;
        width: 250px;
        border: 1px solid grey;
        overflow-y: auto;
        font-size: small;
        color: black;
        margin-bottom: 20px;
    }

    #msg-box{
        width: 250px;
    }

    #chat-reqs-container{
        height: 50%;
    }

    #old-chat-container{
        height: 50%;
    }

    .cons-container{
        overflow-y: auto;
        font-size: small;
        min-height: 90%;
        max-height: 90%;
    }

    .con{
        height: 40px;
        font-size: small;
        border-bottom: 1px solid lightgray;
    }

    .con:first-of-type{
        border-top: 1px solid lightgray;
        margin-top: 5px;
    }

    .con:hover{
        background-color: ghostwhite;
        cursor: pointer;
    }

    .con .status{
        width: 7px;
        height: 7px;
        display: inline-block;
        margin-bottom: 1px;
        border-radius: 50%;
        margin-right: 5px;
    }

    .con .online{
        background: limegreen;
    }

    .con .offline{
        background: grey;
    }

    .con .client-name{
        display: inline-block;
        line-height: 40px;
        width: 80%;
    }

    .con .unread-count{
        display: inline-block;
        padding: 2px 5px;
        background: black;
        border-radius: 45%;
        color: white;
        font-weight: bolder;
    }

    .hide{
        display: none;
    }

    .by-me{
        text-align: right;
        padding-right: 10px;
        padding-top: 10px;
        padding-bottom: 10px;
        padding-left: 10px;
        background-color: lightblue;
        margin: 10px;
        border-top-left-radius: 10px;
        border-bottom-left-radius: 10px;
        border-top-right-radius: 10px;
        display: inline-block;
        float:right;
    }

    .by-receiver{
        text-align: left;
        padding-left: 10px;
        padding-top: 10px;
        padding-bottom: 10px;
        padding-right: 10px;
        background-color: lightgray;
        margin: 10px;
        border-top-left-radius: 10px;
        border-bottom-right-radius: 10px;
        border-top-right-radius: 10px;
        display: inline-block;
    }

    .unable-to-send{
        text-align: right;
        padding-right: 10px;
        padding-top: 10px;
        padding-bottom: 10px;
        padding-left: 10px;
        margin: 10px;
        border-top-left-radius: 10px;
        border-bottom-left-radius: 10px;
        border-top-right-radius: 10px;
        display: inline-block;
        float:right;
        background-color: orangered;
    }

    .time{
        font-size: x-small;
        color: #424242;
        margin-top: 5px;
    }

    .right{
        float: right;
    }

    .close{
        cursor: pointer;
        color: red;
        padding: 3px;
        font-size: small;
        border: 1px solid red;
    }

    .break{
        clear: both;
    }
    </style>
    <link rel="stylesheet" type="text/css" href="{{url('css/jquery.mCustomScrollbar.css')}}" />
</head>
<body>
<div id="chat-box-container">
    <div id="chat-box">
        <p><strong>Client #<span id="client-id"></span></strong><strong class="right close">X</strong></p>
        <div id="msg-container">
        </div>
        <div id="msg-box">
            <input type="text" id="msg" name="msg">
            {{csrf_field()}}
            <input type="button" id="send-btn" value="Send">
        </div>
    </div>
</div>
<div id="cons-container">
    <div id="chat-reqs-container">
        <strong>Chat Requests:</strong>
        <div id="chat-reqs" class="cons-container">
            @foreach((new \App\Http\Controllers\AdminController)->getChatRequests() as $req)
            <div class="con" id="con{{$req->id}}" data-id="{{$req->id}}">
                <div class="status @if($req->status==0) offline @elseif($req->status==1) online @endif" data-id="{{$req->status}}"></div>
                <div class="client-name">Client - {{$req->id}}</div>
                <div class="unread-count" @if($req->unread_count==0) style="display: none;" @endif data-id="{{$req->unread_count}}">
                    @if($req->unread_count>0) {{$req->unread_count}} @else 0 @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    <div id="old-chat-container">
        <strong>Old conversations:</strong>
        <div id="old-cons" class="cons-container">
            @foreach((new \App\Http\Controllers\AdminController)->getOldChat() as $req)
            <div class="con" id="con{{$req->id}}" data-id="{{$req->id}}">
                <div class="status @if($req->status==0) offline @elseif($req->status==1) online @endif" data-id="{{$req->status}}"></div>
                <div class="client-name">Client - {{$req->id}}</div>
                <div class="unread-count" @if($req->unread_count==0) style="display: none;" @endif data-id="{{$req->unread_count}}">
                    @if($req->unread_count>0) {{$req->unread_count}} @else 0 @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>
<div id="templates" style="display: none;">
    <div class="con">
        <div class="status"></div>
        <div class="client-name">Client - </div>
        <div class="unread-count"></div>
    </div>
</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
<script type="text/javascript" src="{{url('js/jquery.mCustomScrollbar.concat.min.js')}}"></script>
<script type="text/javascript">
    $(document).ready(function(){
        $('.cons-container, #msg-container').mCustomScrollbar({
            theme: 'dark'
        });
    });
</script>
<script type="text/javascript" src="{{url('js/exe-chat.js')}}"></script>
</body>
</html>