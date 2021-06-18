<?php
namespace app\api\controller;

use app\api\model\Teammodel;
use think\Db;
use think\Exception;

class Team extends Home {

    /**
     * notes: 团队列表
     * date: 2021/1/4
     * time: 15:30
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function team_list(){
        $data=$this->params;
        $db=new Teammodel();
        if(isset($data['id'])){
            //团队信息
            $groups_data=$db->field('groups_name,code')->find($data['id']);
            $msg=$db->team_new($data['id']);
            $groups_data['new']=$msg;
            //团队用户列表
            $user_list=Db::table('groups_user gr')->where('groups_id',$data['id'])
                ->join('ts_user us','gr.user_id=us.id')
                ->order('gr.state asc')
                ->field('us.user_name,us.user_head,gr.user_id,gr.state,gr.ide_name')
                ->select();
            $user_data=[];
            foreach ($user_list as $k=>$v){
                if($v['user_id']==$data['uid']){
                    $user_data=$v;
                    $user_list[$k]['me']='true';
                }
            }
            $data=[
                'user_list'=>$user_list,
                'groups_data'=>$groups_data,
                'user'=>$user_data
            ];
        }else{
            $where=[];
            if(!$data['state']){
                $where[]=['gu.state','in',[1,2]];
            }else{
                $where[]=['gu.state','=',3];
            }
            $data=$db->alias('gr')
                ->join('groups_user gu','gu.groups_id=gr.id')
                ->order(['gr.time'=>'desc','gu.state'=>'asc'])
                ->where('gu.user_id',$data['uid'])
                ->where($where)
                ->field('gr.groups_name,gr.head_por,gr.id,gu.state,gu.ide_name')
                ->select();
            foreach($data as $k=>$v){
                $msg=$db->team_new($v['id']);
                $data[$k]['new']=$msg;
                $num=Db::table('groups_user')->where('groups_id',$v['id'])->count('groups_id');
                $data[$k]['team_num']=$num;//团队人数
            }
        }
        if($data){
            $this->return_msg(200,'成功',$data);
        }
        $this->return_msg(0,'无数据');
    }
    public function team_new(){
        $data=$this->params;
        $db=new Teammodel();
        if(isset($data['id'])){
            $new_data=$db->team_newlist($data['id'],$data['uid']);
        }else{
            $new_data=$db->team_new($data['id']);
        }

        $this->return_msg(200,'成功',$new_data);
    }
    public function team_search(){
        $data=$this->params;
        $db=new Teammodel();
        $where=['code'=>$data['text']];
        $res=$db->where($where)->field('id,groups_name name,head_por head')->select();
        if(count($res)>0){
            $this->return_msg(200,'成功',$res);
        }
        $this->return_msg(0,'无数据');
    }
    /**
     * notes:团队添加
     * date: 2020/12/29
     * time: 16:18
     */
    public function team_add(){
        $data=$this->params;
        $formmodel=new Teammodel();
        $file = request()->file('file');
        $path="/upload/team_head";
        $team_head=$this->uploadImg($file,$path);
        $insert_data=[
            'user_id'=>$data['uid'],
            'groups_name'=>$data['groups_name'],
            'time'=>time(),
            'code'=>setonly(8),
            'head_por'=>yuming.$team_head
        ];
        Db::startTrans();
        $id=$formmodel->team_insert($insert_data);
        $add=[
            'groups_id'=>$id,
            'user_id'=>$data['uid'],
            'state'=>1,
            'ide_name'=>'组长',
            'add_time'=>time()
        ];
        if($id){
            $res=$formmodel->team_user_insert($add);
            if($res){
                Db::commit();
                $this->return_msg(200,'添加成功');
            }else{
                Db::rollback();
                $this->return_msg(0,'添加失败');
            }
        }
        Db::rollback();
        $this->return_msg(0,'添加失败');
    }
    /**
     * notes: 删除团队
     * date: 2021/1/5
     * time: 10:04
     */
    public function team_delete(){
        $data=$this->params;
        $db=new Teammodel();
        $where=['id'=>$data['id'],'user_id'=>$data['uid']];
        $db->team_delete($where);
    }

