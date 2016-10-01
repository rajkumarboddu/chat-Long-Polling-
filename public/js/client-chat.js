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
            console.log(response);
            received_msgs = [];
            if(response.from_id !== undefined){
                from_id = response.from_id;
            }
            if(response.to_id !== undefined){
                to_id = response.to_id;
            }
            // if new message arrives
            if(response.new_msgs !== undefined){
                $.each(response.new_msgs,function(i,ele){
                    // append messages
                    $container.append('<div></div><div class="by-receiver">' + ele.message + '</div>');
                    scrollToBottom($container);
                    received_msgs.push(ele.id);
                });
            }
            last_activity_time = (new Date()).getTime();
            init();
        }).fail(function(responseObj){
            console.log(responseObj);
        });
    };

    function ping(){
        // ping server for every 5 seconds
        if(((new Date()).getTime())-last_activity_time>5000){
            $.get('chat/client/ping',function(response){
                last_activity_time = (new Date()).getTime();
            });
        }
    }

    // ping server
    setInterval(ping,1000);

    var $container = $('#msg-container');
    $container[0].scrollTop = $container[0].scrollHeight;

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
            $container.append('<div class="by-me">' + message + '</div><div></div>');
        }).fail(function(responseObj){
            $container.append('<div class="unable-to-send">' + message + '</div><div></div>');
        });
        scrollToBottom($container);
        $('#msg').val('');
    }

    init();

    function scrollToBottom($div){
        var height = Math.abs($div.children().last().position().top)+Math.abs($div.children().first().position().top);
        $div.animate({scrollTop: height}, 'slow');
    }
});