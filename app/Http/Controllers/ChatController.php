<?php

namespace App\Http\Controllers;

use App\Chat;
use App\Message;
use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ChatController extends Controller
{
    public function init(Request $request){
        set_time_limit(0);
        clearstatcache();
        $chat = null;
        $chat_token = $request->cookie('chat_token');
        DB::beginTransaction();
        try{
            if($chat_token){
                $chat = Chat::where('chat_token',$chat_token)->first();
            }
            // if a first time user create a record
            if($chat==null){
                $chat_token = self::generateRandomString(16);
                $chat = Chat::create([
                    'chat_token' => $chat_token,
                    'last_activity' => time()
                ]);
            } else{
                // update last activity
                self::updateLastActivity($chat_token);
            }
            $to_id = $request->to_id;
            $from_id = $request->from_id;
            $received_msgs = $request->received_msgs;
            // mark received msgs as read
            if(count($received_msgs)>0 && $from_id && $to_id){
                DB::table('messages')
                    ->where('from_id',$to_id)
                    ->where('to_id',$from_id)
                    ->whereIn('id',$received_msgs)->update(['read'=>1]);
            }
            DB::commit();
            // send created ID in case of initialization
            if($from_id==null){
                return response()->json(['from_id'=>$chat->chat_token],200)->withCookie(cookie()->forever('chat_token', $chat_token));
            }
            while(true){
                // check for new messages
                $new_msgs = DB::table('messages')->where('to_id',$from_id)->where('read',0);
                if($to_id){
                    $new_msgs = $new_msgs->where('from_id',$to_id);
                }
                $new_msgs = $new_msgs->get();
                if(count($new_msgs) > 0){
                    $data = [
                        'from_id' => $new_msgs[0]->to_id,
                        'to_id' => $new_msgs[0]->from_id,
                        'new_msgs' => $new_msgs
                    ];
                    return response()->json($data,200);
                    break;
                } else{
                    sleep(1);
                    continue;
                }
            }
        } catch(\Exception $e){
            DB::rollBack();
            return response()->json('Internal server error', 500);
        }
    }

    public function test(){

    }

    public function saveMessage(Request $request){
        $validator = Validator::make($request->all(),[
            'from_id' => 'required|exists:chats,chat_token',
            'message' => 'required'
        ]);
        if($validator->fails()){
            return response()->json($validator->errors()->all()[0],449);
        }
        try{
            $to_id = ($request->to_id=='') ? null : $request->to_id;
            Message::create([
                'from_id' => $request->from_id,
                'to_id' => $to_id,
                'message' => $request->message
            ]);
            return response()->json('Message sent successfully',200);
        } catch(\Exception $e){
            return response()->json('Internal server error', 500);
        }
    }

    public function pingServer(Request $request){
        try{
            // update last activity
            self::updateLastActivity($request->cookie('chat_token'));
            return response()->json('...',200);
        } catch(\Exception $e){
            return response()->json('Internal server error', 500);
        }
    }

    public static function updateLastActivity($chat_token){
        Chat::where('chat_token',$chat_token)->update(['last_activity'=>time()]);
    }

    public static function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
