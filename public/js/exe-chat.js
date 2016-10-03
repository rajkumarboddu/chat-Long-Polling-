$(document).ready(function(){

    var $container = $('#mCSB_1_container'),
        to_id = '',
        received_msgs = [],
        csrf_token = $('input[name="_token"]').val(),
        listen_xhr,
        new_reqs= [],
        old_reqs = [],
        new_chat_tone = new Audio('../tones/new_chat_req.mp3'),
        new_msg_tone = new Audio('../tones/new_msg.mp3'),
        out_of_focus = true;

    var init = function(){
        setNotificationData(notificationAjax);
    };

    function setNotificationData(callback){
        new_reqs = [];
        old_reqs = [];
        // get chat reqs
        $('#mCSB_2_container > .con').each(function(i,ele){
            var con = {
                id: $(ele).data('id'),
                status: $(ele).find('.status').data('id'),
                unread_count: $(ele).find('.unread-count').data('id')
            };
            new_reqs.push(con);
        });
        // get old chat
        $('#mCSB_3_container > .con').each(function(i,ele){
            var con = {
                id: $(ele).data('id'),
                status: $(ele).find('.status').data('id'),
                unread_count: $(ele).find('.unread-count').data('id')
            };
            old_reqs.push(con);
        });
        var present_data = {
            new_reqs: new_reqs,
            old_reqs: old_reqs,
            _token: csrf_token
        };
        callback(present_data);
    }

    var notificationAjax = function(data){
        $.ajax({
            url: 'getNotifications',
            method: 'post',
            data: data,
            async: true
        }).done(function(response){
            // remove cons that were assigned to other exec
            if(response.assigned_to_others!==undefined){
                $.each(response.assigned_to_others,function(i,ele){
                    if($('#con'+ele.id).length==1){
                        $('#con'+ele.id).remove();
                    }
                });
            }
            // append new chat requests
            if(response.new_reqs!==undefined){
                $.each(response.new_reqs,function(i,ele){
                    var $chat_reqs = $('#mCSB_2_container');
                    if($chat_reqs.find('#con'+ele.id).length==1){
                        return true;
                    }
                    var $con_tmp = $('#templates > .con').clone(),
                        status = ele.status,
                        id = ele.id;
                    $con_tmp.attr('id','con'+id).data('id',id);
                    $con_tmp.find('.status').data('id',status).addClass(getClassNameForStatus(status));
                    $con_tmp.find('.client-name').append(ele.id);
                    $con_tmp = setUnreadCount($con_tmp,ele.unread_count);
                    $chat_reqs.append($con_tmp);
                    scrollToBottom($('#chat-reqs'));
                });
                if(response.new_reqs.length>0) new_chat_tone.play();
            }
            // move newly assigned cons from chat reqs to old chats
            if(response.new_to_old!==undefined){
                $.each(response.new_to_old,function(i,ele){
                    var $o_con = $('#mCSB_2_container > #con'+ele.id);
                    if($o_con.length==0){
                        return true;
                    }
                    if($('#mCSB_3_container > #con'+ele.id).length==1){
                        return true;
                    }
                    var $con = $o_con.clone(),
                        status = ele.status;
                    $con.find('.status').data('id',status).addClass(getClassNameForStatus(status));
                    $con = setUnreadCount($con,ele.unread_count);
                    $('#mCSB_3_container').append($con);
                    scrollToBottom($('#old-cons'));
                    $o_con.remove();
                });
            }
            // update status/unread count
            if(response.status_unread_updates!==undefined){
                var new_msg_count = 0;
                $.each(response.status_unread_updates,function(i,ele){
                    var $con = $('#con'+ele.id);
                    if($con.length==0){
                        return true;
                    }
                    var status = ele.status,
                        $status = $con.find('.status').data('id',status),
                        new_class = getClassNameForStatus(status),
                        unread_count = ele.unread_count,
                        $unread_count = $con.find('.unread-count');
                    if($status.hasClass('online') && new_class!='online'){
                        $status.removeClass('online').addClass(new_class);
                    } else if($status.hasClass('offline') && new_class!='offline'){
                        $status.removeClass('offline').addClass(new_class);
                    } else{
                        $status.addClass(new_class);
                    }
                    if($unread_count.data('id')!=unread_count){
                        if($unread_count.data('id')<unread_count) new_msg_count++;
                        $unread_count.data('id',unread_count).html(unread_count);
                        if(unread_count==0){
                            $unread_count.hide();
                        } else{
                            $unread_count.show();
                        }
                    }
                });
                if(new_msg_count>0) new_msg_tone.play();
            }
            console.log(response);
            init();
        }).fail(function(responseObj){
            console.log(responseObj);
        });
    };

    init();

    function getClassNameForStatus(status){
        if(status==0){
            return 'offline';
        } else if(status==1){
            return 'online';
        }
    }

    function setUnreadCount($con,unread_count){
        var $unread_count = $con.find('.unread-count');
        $unread_count.data('id',unread_count);
        if(unread_count==0){
            $unread_count.css('display','none');
        } else if(unread_count>0){
            $unread_count.css('display','inline-block');
        }
        return $con;
    }

    // load messages of a conversation
    $(document).on('click','.con',function(){
        var $this = $(this);
        $.ajax({
            url: 'getChatMessages',
            method: 'get',
            data: {chat_id: $this.data('id')}
        }).done(function(response){
            $this.find('.unread-count').hide();
            $container.html('');
            var first_unread_msg_id, unread_exists = false;
            if(response.first_unread_msg_id !== undefined){
                first_unread_msg_id = response.first_unread_msg_id;
                unread_exists = true;
            }
            $.each(response.msgs,function(i,ele){
                if(ele.msg_by=='client'){
                    if(unread_exists && ele.id==first_unread_msg_id){
                        var $clone = $('#templates > #unread-msg-notification ').clone();
                        $clone.attr('id','unread-notification');
                        $container.append($clone);
                        unread_exists = false;
                    }
                    $container.append('<div class="by-receiver">' + ele.message + '<div class="time">'+ele.created_at+'</div></div><div></div>');
                } else if(ele.msg_by=='exe'){
                    $container.append('<div class="by-me">' + ele.message + '<div class="time">'+ele.created_at+'</div></div><div></div>');
                }
            });
            to_id = response.chat.chat_token;
            $('#client-id').html(response.chat.id);
            $('#chat-box').show();
            scrollToBottom($('#msg-container'));
            if(listen_xhr !== undefined){
                listen_xhr.abort();
            }
            listen();
        }).fail(function(responseObj){
            console.log(responseObj);
        });
    });

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
            to_id: to_id,
            message: message,
            _token: csrf_token
        };
        $.ajax({
            url: 'sendMessage',
            method: 'post',
            data: chat_data,
            async: true
        }).done(function(response){
            $container.append('<div class="by-me">' + message + '<div class="time">'+getTimestamp()+'</div></div><div class="break"></div>');
            scrollToBottom($('#msg-container'));
            $('#msg').val('');
        }).fail(function(responseObj){
            $container.append('<div class="unable-to-send">' + message + '<div class="time">'+getTimestamp()+'</div></div><div class="break"></div>');
            scrollToBottom($('#msg-container'));
        });
        $('#msg').val('');
    }

    var listen = function(){
        var chat_data = {
            to_id: to_id,
            received_msgs : received_msgs
        };
        listen_xhr = $.ajax({
            url: 'listen',
            method: 'get',
            async: true,
            data: chat_data
        }).done(function(response){
            console.log(response);
            received_msgs = [];
            // if new messages arrive
            $.each(response,function(i,ele){
                // append unread message notification if out of focus
                if(out_of_focus && $container.find('#unread-notification').length==0){
                    var $clone = $('#templates > #unread-msg-notification ').clone();
                    $clone.attr('id','unread-notification');
                    $container.append($clone);
                }
                // append messages
                $container.append('<div class="by-receiver">' + ele.message + '<div class="time">'+ele.created_at+'</div></div><div class="break"></div>');
                scrollToBottom($('#msg-container'));
                received_msgs.push(ele.id);
            });
            listen();
        }).fail(function(responseObj){
            console.log(responseObj);
        });
    };

    $(document).on('click','.close',function(){
        if(listen_xhr!==undefined){
            listen_xhr.abort();
        }
        $('#chat-box').hide();
    });

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

    $('#msg').blur(function(){
        out_of_focus = true;
        console.log('out of focus');
    });

    $('#msg').focus(function(){
        console.log('in focus');
        out_of_focus = false;
        // hide unread messages notification area
        if($container.find('#unread-notification').length>0){
            $container.find('#unread-notification').fadeOut('slow');
            $container.find('#unread-notification').remove();
        }
    });

});