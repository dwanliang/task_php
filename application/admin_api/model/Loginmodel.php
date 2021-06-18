<?php

namespace app\admin_api\model;

use Firebase\JWT\JWT;
use think\facade\Cache;
use think\Model;

class Loginmodel extends Model {
    protected $table='ts_admin';
    protected $autoWriteTimestamp = true;

    public function user_find($where){
        $res=$this->where($where)->find();
        return $res;
    }
    public function user_upadte($where,$update){
        $user_data=db($this->table)->where($where)->update($update);
        return $user_data;
    }
    public function Getnewtoken($uid,$username){
        $toke = array(
            "Time" => time(),
            "OverTime" => time()+60*60*24,
            'UserInfo'=>[
                'uid'=>$uid,
                'name'=>$username
            ]
        );
        $jwt = JWT::encode($toke, example_key);
        Cache::set($jwt,$uid,60*60*24);
        return $jwt;
    }
}