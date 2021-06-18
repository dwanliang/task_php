<?php

namespace app\api\controller;

use think\worker\Server;
use Workerman\Worker as Work;

class Websocket extends Server
{
    protected $socket = 'websocket://127.0.0.1:2346';
    protected $processes = 1;
    protected $uidConnections = [];
    protected $teamConnections = array();
    static $count  = 0;
    /**
     * 收到信息
     * @param $connection
     * @param $data
     */
    public function onMessage($connection, $data){
        if(!isset($connection->chat_id))
        {
            $chat_info=json_decode($data,true);
            if(isset($chat_info['chat_id'])){
                // 没验证的话把第一个包当做uid（这里为了方便演示，没做真正的验证）
                $connection->chat_id = $chat_info['chat_id'];
                $connection->uid = $chat_info['uid'];

                /* 保存uid到connection的映射，这样可以方便的通过uid查找connection，
                 * 实现针对特定uid推送数据
                 */
                $this->uidConnections[$connection->chat_id][$connection->uid] = $connection;

            }else{
                $connection->uid = $chat_info['uid'];
                $this->uidConnections[$connection->uid] = $connection;
            }
            return;
        }else{
            $chat_info=json_decode($data,true);
            $chat_info['iden']=2;
            $message=json_encode($chat_info);
            $room_id=$chat_info['room_id'];
            //向聊天室发送新消息
            $this->sendMessageByChatid($room_id,$chat_info['user_id'],$message);//
            //向不在聊天室内的人发送新消息
            return;
            $user_idinfo=$this->room_data($room_id);
            if(is_array($user_idinfo)){
                foreach ($user_idinfo as $k=>$res){
                    $this->sendMessageByUser_id($res['user_id']);
                }
            }else{
                $this->sendMessageByUser_id($user_idinfo,$message);
            }
        }
    }

    /**
     * 当连接建立时触发的回调函数
     * @param $connection
     */
    public function onConnect($connection)
    {
//        print_r($connection);
//        $this->ss[$connection->uid] = $connection;
    }

    /**
     * 当连接断开时触发的回调函数
     * @param $connection
     */

    public function onClose($connection)
    {
        if(isset($connection->chat_id))
        {
            if(isset($connection->uid)){
                // 连接断开时删除映射
                $data = '用户 '.$connection->uid.'退出房间';
//            $this->broadcast($data);
                unset($this->uidConnections[$connection->chat_id][$connection->uid]);
            }
        }
    }
    /**
     * 当客户端的连接上发生错误时触发
     * @param $connection
     * @param $code
     * @param $msg
     */
    public function onError($connection, $code, $msg)
    {
        echo "error $code $msg\n";
    }

    /**
     * 每个进程启动
     * @param $worker
     */
    public function onWorkerStart($worker)
    {
//        print_r($worker);
    }


//    function broadcast($message){
//        foreach($this->uidConnections as $connection)
//        {
//            $connection->send($message);
//        }
//    }


    /**
     * notes: 向聊天室发送即时新消息
     * date: 2021/2/5
     * time: 14:28
     * @param $chat_id
     * @param $user_id
     * @param $message
     * @return bool
     */
    function sendMessageByChatid($chat_id,$user_id,$message){
        if(isset($this->uidConnections[$chat_id])){
            foreach ($this->uidConnections[$chat_id] as $k=>$v){
                if($k==$user_id){
                    continue;
                }
                $connection = $v;
                $connection->send($message);
            }
            return true;
        }
        return false;
    }

    /**
     * notes: 向指定用户发送新消息
     * date: 2021/2/5
     * time: 14:30
     * @param $user_id
     * @param $message
     * @return bool
     */
    public function sendMessageByUser_id($user_id,$message){
        if(isset($this->uidConnections[$user_id])){
            $connection = $this->uidConnections[$user_id];
            $connection->send($message);
            return true;
        }
        return false;
    }

    public static function room_data($id){
        $room_info=db('chatroom')->where('id',$id)->find();
        if($room_info['type']==1){
            return $room_info['send_id'];
        }else{
            return db('groups_user')->where('groups_id',$room_info['send_id'])->field('user_id')->select();
//            return $Team_UserData;
        }
    }
}