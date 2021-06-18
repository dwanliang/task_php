<?php

namespace app\api\model;

use think\Model;
use think\Db;

class Teammodel extends Model
{
    protected $table='groups';
    protected $autoWriteTimestamp = true;

    public function team_update($update,$where){
        return $res=db($this->table)->where($where)->update($update);
    }
    public function team_insert($insert_data){
        return $res=db($this->table)
            ->insertGetId($insert_data);
    }
    public function team_find($where){
        return $res=db($this->table)->where($where)
            ->find();
    }
    public function team_user_insert($insert_data){
        return $res=db('groups_user')
            ->insert($insert_data);
    }
    public function team_delete($where){
        return $res=db($this->table)->where($where)
            ->delete();
    }
    public function team_user_delete($where){
        return $res=db('groups_user')->where($where)
            ->delete();
    }
    public function team_user_update($update,$where){
        return $res=db('groups_user')->where($where)->update($update);
    }
    public function msg_insert($add){
        $res=Db::table('team_msg')->insert($add);
        return $res;
    }
    public function team_new($id){
        $count=db('team_new')->where(['groups_id'=>$id,'state'=>1])->count();
        if($count>0){
            return true;
        }
        return false;
    }
    public function team_admin($uid){
        $team_user=db('groups_user')->where('user_id',$uid)->where('state','<',3)->select();
        return $team_user;
    }
    public function team_newlist($id,$uid){
        $time=time()-7*24*60*60;//只显示一周的消息
        $user=db('groups_user')->where(['groups_id'=>$id,'user_id'=>$uid])->find();
        $where=[];
        if($user['state']>2){
            $where[]=['tu.type','in',[2,3]];
        }
        $res=db('team_new')->alias('tu')
            ->where([['groups_id','=',$id]])
            ->where($where)
            ->limit(10)
            ->leftJoin('ts_user u','u.id=tu.user_id')
            ->field('tu.id,tu.explain,tu.type,u.user_name,tu.state')
            ->order('add_time desc')
            ->select();
        if($res){
            db('team_new')->where(['groups_id'=>$id,'type'=>2])->where('state',1)->update(['state'=>2]);
        }
        return $res;
    }
    public function team_new_edit($where,$update){
        $res=db('team_new')->where($where)->update($update);
        return $res;
    }
    public function team_user_add($add){
        $res=db('groups_user')->insert($add);
        return $res;
    }
    public function team_new_add($add){
        $res=db('team_new')->insert($add);
        return $res;
    }
    public function team_user_deletes($data,$where){
        Db::startTrans();//开启事务
        $res=db('groups_user')->field('state')->where($where)->find();
        if(isset($data['user_id'])){//踢出团队
            if($res['state']>2){//身份太低
                return json(['code'=>0,'msg'=>'请求错误！']);
            }
            $where=['groups_id'=>$data['id'],'user_id'=>$data['user_id']];
        }else{//退出团队
            if($res['state']==1){//组长
                $num=db('groups_user')->where('groups_id',$data['id'])->count();
                if($num==1){
                    $delete=db('groups')->where('id',$data['id'])->delete();
                    if($delete){
                        Db::commit();
                        return json(['code'=>200,'msg'=>'退出成功']);
                    }else{
                        Db::rollback();
                    }
                }
                $ste=db('groups_user')->where('state',2)->where('groups_id',$data['id'])->order('id asc')->find();
                $update=[
                    'state'=>1,
                    'ide_name'=>'组长'
                ];
                if(!$ste){
                    $ste=db('groups_user')->where('state',3)->where('groups_id',$data['id'])->order('id asc')->find();
                }
                $res=$this->team_user_update($update,['id'=>$ste['id']]);
            }
        }
        if($res){
            $res=$this->team_user_delete($where);
            if($res){
                Db::commit();
                return json(['code'=>200,'msg'=>'退出成功']);
            }else{
                Db::rollback();
            }
        }else{
            Db::rollback();
            return json(['code'=>0,'msg'=>'退出失败']);
        }
    }
}
