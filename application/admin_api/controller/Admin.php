<?php
namespace app\admin_api\controller;

use app\admin_api\model\Adminmodel;
use app\admin_api\model\Rolemodel;
use think\facade\Cache;

class Admin extends Home{
    /**
     * notes:管理员列表
     * date: 2021/2/23
     * time: 16:45
     */
    public function admin_list(){
        $data = $this->params;
        $db   = new Adminmodel();
        if (isset($data['id'])) {
            $res = $db
                ->field('id,username,describes')
                ->find($data['id']);
        } else {
            $limit = $data['limit'];
            $page  = $data['page'];
            $res   = $db
                ->order('id asc')
                ->field("id,username,login_time,ip,describes,from_unixtime(add_time,'%Y-%m-%d') as add_time,from_unixtime(login_time,'%Y-%m-%d %H:%i:%s') as login_time")
                ->paginate($limit, false, ['page' => $page]);
        }
        $this->return_msg(200, 'success', $res);
    }

    /**
     * notes:添加管理员
     * date: 2021/2/23
     * time: 16:45
     */
    public function add(){
        $data=$this->params;
        $db=new Adminmodel();
        
        $username=$data['username'];
        $password=$data['password'];
        $salt=rand(0, 99999);  //生成盐
        $password=sha1($password.$salt);

        $add['username']=$username;
        $add['password']=$password;
        $add['salt']=$salt;
        $add['add_time']=time();
        $admin_info=$db->where('username',$username)->find();
        if($admin_info){
            $this->return_msg(0,'账号已存在',[],'添加管理员');
        }
        $res=$db->add($add);
        if($res){
            return $this->return_msg(200,'success');
        }
        return $this->return_msg(400,'error');
    }

    /**
     * notes:编辑管理员
     * date: 2021/2/23
     * time: 16:45
     */
    public function edit(){
        $data=$this->params;
        $db=new Adminmodel();

        $username=$data['username'];
        $describes=$data['describes'];

        $edit=[
            'username'=>$username,
            'describes'=>$describes,
        ];
        $header=$this->request->header();

        if($data['id']==1){
            $this->return_msg(0, '不能编辑超级管理员', [], '编辑管理员');
        }
        if(isset($data['password'])){
            $salt                 = rand(0, 99999);  //生成盐
            $pwd                  = sha1($data['password'] . $salt);
            $edit['salt']         = $salt;
            $edit['password']     = $pwd;
        }

        try {
            $where=['id'=>$data['id']];
            $res=$db->edit($where,$edit);
        }catch (Exception $e){
            $this->return_msg(0,'编辑失败,错误原因:'.$e->getMessage(),[],'编辑管理员');
        }
        $this->return_msg(200,'编辑成功',[],'编辑管理员');
    }

    /**
     * notes:管理员角色
     * date: 2021/2/23
     * time: 16:44
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function admin_role(){
        $data=$this->params;
        $db=new Rolemodel();
        $res=$db->field('id,role_name')
            ->order('role_sort asc')
            ->select();
        $checkeds=db('admin_role')
            ->where('admin_id',$data['admin_id'])
            ->select();
        $checked=[];
        foreach ($checkeds as $k=>$re){
            $checked[]=$re['role_id'];
        }
        $info['data']=$res;
        $info['checked']=$checked;
        $this->return_msg(200,'success',$info);
    }

    /**
     * notes:设置管理员角色
     * date: 2021/2/23
     * time: 16:44
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function setrole(){
        $data    = $this->params;
        $db      = new Adminmodel();
        $add     = [];
        $admin_id = $data['admin_id'];
        foreach ($data['admin_ids'] as $k => $re) {
            $add[] = ['admin_id' => $admin_id, 'role_id' => $re,'add_time'=>time()];
        }
        foreach ($data['checked'] as $k => $re) {
            $delete = ['admin_id' => $admin_id, 'role_id' => $re];
            $del=db('admin_role')->where($delete)->delete();
        }
        try {
            $res = db('admin_role')->insertAll($add);
        }catch (Exception $e){
            $this->return_msg(0,'error'.$e->getMessage(),[],'设置管理员角色');
        }
        $this->return_msg(200,'success',[],'设置管理员角色');
    }

    /**
     * notes:删除管理员
     * date: 2021/2/23
     * time: 16:44
     */
    public function delete()
    {
        $data  = $this->params;
        $db    = new Adminmodel();
        $where = $data['id'];
        $header=$this->request->header();
        $token=$header['dwanverification'];
        $ids=explode(',',$data['id']);
        if($data['id']==1 || in_array(1,$ids)){
            $this->return_msg(0, '不能删除超级管理员', [], '删除管理员');
        }
        if($data['id']==Cache::get($token) || in_array(Cache::get($token),$ids)){
            $this->return_msg(0, '不能删除自己', [], '删除管理员');
        }
        try {
            $res = $db->admin_delete($where);
        } catch (Exception $e) {
            $this->return_msg(0, $e, '删除管理员');
        }
        $this->return_msg(200, '删除成功', [], '删除管理员');
    }

    public function admin_log_list(){
        $data  = $this->params;
        $db    = new Adminmodel();
        $limit = $data['limit'];
        $page  = $data['page'];
        $where=[];
        $where=$this->log_isset($where,$data);
//        print_r($where);die;
        $res   = $db->alias('ta')
            ->join('admin_log al','al.admin_id=ta.id')
            ->where($where)
            ->order('al.time desc')
            ->field("ta.id,ta.username,al.*,from_unixtime(al.time,'%Y-%m-%d %H:%i:%s') as time")
            ->paginate($limit, false, ['page' => $page]);
        $admin_info=$db->field('username,id')
            ->select();
        $data=$res->items();
        $data=[
            'data'=>$data,
            'total'=>$res->total(),//总数
            'last_page'=>$res->lastPage(),//总页数
            'current_page'=>$res->currentPage(),//当前页数
            'admin_list'=>$admin_info,//管理员列表
        ];
        $this->return_msg(200, 'success', $data);
    }
    public function log_isset($where,$data){
        if(isset($data['log_name'])){
            $where[] = ['al.log_name','like','%' . $data['log_name'] . '%'];
        }
        if(isset($data['log_state'])){
            $where[] = ['al.log_state','=',$data['log_state']];
        }
        if(isset($data['admin_id'])) {
            $where[] = ['al.admin_id', '=', $data['admin_id']];
        }
        if(isset($data['date'])) {
            $time1=strtotime($data['date'][0]);
            $time2=strtotime($data['date'][1])+60*60*24-1;
            $where[] = ['al.time','between',[$time1,$time2]];
//            $where[] = ['al.time','between',[$time1,$time2]];
        }
        return $where;
    }
}