<?php

namespace app\api\controller;

use think\Controller;
use think\Exception;
use think\Request;
use app\api\model\Usermodel;
use app\api\controller\Email;
use think\facade\Cache;

class User extends Home
{
    protected function initialize(){
        parent::initialize();
    }
    public function user_list(){
        $data=$this->params;
        $db=new Usermodel();
        $user_id=$data['uid'];
        if(isset($data['user_id'])){
            $user_id=$data['user_id'];
            $friend_where=$this->friend_where($data['uid'],$data['user_id']);
            if($data['uid']==$data['user_id']){
                $friend_where=true;
            }
        }
        $res=$db
            ->field('id,user_name,user_head,only_id')
            ->find($user_id);
        if($res){
            if(isset($friend_where)){
                $res['friend_where']=$friend_where;
            }
            $this->return_msg(200,'成功',$res);
        }
        $this->return_msg(0,'失败');
    }
    /**
     * notes: 用户注册
     * date: 2021/1/7
     * time: 16:22
     */
    public function user_wx(){
        $data=$this->params;
        $db=new Usermodel();
        $url='https://api.weixin.qq.com/sns/jscode2session?appid='.AppID.'&secret='.AppSecret.'&js_code='.$data['code'].'&grant_type=authorization_code';
        $wx_userData=get_requ($url);
        $wx_userData=json_decode($wx_userData,true);
        if($wx_userData){
            $this->return_msg(200,'success',$wx_userData['openid']);
        }
        $this->return_msg(0,'error');
    }
    public function user_wxadd(){
        $data=$this->params;
        $db=new Usermodel();
        $type=$data['type'];
        $user_id=$db->user_insert($data,$type);
        if($user_id) {
            $user_data = [
                'user_id'   => $user_id,
                'user_name' => $data['user_name'],
            ];
            $this->return_msg(200, '授权成功', $user_data);
        }
        $this->return_msg(0,'授权失败');
    }
    public function user_new_add(){
        $data=$this->params;
        $db=new Usermodel();
        $res=$db->user_new_add($data);
        if($res){
            $this->return_msg(200,'成功');
        }
        $this->return_msg(0,'失败');
    }
    public function user_new_edit(){
        $data=$this->params;
        $db=new Usermodel();
        $res=$db->user_new_update($data);
        if($res){
            $this->return_msg(200,'成功');
        }
        $this->return_msg(0,'失败');
    }
    public function user_new(){
        $data=$this->params;
        $db=new Usermodel();
        try {
            $res=$db->user_new_list($data);
        }catch (Exception $e){
            $this->return_msg(0,'失败',$e);
        }
        $this->return_msg(200,'成功',$res);

    }
    public function user_add(){
        $data=$this->params;
        $db=new Usermodel();
        $user_name=$data['user_name'];
        $type=$data['type'];
        $num=rand(1,6);
        $user_head=yuming.'/upload/user_head/head'.$num.'.jpg';//默认头像

        $user_id=$db->user_insert($data,$type);

        if($user_id && $type!==1){
            $user_data=[
                'user_id'=>$user_id,
                'user_name'=>$user_name,
                'user_head'=>$user_head,
                'token'=>model('admin_api/Loginmodel')->Getnewtoken($user_id,$user_name)
            ];
            $this->return_msg(200,'注册成功',$user_data);
        }else if($user_id && $type==1){
            $this->return_msg(200,'注册成功');
        }
        $this->return_msg(0,'注册失败');
    }
    public function user_search(){
        $data=$this->params;
        $db=new Usermodel();
        $where=['only_id'=>$data['text']];
        $res=$db->where($where)->field('id,user_name name,user_head head')->select();
        if(count($res)>0){
            $this->return_msg(200,'成功',$res);
        }
        $this->return_msg(0,'无数据');
    }
    public function my_friend(){
        $data=$this->params;
        $db=new Usermodel();
        $user_friend=$db->user_friend($data['uid']);
        if($user_friend){
            $this->return_msg(200,'success',$user_friend);
        }
        $this->return_msg(0,'error');
    }
    public function friend_where($uid,$friend_id){
        $res=db('friend_user')
            ->where('user_id='.$uid.' and friend_id='.$friend_id.' or user_id='.$friend_id.' and friend_id='.$uid.'')
            ->find();
        if($res){
            return true;
        }
        return false;
    }
    /**
     * notes: 设置验证码
     * date: 2021/1/14
     * time: 11:37
     * @return mixed
     */
    public function get_code(){
        session_status();
        $data=$this->params;
        $code = rand(100000,999999);
        $toemail=$data['email'];//收件人
        $key=$data['key'];//判断是注册验证码，还是忘记密码验证码
        if($key==1){//注册
            Cache::set('code'.$toemail,$code,600);
            $code="注册验证码：".$code.",10分钟内有效。";
        }else{//忘记密码
//            session('new_code'.$toemail,$code);
            Cache::set('new_code'.$toemail,$code,600);
            $code="忘记密码验证码：".$code.",10分钟内有效。请在10分钟内完成操作";
        }
        $email=new Email();
        if(!$email->set_email($toemail,$code)){
            $this->return_msg(0,'发送验证码失败');
        }
//        session('email'.$toemail,$toemail);
        Cache::set('email'.$toemail,$toemail,600);
//        session('code_time'.$toemail,time());//设置session过期时间
        $this->return_msg(200,'验证码已发送');
    }
    /**
     * notes: 登录接口
     * date: 2021/1/7
     * time: 16:42
     */
    public function login(){
        $data=$this->params;
        $db=new Usermodel();
        $where=['user_name'=>$data['user_name']];
        $user_info=$db->user_find($where);
        if(!$user_info){
            $this->return_msg(0,'账号不存在');
        }
        if($user_info['dongjie']){
            $this->return_msg(0,'账号已被冻结！');
        }
        $pwd=sha1($data['password'].$user_info['salt']);
        if($user_info['user_password']!==$pwd){
            $this->return_msg(0,'密码错误');
        }
        $ip = request()->ip();//获取ip地址
        $update=[
          'ip'=>$ip,
        ];
        $db->user_upadte($where,$update);
        $user_data=[
            'user_id'=>$user_info['id'],
            'user_name'=>$user_info['user_name'],
            'user_head'=>$user_info['user_head'],
        ];
        $token=model('admin_api/Loginmodel')->Getnewtoken($user_info['id'],$user_info['user_name']);
        if($token){
            $user_data['token']=$token;
            $this->return_msg(200,'登录成功',$user_data);
        }
        $this->return_msg(0,'登录成失败');
    }

