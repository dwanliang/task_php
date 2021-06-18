<?php

namespace app\admin_api\model;

use think\Model;

class Usermodel extends Model {
    protected $table='ts_user';
    protected $autoWriteTimestamp = true;

    public function user_find($where){
        $res=$this->where($where)->find();
        return $res;
    }
    public function user_upadte($where,$update){
        $user_data=db($this->table)->where($where)->update($update);
        return $user_data;
    }
    public function user_delete($delete){
        $res=db($this->table)->delete($delete);
        return $res;
    }
    public function add($username,$password){
        $num=rand(1,6);
        $user_head=yuming.'/upload/user_head/head'.$num.'.jpg';//默认头像
        $only_id   = setonly(6);//生成随机加好友id
//        $ip = request()->ip();//获取ip地址

        $add       = [
            'only_id'   => $only_id,
            'user_name' => $username,
            'user_time' => time(),
            'user_head' => $user_head,
            'type'      => 1,
        ];
        $salt                 = rand(0, 99999);  //生成盐
        $pwd                  = sha1($password . $salt);
        $add['user_password'] = $pwd;
        $add['salt']          = $salt;
        $where=['user_name'=>$username];
        $user=$this->user_find($where);
        if($user){
            return ['code'=>0,'msg'=>'账号已存在'];
        }
        $res=$this->insert($add);
        return ['code'=>200,'msg'=>'添加成功','data'=>$res];
    }
    public function edit($where,$edit){
        $res=$this->where($where)->update($edit);
        return $res;
    }
}