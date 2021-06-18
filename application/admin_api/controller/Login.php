<?php
namespace app\admin_api\controller;

use app\admin_api\model\Loginmodel;
use Firebase\JWT\JWT;
use think\facade\Cache;

class Login extends Home{

    public function login(){
        $data=$this->params;
        $username=$data['username'];
        $password=$data['password'];

        $db=new Loginmodel();
        $where=['username'=>$username];
        $user_info=$db->user_find($where);
        if(!$user_info){
            return $this->return_msg(400,'账号不存在',[]);
        }
        $pwd=sha1($password.$user_info['salt']);
        if($user_info['password']!==$pwd){
            return $this->return_msg(400,'密码错误',[]);
        }
        $ip = request()->ip();//获取ip地址
        $update=[
            'ip'=>$ip,
            'login_time'=>time()
        ];
        $db->user_upadte($where,$update);//更新登录ip和登录时间
        $token=$db->Getnewtoken($user_info['id'],$username);
        if($token){
            Cache::set($token,$user_info['id'],60*60*24);
            return $this->return_msg(200,'success',['token'=>$token,'userInfo'=>$user_info['username']]);
        }
        return $this->return_msg(0,'error',[]);
    }
}