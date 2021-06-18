<?php

namespace app\api\model;

use think\Model;

class Chatroommodel extends Model
{
    protected $table='chatroom';
    protected $autoWriteTimestamp = true;

    /**
     * notes: 创建聊天室
     * date: 2021/1/29
     * time: 14:09
     * @param $uid
     * @param $oid
     * @param $type
     * @return int|string
     */
    public function chatroom_add($uid,$oid,$type){
        $id=$this->insertGetId([
            'user_id'=>$uid,
            'send_id'=>$oid,
            'add_time'=>time(),
            'type'=>$type
        ]);
        return $id;
    }

    /**
     * notes: 添加聊天内容
     * date: 2021/1/29
     * time: 14:09
     * @param $data
     * @return int|string
     */
    public function chat_con_add($data){
        $id=db('chat_con')->insertGetId([
            'chat_con'=>$data['chat_con'],
            'room_id'=>$data['room_id'],
            'type'=>$data['type'],
            'user_id'=>$data['uid'],
            'add_time'=>time()
        ]);
        //恢复聊天室状态
        db('room_state')->where(['room_id'=>$data['room_id'],'state'=>2])->update(['state'=>1]);
        return $id;
    }

    /**
     * notes: 聊天内容
     * date: 2021/1/29
     * time: 14:08
     * @param $dat
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function chat_con($dat){
        $max_id=$dat['max_id'];$where='';
        if($dat['page']>1){
            $where='cc.id<='.$max_id;
        }
//        if($dat['lunxun']==2){
//            $where='cc.user_id!='.$dat['uid'];
//        }
        $res=db('chat_con')->alias('cc')
            ->where('room_id',$dat['room_id'])->where($where)
            ->join('ts_user tu','tu.id=cc.user_id')
            ->order('add_time desc')
            ->field('cc.id,cc.user_id,cc.chat_con,cc.add_time,cc.type,tu.user_name,tu.user_head')
            ->paginate($dat['size'], false,['page'=>$dat['page']]);

        $data=$res->items();
        $data=$this->add_time($data);
        foreach ($data as $k=>$v){
            $data[$k]['iden']=2;
            if($dat['uid']==$v['user_id']){
                $data[$k]['iden']=1;
            }
        }
        $user=db('ts_user')->where('id',$dat['uid'])->field('user_name,id,user_head')->find();
        if($dat['max_id']>0){
            $data=array_replace($data);
        }
        $data=[
            'data'=>$data,
            'total'=>$res->total(),//总数
            'last_page'=>$res->lastPage(),//总页数
            'current_page'=>$res->currentPage()//当前页数
        ];
        if($dat['page']==1 && count($res)>0){
            $data['max_id']=$data['data'][0]['id'];
        }
        $data['user']=$user;
        return $data;
    }

    /**
     * notes: 删除聊天室
     * date: 2021/1/29
     * time: 14:07
     * @param $data
     * @return int|string
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function room_delete($data){
        foreach (explode(',',$data['room_id']) as $k=>$v){
            $room_state=db('room_state')->where(['room_id'=>$v,'user_id'=>$data['uid']])->field('id,state')->find();
            if($room_state){
                $res=db('room_state')->where(['room_id'=>$v,'user_id'=>$data['uid']])->update(['state'=>2]);
            }else{
                $res=db('room_state')->insert(['state'=>2,'room_id'=>$v,'user_id'=>$data['uid']]);//开启聊天室状态
            }
            if(!$res){
                return false;
            }
        }
        return true;
    }

    /**
     * notes: 判断聊天室是否存在，
     * date: 2021/1/29
     * time: 14:07
     * @param $data
     * @return int|mixed|string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function char_where($data){
        $uid=$data['uid'];
        if(isset($data['user_id'])){
            $user_id=$data['user_id'];
            $res=$this->where('user_id='.$uid.' and send_id='.$user_id.' and type=1 or user_id='.$user_id.' and send_id='.$uid.' and type=1')
                ->find();
            if(empty($res)){
                $id=$this->chatroom_add($uid,$user_id,1);
                return $id;
            }else{
                $room_state=db('room_state')->where(['room_id'=>$res['id'],'user_id'=>$data['uid']])->field('id,state')->find();
                if($room_state)
                    if($room_state['state']==2)
                        db('room_state')->where('id',$room_state['id'])->update(['state'=>1]);//开启聊天室状态
            }
        }else{
            $groups_id=$data['groups_id'];
            $res=$this->where(['send_id'=>$groups_id,'type'=>2])
                ->find();
            if(empty($res)){
                $id=$this->chatroom_add($uid,$groups_id,2);
                return $id;
            }
        }

        return $res['id'];
    }

    /**
     * notes: 聊天室列表
     * date: 2021/1/29
     * time: 14:08
     * @param $uid
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function chat_list($uid){
        $res=$this->alias('rm')
            ->where('rm.user_id='.$uid.' and rm.type=1 or rm.send_id='.$uid.' and rm.type=1 or rm.send_id in (select gu.groups_id from groups_user gu where user_id='.$uid.') and rm.type=2')
            ->where('not exists(select 1 from room_state rs where rs.user_id='.$uid.' and rs.room_id=rm.id and rs.state=2)')
            ->leftjoin('groups gr','rm.send_id=gr.id and rm.type=2')
            ->leftjoin('ts_user tu','tu.id=rm.send_id and rm.type=1 and rm.send_id!='.$uid.' or tu.id=rm.user_id  and rm.user_id!='.$uid.'')
            ->Join('chat_con cc','cc.room_id=rm.id and cc.id =(select max(cc.id) from chat_con cc where cc.room_id=rm.id)')
            ->leftjoin('ts_user us','us.id=cc.user_id')
            ->order('cc.add_time desc')
            ->field('rm.id room_id,rm.type,gr.groups_name,gr.id team_id,gr.head_por,tu.user_name,tu.user_head,cc.chat_con,cc.add_time,cc.type con_type,us.user_name chat_username')
            ->select();
        foreach ($res as $k=>$v){
            if($v['con_type']==2){
                $res[$k]['chat_con']='[图片]';
            }
        }
        $res=$this->add_time($res);
        if($res){
            return $res;
        }
        return false;
    }

    /**
     * notes: 时间戳转指定日期
     * date: 2021/1/29
     * time: 14:08
     * @param $res
     * @return mixed
     */
    public function add_time($res){
        $today=mktime(0,0,0,date('m'),date('d'),date('Y'));//今日开始时间戳
        $beginYesterday=mktime(0,0,0,date('m'),date('d')-1,date('Y'));//昨日开始时间戳
        $yearday=mktime(0, 0, 0, 1,1, date("Y"));//今年开始时间戳
        foreach ($res as $k=>$v){
            if($v['add_time']<$yearday){
                $res[$k]['add_time']=date('Y年m月d日',$v['add_time']);
            }else if($v['add_time']<$beginYesterday){
                $res[$k]['add_time']=date('m月d日',$v['add_time']);
            }else if($v['add_time']<$today){
                $res[$k]['add_time']='昨天';
            }else if(date('H',$v['add_time'])<12){
                $res[$k]['add_time']='上午'.date('H:i',$v['add_time']);
            }else if(date('H',$v['add_time'])>13){
                $res[$k]['add_time']='下午'.date('H:i',$v['add_time']);
            }else{
                $res[$k]['add_time']='中午'.date('H:i',$v['add_time']);
            }
        }
        return $res;
    }

    public function chatroom_see($where){
        $res=$this->where($where)->select();
        return $res;
    }
    public function chatroom_find($where){
        $res=$this->where($where)->find();
        return $res;
    }
    public function chatroom_edit($where,$update){
        $res=db($this->table)->where($where)->update($update);
        return $res;
    }
}
