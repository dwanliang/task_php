<?php

namespace app\api\controller;

use think\Controller;
use app\api\model\Dakamodel;
use think\Request;

class Daka extends Home {
    public function daka_list(){
        $data=$this->params;
        $db=new Dakamodel();
        $uid=$data['uid'];
        if(isset($data['id'])){//打卡详情
            $data=$db->daka_con($data);
            if($data){
                $this->return_msg(200,'成功',$data);
            }
        } else{//打卡列表
            $data=$db->daka_list($data);
            if($data){
                $this->return_msg(200,'成功',$data);
            }
        }
    }
    public function my_dakacon(){
        $data=$this->params;
        $db=new Dakamodel();
        $day_time_start=strtotime($data['date']);
        $day_time_end=$day_time_start+24*60*60;
        $res=$db->alias('td')
            ->where('td.id',$data['id'])
            ->rightJoin('groups_user gu','td.groups_id=gu.groups_id')
            ->leftJoin('daka_user du','du.user_id=gu.user_id and du.daka_time BETWEEN '.$day_time_start.' and '.$day_time_end.' and du.daka_id='.$data['id'].'')
            ->leftJoin('ts_user tu','tu.id=gu.user_id')
            ->field('du.daka_time,du.state,tu.user_name,tu.user_head,tu.id user_id')
            ->select();
        foreach ($res as $k=>$v){
            if($v['daka_time']){
                $res[$k]['daka_time']=date('H:i:s',$v['daka_time']);
            }

        }
        if($res){
            $this->return_msg(200,'success',$res);
        }
        $this->return_msg(0,'error');
    }
    public function my_daka(){
        $data=$this->params;
        $db=new Dakamodel();
        $uid=$data['uid'];
        $page=$data['page'];
        $size=$data['size'];
        $ress=$db->alias('dk')->where(['dk.user_id'=>$uid])
            ->leftjoin('groups gr','dk.groups_id=gr.id')
            ->join('ts_user us','dk.user_id=us.id')
            ->order(['dk.add_time'=>'desc'])
            ->field('dk.*,us.user_name,gr.groups_name')
            ->paginate($size, false,['page'=>$page]);
        $res=$ress->items();
        foreach ($res as $k=>$v){
            $res[$k]['time']=date('Y-m-d H:i:s',$v['add_time']);
            if($v['type']==1){//指定日期
                $res[$k]['time_start']=strtotime(date('Y-m-d',$v['add_time']) .$v['time_start'])*1000;
                $res[$k]['time_end']=strtotime(date('Y-m-d',$v['add_time']) .$v['time_end'])*1000;
            }else if($v['type']==2){//每日
                $res[$k]['time_start']=strtotime(date('Y-m-d '.$v['time_start']))*1000;
                $res[$k]['time_end']=strtotime(date('Y-m-d '.$v['time_end']))*1000;
            }else{//自定义星期
                $dates=explode(',',$v['date']);
                foreach ($dates as $date){
                    $week=intval(date('w'));if($week==0)$week=7;$date=intval($date);
                    if($week==$date){//判断星期几
                        $res[$k]['time_start']=strtotime(date('Y-m-d '.$v['time_start']))*1000;
                        $res[$k]['time_end']=strtotime(date('Y-m-d '.$v['time_end']))*1000;
                        break;
                    }else if($date>$week){
                        $res[$k]['time_start']=strtotime(date('Y-m-d '.$v['time_start'],strtotime("+".$date-$week." day")))*1000;
                        $res[$k]['time_end']=strtotime(date('Y-m-d '.$v['time_end'],strtotime("+".$date-$week." day")))*1000;
                        break;
                    }else if($dates[count($dates)-1]<$week){//最后一个都小于当前星期
                        $res[$k]['time_start']=strtotime(date('Y-m-d '.$v['time_start'],strtotime("+".(7-$week+$dates[0])." day")))*1000;
                        $res[$k]['time_end']=strtotime(date('Y-m-d '.$v['time_end'],strtotime("+".(7-$week+$dates[0])." day")))*1000;
                        break;
                    }
                }
            }
        }
        $data=[
            'data'=>$res,
            'total'=>$ress->total(),//总数
            'last_page'=>$ress->lastPage(),//总页数
            'current_page'=>$ress->currentPage()//当前页数
        ];
        $this->return_msg(200,'success',$data);
    }

    /**
     * notes: 打卡
     * date: 2021/2/3
     * time: 9:54
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function daka(){
        $data=$this->params;
        $db=new Dakamodel();
        $add=[
            'daka_id'=>$data['daka_id'],
            'user_id'=>$data['uid'],
            'daka_time'=>time(),
            'state'=>1
        ];
        $ss=db('daka_user')
            ->where(['user_id'=>$data['uid'],'daka_id'=>$data['daka_id']])
            ->where('daka_time','between',[strtotime(date('Y-m-d 00:00:00')),time()])
            ->find();
        $daka=$db->daka_find(['id'=>$data['daka_id']]);
        if($daka){
            if($daka['time_start']>date('H:i')){
                $this->return_msg(0,'打卡还未开始！');
            }
            if($daka['time_end']<=date('H:i')){
                $this->return_msg(0,'打卡失败，打卡时间超过规定时间');
            }
        }
        if($ss) $this->return_msg(0,'请勿重复打卡!');
        $res=$db->daka_user($add);
        if($res){
            $this->return_msg(200,'打卡成功');
        }
        $this->return_msg(0,'打卡失败');
    }

    public function daka_delete(){
        $data=$this->params;
        $db=new Dakamodel();
        $where=$data['ids'];
        $res=$db->daka_delete($where);
        if($res){
            $this->return_msg(200,'删除成功');
        }
        $this->return_msg(0,'删除失败');
    }
    /**
     * notes: 添加打卡
     * date: 2021/2/3
     * time: 9:55
     */
    public function daka_add(){
        $data=$this->params;
        $db=new Dakamodel();
        $add=[
            'groups_id'=>$data['groups_id'],
            'user_id'=>$data['uid'],
            'time_start'=>$data['time_start'],
            'time_end'=>$data['time_end'],
            'type'=>$data['type'],
            'daka_con'=>$data['daka_con'],
            'add_time'=>time()
        ];
        $add['date']='';
        if($data['type']==3){
            $add['date']=$data['date'];
        }else if($data['type']==1){
            $add['date']=date('Y-m-d');
        }
        $res=$db->daka_add($add);
        if($res){
            $this->return_msg(200,'添加成功');
        }
        $this->return_msg(0,'添加失败');
    }

}
