<?php

namespace app\api\controller;

use app\api\model\Groupsmodel;

class Groups extends Home{

    public function groups_list(){
        $data=$this->params;
        $db=new Groupsmodel();
        if(isset($data['groups_id'])){
            $data=$db->groups_see($data['groups_id']);
        }else{
            $data=$db->alias('gr')
                ->join('groups_user gu','gu.groups_id=gr.id and gu.user_id='.$data['uid'].' and state in (1,2)')
//            ->where('user_id',$data['uid'])
//            ->where('state','in',[1,2])
                ->field('groups_name,gr.id')
                ->select();
        }

        if($data){
            return $this->return_msg(200,'成功',$data);
        }
        return $this->return_msg(0,'无数据');
    }
    public function groups_find(){

    }
}