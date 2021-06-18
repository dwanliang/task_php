<?php
namespace app\admin_api\controller;

use app\admin_api\model\Usermodel;
use think\Exception;

class User extends Home{

    public function user_list(){
        $data=$this->params;
        $db=new Usermodel();
        if(isset($data['id'])){
            $user_info=$db->where('id',$data['id'])->field('id,user_name,user_head,email,source')->find();
            $user_info['user_head']=[
                ['name'=>'','url'=>$user_info['user_head'],]
            ];
        }else{
            $where=[];
            if(isset($data['user_name'])){
                if($data['user_name'])$where[] = ['user_name','=',$data['user_name']];
            }
            if(isset($data['dongjie'])){
                if($data['dongjie']!==null)$where[] = ['dongjie','=',$data['dongjie']];
            }
            if(isset($data['source'])){
                if($data['source']!=='')$where[] = ['source','=',$data['source']];
            }
            $page=$data['page'];
            $limit=$data['limit'];
            $user_info=$db
                ->where($where)
                ->order('id desc')
                ->field("id,user_name,user_head,source,email,only_id,from_unixtime(user_time,'%Y-%m-%d') as add_time,ip,dongjie")
                ->paginate($limit, false,['page'=>$page]);
        }
        if($user_info){
            $this->return_msg(200,'success',$user_info);
        }
        $this->return_msg(0,'not Data');
    }
    public function delete(){
        $data=$this->params;
        $db=new Usermodel();
        $where=$data['id'];
        try {
            $res=$db->user_delete($where);
        }catch (Exception $e){
            $this->return_msg(0,$e,'删除用户');
        }
        $this->return_msg(200,'删除成功',[],'删除用户');
    }
    public function dongjie(){
        $data=$this->params;
        $db=new Usermodel();
        $state=$data['state'];
        if($state==1){
            $state=0;
        }else{
            $state=1;
        }
        try {
            $res=$db->where('id',$data['id'])->update(['dongjie'=>$state]);
        }catch (Exception $e){
            $this->return_msg(0,'error',[],'冻结用户');
        }
        $this->return_msg(200,'success',[],'冻结用户');
    }
    public function add(){
        $data=$this->params;
        $db=new Usermodel();
        $username=$data['username'];
        $password=$data['password'];
        $res=$db->add($username,$password);
        if($res['code']==200){
            $this->return_msg(200,$res['msg'],[],'添加用户');
        }
        $this->return_msg(0,$res['msg'],[],'添加用户');
    }
    public function edit(){
        $data=$this->params;
        $db=new Usermodel();
        $username=$data['username'];
        $email=$data['email'];
        $file = request()->file('file');
        $edit=[
            'user_name'=>$username,
            'email'=>$email,
        ];
        if($file){
            $path="/upload/user_head";
            $user_head=$this->uploadImg($file,$path);
            if(!$user_head){
                $this->return_msg(0,'',[],'编辑用户');
            }
            $edit['user_head']=yuming.$user_head;
        }
        if(isset($data['password'])){
            $salt                 = rand(0, 99999);  //生成盐
            $pwd                  = sha1($data['password'] . $salt);
            $edit['salt']         = $salt;
            $edit['user_password']     = $pwd;
        }
        try {
            $where=['id'=>$data['id']];
            $res=$db->edit($where,$edit);
        }catch (Exception $e){
            $this->return_msg(0,'编辑失败,错误原因:'.$e->getMessage(),[],'编辑用户');
        }
        $this->return_msg(200,'编辑成功',[],'编辑用户');
    }

}