    /**
     * notes:忘记密码
     * date: 2021/1/7
     * time: 16:43
     */
    public function pass_reset(){
        $data=$this->params;
        $db=new Usermodel();
        $res=$db->pass_reset($data);//修改密码
        if($res){
            $this->return_msg(200,'修改密码成功');
        }
        $this->return_msg(0,'修改密码失败');
    }

    /**
     * notes: 用户修改
     * date: 2021/1/12
     * time: 10:29
     */
    public function user_edit(){
        $data=$this->params;
        $db=new Usermodel();
        $value=$data['data'];
        $type=$data['type'];
        $user_id=$data['uid'];

        $where=['id'=>$user_id];
        try {
            $res=$db->user_edit($value,$type,$where);
        }catch (Exception $e){
            $this->return_msg(0,'修改失败');
        }
        $this->return_msg(200,'修改成功');
    }

    /**
     * notes: 头像
     * date: 2021/1/8
     * time: 16:49
     */
    public function user_head(){
        $data=$this->params;
        $db=new Usermodel();
        $file = request()->file('file');
        $path="/upload/user_head";
        $names=time().rand(100,1000);
        $user_head=$this->uploadImg($file,$path);
        $where=['id'=>$data['uid']];
        $type='user_head';
        $res=$db->user_edit(yuming.$user_head,$type,$where);
        if($res){
            $this->return_msg(200,'上传成功');
        }
        $this->return_msg(0,'上传失败');
    }
    public function qq_login(){
        $app_id = "101931082";//替换即可
        $app_secret = "3722817a3da85c2f0980e9b29e5a12a9";//替换即可
        //成功授权后的回调地址
        $my_url = urlencode("http://47.107.243.176/task/tp/public/Index.php/api/Admin/qq_login");

        //获取code
        $code = $_GET['code'];

        //Step2：通过Authorization Code获取Access Token
        $token_url = "https://graph.qq.com/oauth2.0/token?grant_type=authorization_code&client_id=".$app_id."&redirect_uri=".$my_url."&client_secret=".$app_secret."&code=".$code."";

        //file_get_contents() 把整个文件读入一个字符串中。
        $response = file_get_contents($token_url);

        //Step3:在上一步获取的Access Token，得到对应用户身份的OpenID。
        $params = array();
        //parse_str() 函数把查询字符串（'a=x&b=y'）解析到变量中。
        parse_str($response,$params);
        $graph_url = "https://graph.qq.com/oauth2.0/me?access_token=".$params['access_token']."";
//        $str = file_get_contents($graph_url);
        $str=get_requ($graph_url);
        // --->找到了字符串：callback( {"client_id":"YOUR_APPID","openid":"YOUR_OPENID"} )
        //
        // strpos() 函数查找字符串在另一字符串中第一次出现的位置，从0开始
        if(strpos($str,"callback")!==false){
            $lpos = strpos($str,"(");
            // strrpos() 函数查找字符串在另一字符串中最后一次出现的位置。
            $rpos = strrpos($str,")");
            //substr(string,start,length) 截取字符串某一位置
            $str = substr($str,$lpos+1,$rpos-$lpos-1);
        }
        // json_decode() 函数用于对 JSON 格式-->{"a":1,"b":2,"c":3,"d":4,"e":5}<--的字符串进行解码，并转换为 PHP 变量,默认返回对象
        $user = json_decode($str);
        // dump($user->openid);die;　
//        session('openid',$user->openid,SESSIONINDEX);
        Cache::set('openid',$user->openid,600);

        //Step4: 调用OpenAPI接口,得到json数据，要转换为数组

        $arr = "https://graph.qq.com/user/get_user_info?access_token=".$params['access_token']."&oauth_consumer_key=".$app_id."&openid=".$user->openid."";
        //加上true，得到数组，否则默认得到对象
        $res = json_decode(get_requ($arr),true);
//         dump($res['nickname']);
        dump($res);die;

        //下方写逻辑即可

    }
}
