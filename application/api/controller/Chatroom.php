<?php

namespace app\api\controller;

use app\api\model\Chatroommodel;
use think\Controller;
use think\Model;
use think\Request;
use think\session\driver\Redis;

class Chatroom extends Home{
    /**
     * notes:聊天内容
     * date: 2021/1/29
     * time: 14:19
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function chat_con(){
        $data=$this->params;
        $db=new Chatroommodel();
        if($data['lunxun']==2){
//            $chat_con['data']=$this->chat_redis_con($data);
        }else{
            $chat_con=$db->chat_con($data);
        }
        if($chat_con){
            $this->return_msg(200,'success',$chat_con);
        }
        $this->return_msg(0,'success','无数据');
    }

    /**
     * notes: 定时轮询redis聊天内容列表
     * date: 2021/1/29
     * time: 14:19
     * @param $data
     * @return array
     */
    public function chat_redis_con($data){
        $redis=new \Redis();
        $redis->connect('127.0.0.1',6379);
        $redis->select(1);
        $chat_con_list=$redis->lrange("chat_con", 0 ,-1);
        $chat_con=[];
        foreach ($chat_con_list as $k=>$v){
            $dat=json_decode($v,true);//字符串转数组
            $dat['iden']=2;
            if($dat['user_id']!=$data['uid'] && $data['room_id']==$dat['room_id']){
                $chat_con[]=$dat;
            }
        }
        return $chat_con;
    }

    /**
     * notes:添加聊天内容
     * date: 2021/1/29
     * time: 14:19
     */
    public function chat_con_add(){
        $data=$this->params;
        $db=new Chatroommodel();
        if($data['type']==2){
            $file = request()->file('file');
            $path="/upload/chat_img";
            $chat_img=$this->uploadImg($file,$path);
            $data['chat_con']=yuming.$chat_img;
        }
        $id=$db->chat_con_add($data);
        if($id){
//            $redis=new \Redis();//实例化redis
//            $redis->connect('127.0.0.1',6379);
//            $redis->select(1);
//            $redis->rpush("chat_con",$data['con']);//设置自动冻结订单redis
//            $redis->expireAt('chat_con', time()+5.10);
            $res=db('room_state')->where('room_id',$data['room_id'])->update(['state'=>1]);
            $this->return_msg(200,'success',$data['chat_con']);
        }
        $this->return_msg(0,'error');
    }

    /**
     * notes: 判断聊天室是否存在
     * date: 2021/1/29
     * time: 14:18
     * @return int|mixed|string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function chat_where(){
        $data=$this->params;
        $db=new Chatroommodel();
        $chatroomid=$db->char_where($data);
        if($chatroomid){
            return $chatroomid;
        }
    }

    /**
     * notes: 删除聊天室
     * date: 2021/1/29
     * time: 14:18
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function room_delete(){
        $data=$this->params;
        $db=new Chatroommodel();
        $res=$db->room_delete($data);
        if($res){
            $this->return_msg(200,'删除成功');
        }
        $this->return_msg(0,'删除失败');
    }

    /**
     * notes:添加聊天室
     * date: 2021/1/29
     * time: 14:19
     * @return int|string
     */
    public function chatroom_add(){
        $data=$this->params;
        $db=new Chatroommodel();
        $id=$db->chatroom_add($data['uid'],$data['user_id'],$data['type']);
        return $id;
    }
}
