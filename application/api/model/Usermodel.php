<?php

namespace app\api\model;

use think\facade\Cache;
use think\Model;

class Usermodel extends Model
{
    protected $table = 'ts_user';
    protected $autoWriteTimestamp = true;

    public function user_see($where){
        $user_data=db($this->table)->where($where)->select();
        return $user_data;
    }
    public function user_find($where){
        $user_data=db($this->table)->where($where)->find();
        return $user_data;
    }
    public function user_upadte($where,$update){
        $user_data=db($this->table)->where($where)->update($update);
        return $user_data;
    }
    public function user_new($id){
        $count=db('user_new')->where(['send_id'=>$id,'state'=>1])->count();
        if($count>0){
            return true;
        }
        return false;
    }
    public function user_new_list($data){
        $res=db('user_new')->alias('un')
            ->where(['un.send_id'=>$data['uid'],'un.type'=>1])
            ->join('ts_user tu','tu.id=un.user_id')
            ->field('un.type,un.state,tu.user_name,un.id,un.user_id')
            ->order('un.add_time desc')
            ->select();
        return $res;
    }
    public function user_new_add($data){
        $res=db('user_new')
            ->insert([
                'user_id'=>$data['uid'],
                'state'=>1,
                'type'=>1,
                'send_id'=>$data['user_id'],
                'add_time'=>time()
            ]);
        return $res;
    }
    public function user_new_update($data){
        $res=db('user_new')->where('id',$data['new_id'])->update(['state'=>$data['state']]);
        if($data['state']==2){
            $add=$this->friend_add($data['uid'],$data['user_id']);
            if($add){

            }
        }
        return $res;
    }
    public function friend_add($user_id,$friend_id){
        $res=db('friend_user')->insert([
            'user_id'=>$user_id,
            'friend_id'=>$friend_id,
            'add_time'=>time()
        ]);
        return $res;
    }
    public function user_friend($user_id){
        $zimu=['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','S','Y','Z'];
        $user_list=db('friend_user fu')
            ->where(['fu.user_id'=>$user_id])
            ->whereOr(['fu.friend_id'=>$user_id])
            ->join('ts_user tu','tu.id=fu.friend_id and fu.user_id='.$user_id.' or tu.id=fu.user_id and fu.friend_id='.$user_id.'')
//            ->order("CONVERT(tu.user_name using gbk) asc")
            ->orderRaw("convert(tu.user_name using gbk) asc")
            ->field('tu.user_name,tu.user_head,tu.id user_id')
            ->select();
        $data=[];
        foreach ($zimu as $j){
            $ss=[];$dd=[];
            $ss['letter']=$j;
            foreach ($user_list as $k=>$v){
                if(getFirstCharter($v['user_name'])==$j){
                    $dd[]=$v;
                    unset($user_list[$k]);
                }
            }
            $ss['data']=$dd;
            $data[]=$ss;
        }

        return $data;
    }
    public function pass_reset($data){

        if(isset($data['email'])){//邮箱找回密码
            $where=['email'=>$data['email']];
            $code=$data['code'];
            $email=$data['email'];
            $user_info=$this->user_find($where);
            if(!$user_info){
                $this->return_msg(0,'该邮箱不存在!');
            }
            if ($code!==Cache::get('new_code'.$email)) {
                $this->return_msg(0, '验证码错误或验证码已过期');
            }
            if($user_info['user_password']==sha1($data['password'].$user_info['salt'])){
                $this->return_msg(0,'新密码不可以和旧密码一样！');
            }
        }else{//旧密码修改密码
            $where=['id'=>$data['uid']];
            $user_info=$this->user_find($where);
            if($user_info['user_password']!==sha1($data['passwords'].$user_info['salt'])){
                $this->return_msg(0,'旧密码不正确！');
            }
            if($user_info['user_password']==sha1($data['password'].$user_info['salt'])){
                $this->return_msg(0,'新密码不可以和旧密码一样！');
            }
        }


        $salt=rand(0, 99999);//生成盐
        $pwd=sha1($data['password'].$salt);
        $update=[
            'user_password'=>$pwd,
            'salt'=>$salt
        ];
        $res=$this->user_upadte($where,$update);
        return $res;
    }
    /**
     * notes: 返回json数据
     * @param $code
     * @param string $msg
     * @param array $data
     */
    public function return_msg($code, $msg, $data = []){
        $return_data['code'] = $code;
        $return_data['msg']  = $msg;
        $return_data['data'] = $data;
        echo json_encode($return_data);die;
    }
    /**
     * notes: 添加账号
     * date: 2021/1/7
     * time: 16:08
     * @param $user_name
     * @param string $pwd
     * @param $type
     * @param $user_head
     * @return int|string
     */
    public function user_insert($data,$type){
        $user_name=$data['user_name'];
        $type=$data['type'];
        $num=rand(1,6);
        $source=$data['source'];
        $user_head=yuming.'/upload/user_head/head'.$num.'.jpg';//默认头像
        $only_id   = setonly(6);//生成随机加好友id
//        $ip = request()->ip();//获取ip地址

        $add       = [
            'only_id'   => $only_id,
            'user_name' => $user_name,
            'user_time' => time(),
            'user_head' => $user_head,
            'type'      => $type,
            'source'    => $source
//            'ip'        =>$ip,
        ];

        if($type==1) {//普通注册
            $add=$this->user_zhuce($data,$add);
        }else if($type==4){
            $add=$this->user_weixin($data,$add);
        }
        $id=$res=db($this->table)->insertGetId($add);
        return $id;
    }
    public function user_weixin($data,$add){
        $other_userid=$data['openid'];
        $where=['other_userid'=>$other_userid];
        $user=$this->user_find($where);
        if($user){
            $user_data=[
                'user_id'=>$user['id'],
                'user_name'=>$data['user_name'],
                'user_head'=>$user['user_head'],
                'token'=>model('admin_api/Loginmodel')->Getnewtoken($user['id'],$data['user_name'])
            ];
            $this->return_msg(200,'授权成功',$user_data);
        }
        $add['other_userid']=$other_userid;
        return $add;
    }
    public function user_zhuce($data,$add){
        $pwd=$data['password'];
        $email = $data['email'];
        $code  = $data['code'];
        $user_name=$data['user_name'];
        $where=['user_name'=>$user_name];
        if (!$data['password']) {
            $this->return_msg(0, '密码为空');
        }
        if ($code!==Cache::get('code'.$email)) {
            $this->return_msg(0, '验证码错误或验证码已过期');
        }
        if ($email!==Cache::get('email'.$email)) {
            $this->return_msg(0, '当前邮箱与获取验证码邮箱不一致');
        }
        $user=$this->user_find(['email'=>$email]);
        if($user){
            $this->return_msg(0, '当前邮箱已注册！');
        }
        $user=$this->user_find($where);
        if($user){
            $this->return_msg(0,'账号已存在！');
        }
        $salt                 = rand(0, 99999);  //生成盐
        $pwd                  = sha1($pwd . $salt);
        $add['user_password'] = $pwd;
        $add['salt']          = $salt;
        $add['email']=$email;
        return $add;
    }
    public function user_edit($value,$type,$where){
        if($type=='user'){//编辑用户名
            $update=[
                'user_name'=>$value
            ];
            $user=$this->user_find($update);
            $users=$this->user_find($where);
            if($user==$users){
                die;
            }
            if($user){
                echo json_encode(['code'=>0,'msg'=>'用户名已存在']);die;
            }
        }elseif ($type=='user_head'){//头像
            $update=[
                'user_head'=>$value
            ];
        }
        $res=db($this->table)->where($where)->update($update);
        return $res;
    }
}
