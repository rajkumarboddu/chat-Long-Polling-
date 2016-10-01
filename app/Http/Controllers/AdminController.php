<?php

namespace App\Http\Controllers;

use App\Chat;
use App\Message;
use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    public function doLogin(Request $request){
        $validator = Validator::make($request->all(),[
            'username' => 'required|exists:users,username',
            'password' => 'required'
        ]);
        if($validator->fails()){
            return response()->json($validator->errors()->all()[0],449);
        }
        if(Auth::attempt(['username' => $request->username, 'password' => $request->password])){
            return response()->json('Login successful', 200);
        } else{
            return response()->json('Invalid username or password',402);
        }
    }

    public function viewChatPage(){
        return view('chatPage');
    }

    public function getChatRequests(){
        $chat_reqs = DB::table('chats as c')
                        ->leftJoin('messages as m','m.from_id','=','c.chat_token')
                        ->whereNull('m.to_id')
                        ->whereNull('c.exec_id')
                        ->groupBy('c.id')
                        ->select(DB::raw('sum(case when m.read=0 then 1 else 0 end) as unread_count,
                        (case when (UNIX_TIMESTAMP()-c.last_activity)>6 then 0 else 1 end) as status'),'c.id','c.chat_token')
                        ->get();
        return $chat_reqs;
    }

    public function getOldChat(){
        $chat_reqs = DB::table('chats as c')
                    ->leftJoin('messages as m','m.from_id','=','c.chat_token')
                    ->where('m.to_id',Auth::user()->id)
                    ->where('c.exec_id',Auth::user()->id)
                    ->groupBy('c.id')
                    ->select(DB::raw('sum(case when m.read=0 then 1 else 0 end) as unread_count,
                                (case when (UNIX_TIMESTAMP()-c.last_activity)>6 then 0 else 1 end) as status'),'c.id','c.chat_token')
                    ->get();
        return $chat_reqs;
    }

    public function getChatMessages(Request $request){
        $validator = Validator::make($request->all(),[
            'chat_id' => 'required|exists:chats,id'
        ]);
        if($validator->fails()){
            return response()->json($validator->errors()->all()[0],449);
        }
        DB::beginTransaction();
        try{
            $chat = DB::table('chats')->where('id',$request->chat_id)->select('id','chat_token')->first();
            // mark unread messages as read
            DB::table('messages')
                ->where('from_id',$chat->chat_token)
                ->where('read',0)
                ->update(['read'=>1]);
            // get conversation messages
            $from_msgs = DB::table('chats as c')
                        ->join('messages as m','m.from_id','=','c.chat_token')
                        ->where('c.id',$request->chat_id)
                        ->select('message','from_id','to_id',
                            DB::raw('(case when m.from_id="'.$chat->chat_token.'" then "client" else "exe" end) as msg_by,
                            DATE_FORMAT(m.created_at,"%b %d %Y %h:%i %p") as created_at'));
            $msgs = DB::table('chats as c')
                        ->join('messages as m','m.to_id','=','c.chat_token')
                        ->where('c.id',$request->chat_id)
                        ->unionAll($from_msgs)
                        ->orderBy('created_at','asc')
                        ->select('message','from_id','to_id',
                            DB::raw('(case when m.from_id="'.$chat->chat_token.'" then "client" else "exe" end) as msg_by,
                            DATE_FORMAT(m.created_at,"%b %d %Y %h:%i %p") as created_at'))
                        ->get();
            DB::commit();
            return response()->json(['chat' => $chat, 'msgs' => $msgs],200);
        } catch(\Exception $e){
            DB::rollBack();
            return response()->json('Internal server error',500);
        }
    }

    public function saveMessage(Request $request){
        $validator = Validator::make($request->all(),[
            'to_id' => 'required|exists:chats,chat_token',
            'message' => 'required'
        ]);
        if($validator->fails()){
            return response()->json($validator->errors()->all()[0],449);
        }
        DB::beginTransaction();
        try{
            Message::create([
                'from_id' => Auth::user()->id,
                'to_id' => $request->to_id,
                'message' => $request->message
            ]);
            // update previous messages's receiver id
            Message::where('from_id',$request->to_id)->whereNull('to_id')->update(['to_id'=>Auth::user()->id]);
            // update executive id of the chat
            Chat::where('chat_token',$request->to_id)->update(['exec_id'=>Auth::user()->id]);
            DB::commit();
            return response()->json('Message sent successfully',200);
        } catch(\Exception $e){
            DB::rollBack();
            return response()->json('Internal server error', 500);
        }
    }

    public function listen(Request $request){
        $validator = Validator::make($request->all(),[
            'to_id' => 'required|exists:chats,chat_token'
        ]);
        if($validator->fails()){
            return response()->json($validator->errors()->all()[0],449);
        }
        try{
            set_time_limit(0);
            clearstatcache();
            $to_id = $request->to_id;
            $received_msgs = $request->received_msgs;
            if(count($received_msgs)>0){
                // mark them as read
                DB::table('messages')
                    ->where('from_id',$to_id)
                    ->whereIn('id',$received_msgs)->update(['read'=>1]);
            }
            while(true){
                // check for new messages
                $new_msgs = DB::table('messages')->where('from_id',$to_id)->where('read',0)->get();
                if(count($new_msgs) > 0){
                    return response()->json($new_msgs,200);
                    break;
                } else{
                    sleep(1);
                    continue;
                }
            }
        } catch(\Exception $e){
            return response()->json('Internal server error',500);
        }
    }

    public function getNotifications(Request $request){
        $new_reqs = ($request->new_reqs==null) ? [] : $request->new_reqs;
        $old_reqs = ($request->old_reqs==null) ? [] : $request->old_reqs;
        $new_and_old_reqs = array_merge($new_reqs,$old_reqs);
        $exec_id = Auth::user()->id;
        try{
            set_time_limit(0);
            clearstatcache();
            while(true){
                $updates = false;
                $res_data = [];
                $new_reqs_ids = [];
                $old_reqs_ids = [];
                $new_and_old_reqs_ids = [];
                foreach($new_reqs as $new_req){
                    $new_req_id = $new_req['id'];
                    array_push($new_reqs_ids,$new_req_id);
                    array_push($new_and_old_reqs_ids,$new_req_id);
                }
                foreach($old_reqs as $old_req){
                    $old_req_id = $old_req['id'];
                    array_push($old_reqs_ids,$old_req_id);
                    array_push($new_and_old_reqs_ids,$old_req_id);
                }
                // check for new requests
                $new_reqs_updates = DB::table('chats as c')
                            ->leftJoin('messages as m','m.from_id','=','c.chat_token')
                            ->whereNull('m.to_id')
                            ->whereNull('c.exec_id')
                            ->whereNotIn('c.id',$new_reqs_ids)
                            ->groupBy('c.id')
                            ->select(DB::raw('sum(case when (m.read is null or m.read=1) then 0 else 1 end) as unread_count,
                                (case when (UNIX_TIMESTAMP()-c.last_activity)>6 then 0 else 1 end) as status'),'c.id','c.chat_token')
                            ->get();
                if(count($new_reqs_updates)>0){
                    $res_data['new_reqs'] = $new_reqs_updates;
                    $updates = true;
                }
                // get chats that are newly assigned to exec
                $new_to_old = DB::table('chats as c')
                                ->leftJoin('messages as m','m.from_id','=','c.chat_token')
                                ->where('c.exec_id',$exec_id)
                                ->where('m.to_id',$exec_id)
                                ->whereIn('c.id',$new_reqs_ids)
                                ->groupBy('c.id')
                                ->select(DB::raw('sum(case when (m.read is null or m.read=1) then 0 else 1 end) as unread_count,
                                            (case when (UNIX_TIMESTAMP()-c.last_activity)>6 then 0 else 1 end) as status'),'c.id','c.chat_token')
                                ->get();
                if(count($new_to_old)>0){
                    $res_data['new_to_old'] = $new_to_old;
                    $updates = true;
                }
                // get if any status updates
                $status_and_unread = DB::table('chats as c')
                                    ->leftJoin('messages as m','m.from_id','=','c.chat_token')
                                    ->whereIn('c.id',$new_and_old_reqs_ids)
                                    ->groupBy('c.id')
                                    ->select(DB::raw('sum(case when (m.read is null or m.read=1) then 0 else 1 end) as unread_count,
                                    (case when (UNIX_TIMESTAMP()-c.last_activity)>6 then 0 else 1 end) as status'),'c.id','c.chat_token')
                                    ->get();
                // check any updates happened to status and unread message count
                $status_and_unread_updates = [];
                foreach($status_and_unread as $chat){
                    foreach($new_and_old_reqs as $new_and_old_req){
                        if($new_and_old_req['id']==$chat->id){
                            if($new_and_old_req['status']!=$chat->status || $new_and_old_req['unread_count']!=$chat->unread_count){
                                array_push($status_and_unread_updates,$chat);
                            }
                        }
                    }
                }
                if(count($status_and_unread_updates)>0){
                    $res_data['status_unread_updates'] = $status_and_unread_updates;
                    $updates = true;
                }
                // get new requests that are assigned to other exec
                $assigned_to_others = DB::table('chats as c')
                                        ->leftJoin('messages as m','m.from_id','=','c.chat_token')
                                        ->where('c.exec_id','<>',$exec_id)
                                        ->whereIn('c.id',$new_reqs_ids)
                                        ->groupBy('c.id')
                                        ->select('c.id','c.chat_token')
                                        ->get();
                if(count($assigned_to_others)>0){
                    $res_data['assigned_to_others'] = $assigned_to_others;
                    $updates = true;
                }
                // send notification if any
                if($updates){
                    return response()->json($res_data,200);
                    break;
                } else{
                    sleep(1);
                    continue;
                }
            }
        } catch(\Exception $e){
            return response()->json('Internal server error',500);
        }
    }

}
