<?php
namespace app\api\controller;

use app\api\model\Teammodel;
use app\api\model\Usermodel;
use app\api\model\Chatroommodel;

class Mail extends Home {

    public function mail_index(){
        $data=$this->params;
        $user_id=$data['uid'];
        $team_new=$this->teams_new($user_id);
        $user_new=$this->user_new($user_id);
        $new_data=[
            'team_new'=>$team_new,
            'user_new'=>$user_new,
        ];
        $chatroom=new Chatroommodel();
        $chat_list=$chatroom->chat_list($data['uid']);
        $new_data['chat_list']=$chat_list;
        $this->return_msg(200,'success',$new_data);
    }
    public function teams_new($uid){
        $team=new Teammodel();
        $team_user=$team->team_admin($uid);
        foreach ($team_user as $k => $v){
            $new=$team->team_new($v['groups_id']);
            if($new){
                return true;
            }
        }
        return false;
    }
    public function user_new($uid){
        $user=new Usermodel();
        $new=$user->user_new($uid);
        return $new;
    }
}