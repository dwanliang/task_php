<?php

namespace app\api\controller;

use think\Controller;
use think\Exception;
use think\Request;
use app\api\model\Coursemodel;

class Course extends Home {
    /**
     * notes: 课表列表
     * date: 2021/1/12
     * time: 15:21
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function course_list(){
        $data=$this->params;
        $db=new Coursemodel();
        if(isset($data['course_id'])){
            $res=$db->where('id',$data['course_id'])
                ->field('courseName,courseAddress,courseTeacher,time_start,time_end,week')
                ->find();
            $res['time_end']=explode(':',$res['time_end']);
            $res['time_start']=explode(':',$res['time_start']);
        }else{
            $where=['user_id'=>$data['uid']];
            $res=$db->where($where)
                ->order('time_start asc')
                ->field('id,courseName,courseAddress,courseTeacher,time_start,time_end,week')
                ->select();
            foreach($res as $k=>$v) {
                $res[$k]['time']=$v['time_start'].'-'.$v['time_end'];
                unset($res[$k]['time_start']);
                unset($res[$k]['time_end']);
            }
        }


        if($res){
            $this->return_msg(200,'成功',$res);
        }
        $this->return_msg(200,'无数据');
    }

    public function course_add(){
        $data=$this->params;
        $db=new Coursemodel();
        if($data['week']>7){
            $this->return_msg(200,'参数错误');
        }
        $add=[
            'user_id'=>$data['uid'],
            'week'=>$data['week'],
            'time_end'=>$data['time_end'],
            'time_start'=>$data['time_start'],
            'courseName'=>$data['courseName'],
            'courseAddress'=>$data['courseAddress'],
            'courseTeacher'=>$data['courseTeacher'],
        ];
        $res=$db->course_add($add);

        if($res){
            $this->return_msg(200,'添加成功',$res);
        }
        $this->return_msg(200,'添加失败');
    }
    public function course_delete(){
        $data=$this->params;
        $db=new Coursemodel();
        $where=$data['ids'];
        $res=$db->course_delete($where);
        if($res){
            $this->return_msg(200,'删除成功');
        }
        $this->return_msg(0,'删除失败');
    }
    public function course_edit(){
        $data=$this->params;
        $db=new Coursemodel();
        if($data['week']>7){
            $this->return_msg(200,'参数错误');
        }
        $edit=[
            'user_id'=>$data['uid'],
            'week'=>$data['week'],
            'time_end'=>$data['time_end'],
            'time_start'=>$data['time_start'],
            'courseName'=>$data['courseName'],
            'courseAddress'=>$data['courseAddress'],
            'courseTeacher'=>$data['courseTeacher'],
        ];
        $where=['id'=>$data['course_id']];
        try {
            $res=$db->course_edit($where,$edit);
        }catch (Exception $e){
            $this->return_msg(200,'修改失败');
        }
        $this->return_msg(200,'修改成功',$res);
    }
}
