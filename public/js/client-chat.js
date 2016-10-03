$(document).ready(function(){
    var from_id = '';
    var to_id = '';
    var received_msgs = [];
    var csrf_token = $('input[name="_token"]').val();
    var last_activity_time;

    var init = function(){
        var chat_data = {
            from_id: from_id,
            to_id: to_id,
            received_msgs : received_msgs
        };
        $.ajax({
            url: 'chat/client/init',
            method: 'get',
            async: true,
            data: chat_data
        }).done(function(response){
            received_msgs = [];
            if(response.from_id !== undefined){
                from_id = response.from_id;
            }
            if(response.to_id !== undefined){
                to_id = response.to_id;
            }
            // if any previous messages exists
            if(response.prev_msgs !== undefined){
                $.each(response.prev_msgs,function(i,ele){
                    if(ele.msg_by=='client'){
                        $container.append('<div class="by-me">' + ele.message + '<div class="time">'+ele.created_at+'</div></div><div class="break"></div>');
                    } else if(ele.msg_by=='exe'){
                        $container.append('<div class="by-receiver">' + ele.message + '<div class="time">'+ele.created_at+'</div></div><div class="break"></div>');
                    }
                });
                scrollToBottom($('#msg-container'));
            }
            // if new message arrives
            if(response.new_msgs !== undefined){
                $.each(response.new_msgs,function(i,ele){
                    // append messages
                    $container.append('<div class="by-receiver">' + ele.message + '<div class="time">'+ele.created_at+'</div></div><div class="break"></div>');
                    scrollToBottom($('#msg-container'));
                    received_msgs.push(ele.id);
                });
            }
            last_activity_time = (new Date()).getTime();
            init();
        }).fail(function(responseObj){
            console.log(responseObj);
        });
    };

    var ping_xhr;
    function ping(){
        // ping server for every 5 seconds
        if(((new Date()).getTime())-last_activity_time>5000){
            try{ if(ping_xhr !== undefined) ping_xhr.abort(); } catch(e){}
            ping_xhr = $.get('chat/client/ping',function(response){
                last_activity_time = (new Date()).getTime();
            });
        }
    }

    // ping server
    setInterval(ping,1000);

    var $container = $('#mCSB_1_container');

    // on pressing enter key send message
    $('#msg').keyup(function(e) {
        if (e.keyCode == 13 && $(this).val()!='') {
            sendMessage();
        }
    }).focus();

    // on button click send message
    $('#send-btn').click(function(){
        if($('#msg').val()!=''){
            sendMessage();
        }
    });

    function sendMessage(){
        var message = $('#msg').val();
        var chat_data = {
            from_id: from_id,
            to_id: to_id,
            message: message,
            _token: csrf_token
        };
        $.ajax({
            url: 'chat/client/sendMessage',
            method: 'post',
            data: chat_data
        }).done(function(response){
            $container.append('<div class="by-me">' + message + '<div class="time">'+getTimestamp()+'</div></div><div class="break"></div>');
            scrollToBottom($('#msg-container'));
        }).fail(function(responseObj){
            $container.append('<div class="unable-to-send">' + message + '<div class="time">'+getTimestamp()+'</div></div><div class="break"></div>');
            scrollToBottom($('#msg-container'));
        });
        $('#msg').val('');
    }

    init();

    function scrollToBottom($div){
        $div.mCustomScrollbar("scrollTo","bottom");
    }

    function getTimestamp(){
        var date = new Date();
        var options = {
            year: "numeric", month: "short",
            day: "2-digit", hour: "2-digit", minute: "2-digit"
        };
        var timestamp = date.toLocaleTimeString("en-us", options);
        return timestamp.replace(',','');
    }
});