$(document).ready(function(){

    var $container = $('#msg-container'),
        to_id = '',
        received_msgs = [],
        csrf_token = $('input[name="_token"]').val(),
        listen_xhr,
        new_reqs= [],
        old_reqs = [];

    var init = function(){
        setNotificationData(notificationAjax);
    };

    function setNotificationData(callback){
        new_reqs = [];
        old_reqs = [];
        // get chat reqs
        $('#chat-reqs > .con').each(function(i,ele){
            var con = {
                id: $(ele).data('id'),
                status: $(ele).find('.status').data('id'),
                unread_count: $(ele).find('.unread-count').data('id')
            };
            new_reqs.push(con);
        });
        // get old chat
        $('#old-cons > .con').each(function(i,ele){
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
                    var $chat_reqs = $('#chat-reqs');
                    if($chat_reqs.find('#con'+ele.id).length==1){
                        return true;
                    }
                    var $con_tmp = $('#templates > .con').clone(),
                        status_class,
                        status = ele.status,
                        id = ele.id;
                    $con_tmp.attr('id','con'+id).data('id',id);
                    $con_tmp.find('.status').data('id',status).addClass(getClassNameForStatus(status));
                    $con_tmp.find('.client-name').append(ele.id);
                    $con_tmp = setUnreadCount($con_tmp,ele.unread_count);
                    $chat_reqs.append($con_tmp);
                    scrollToBottom($chat_reqs);
                });
            }
            // move newly assigned cons from chat reqs to old chats
            if(response.new_to_old!==undefined){
                $.each(response.new_to_old,function(i,ele){
                    var $o_con = $('#chat-reqs > #con'+ele.id);
                    if($o_con.length==0){
                        return true;
                    }
                    if($('#old-cons > #con'+ele.id).length==1){
                        return true;
                    }
                    var $con = $o_con.clone(),
                        status = ele.status;
                    $con.find('.status').data('id',status).addClass(getClassNameForStatus(status));
                    $con = setUnreadCount($con,ele.unread_count);
                    $('#old-cons').append($con);
                    scrollToBottom($('#old-cons'));
                    $o_con.remove();
                });
            }
            // update status/unread count
            if(response.status_unread_updates!==undefined){
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
                        $unread_count.data('id',unread_count).html(unread_count);
                        if(unread_count==0){
                            $unread_count.hide();
                        } else{
                            $unread_count.show();
                        }
                    }
                });
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
            $.each(response.msgs,function(i,ele){
                if(ele.msg_by=='client'){
                    $container.append('<div class="by-receiver">' + ele.message + '<div class="time">'+ele.created_at+'</div></div><div></div>');
                } else if(ele.msg_by=='exe'){
                    $container.append('<div class="by-me">' + ele.message + '<div class="time">'+ele.created_at+'</div></div><div></div>');
                }
            });
            to_id = response.chat.chat_token;
            $('#client-id').html(response.chat.id);
            $('#chat-box').show();
            scrollToBottom($container);
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
            scrollToBottom($container);
            $('#msg').val('');
        }).fail(function(responseObj){
            $container.append('<div class="unable-to-send">' + message + '<div class="time">'+getTimestamp()+'</div></div><div class="break"></div>');
            scrollToBottom($container);
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
                // append messages
                $container.append('<div class="by-receiver">' + ele.message + '<div class="time">'+ele.created_at+'</div></div><div class="break"></div>');
                scrollToBottom($container);
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
        var $first_child = ($div.children().first().length == 1) ? $div.children().first() : undefined;
        var $last_child = ($div.children().last()  == 1) ? $div.children().last() : undefined;
        if($first_child !== undefined && $last_child !== undefined){
            var height = Math.abs($last_child.position().top)+Math.abs($first_child.position().top);
            $div.animate({scrollTop: height}, 'slow');
        }
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