    public function team_user_add(){
        $data=$this->params;
        $db=new Teammodel();
        Db::startTrans();
        if(!isset($data['state'])){
            $add=[
              'groups_id'=>$data['groups_id'],
              'add_time'=>time(),
              'user_id'=>$data['uid'],
              'explain'=>'加入团队',
              'type'=>1,
            ];
            $red=db('team_new')->where(['user_id'=>$data['uid'],'groups_id'=>$data['groups_id'],'type'=>1,'state'=>1])->where('state','in',[1,2])->find();
            if($red){
                $this->return_msg(200,'请勿重复申请加入团队');
            }
            $res=$db->team_new_add($add);
            $msg='成功';
        }else{
            $where=['id'=>$data['new_id']];
            $update=[
                'state'=>$data['state']
            ];

            $res=$db->team_new_edit($where,$update);
            $new_data=db('team_new')->where($where)->find();
            if($res && $data['state']==2){
                $add=[
                    'groups_id'=>$new_data['groups_id'],
                    'user_id'=>$new_data['user_id'],
                    'ide_name'=>'成员',
                    'add_time'=>time()
                ];
                $res=$db->team_user_add($add);
            }
            $msg='已同意';
            if($data['state']==3){
                $msg='已拒绝';
            }
        }
        if($res){
            Db::commit();
            $this->return_msg(200,$msg);
        }
        Db::rollback();
        $this->return_msg(0,'失败1');
    }
    /**
     * notes: 退出团队
     * date: 2021/1/5
     * time: 10:05
     */
    public function team_user_delete(){
        $data=$this->params;
        $db=new Teammodel();
        $where=['groups_id'=>$data['id'],'user_id'=>$data['uid']];
        $res=$db->team_user_deletes($data,$where);
        return $res;
    }

    /**
     * notes: 修改团队信息
     * date: 2021/1/5
     * time: 11:28
     */
    public function team_edit(){
        $data=$this->params;
        $db=new Teammodel();
        $update=[
            'groups_name'=>$data['con']
        ];
        $where=['id'=>$data['id']];
        $red=db('groups_user')->where(['groups_id'=>$data['id'],'user_id'=>$data['uid']])->find();
        if(!$red || $red['state']>2){
            $this->return_msg(0,'没有该权限');
        }
        try {
            $res=$db->team_update($update,$where);
        }catch (Exception $e){
            $this->return_msg(0,'设置失败');
        }
        if($res){
            $this->return_msg(200,'设置成功');
        }

    }

    /**
     * notes: 设置团队管理员
     * date: 2021/1/6
     * time: 15:04
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function team_admin(){
        $data=$this->params;
        $db=new Teammodel();
        $ide_name='组员';
        if($data['state']==2){
            $ide_name='管理员';
            $re=$db->find($data['id']);
        }
        $update=[
            'state'=>$data['state'],
            'ide_name'=>$ide_name
        ];
        $where=['groups_id'=>$data['id'],'user_id'=>$data['user_id']];
        $res=$db->team_user_update($update,$where);
        if($res){
            $this->return_msg(200,'设置成功');
        }
        $this->return_msg(0,'设置失败');
    }

    /**
     * notes: 团队公告
     * date: 2021/1/6
     * time: 15:11
     */
    public function team_msg(){
        $data=$this->params;
        $res=Db::table('team_msg m')->where('groups_id',$data['groups_id'])
            ->leftjoin('ts_user u','u.id=m.user_id')
            ->order('msg_time desc')
            ->field('m.*,u.user_name')
            ->select();
        if($res){
            foreach ($res as $k=>$v){
                $res[$k]['msg_time']=date('Y-m-d H:i:m',$v['msg_time']);
            }
            $this->return_msg(200,'成功',$res);
        }
        $this->return_msg(0,'无数据');
    }
    /**
     * notes: 团队公告添加
     * date: 2020/12/19
     * time: 17:10
     */
    public function msg_add(){
        if(request()->isOptions()){
            exit;
        }
        $data=$this->params;
        $db=new Teammodel();
        $where=['user_id'=>$data['uid'],'groups_id'=>$data['groups_id']];
        $res=db('groups_user')->where($where)->find();
        if($res['state']>2) {
            $this->return_msg(0, '权限不够');
        }
        $add=[
            'user_id'=>$data['uid'],
            'msg_con'=>$data['msg_con'],
            'groups_id'=>$data['groups_id'],
            'msg_time'=>time()
        ];
        $res=$db->msg_insert($add);
        if($res){
            $this->return_msg(200,'添加成功');
        }
    }
}