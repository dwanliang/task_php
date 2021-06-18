<?php

namespace app\api\controller;

use think\Controller;
use think\Request;
use app\api\model\Notemodel;

class Note extends Home {
    /**
     * notes: 课表列表
     * date: 2021/1/12
     * time: 15:21
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function note_list(){
        $data=$this->params;
        $db=new Notemodel();
        if(isset($data['note_id'])){
            $data=$db->where('id',$data['note_id'])
                ->find();
        }else{
            $where=['user_id'=>$data['uid']];
            $res=$db->where($where)
                ->order('add_time desc')
                ->field('id,add_time,note_title,note_con')
                ->paginate(8, false,['page'=>$data['page']]);
            $data=$res->items();
            foreach ($data as $k=>$v){
                $data[$k]['add_time']=date('Y-m-d H:i:s',$v['add_time']);
            }
            $data=[
                'data'=>$data,
                'total'=>$res->total(),//总数
                'last_page'=>$res->lastPage(),//总页数
                'current_page'=>$res->currentPage()//当前页数
            ];

        }
        if($data){
            $this->return_msg(200,'成功',$data);
        }
        $this->return_msg(0,'无数据');
        $this->return_msg(0,'无数据');
    }

    public function note_add(){
        $data=$this->params;
        $db=new Notemodel();
        $add=[
            'user_id'=>$data['uid'],
            'note_con'=>$data['note_con'],
            'note_title'=>$data['note_title'],
            'add_time'=>time(),
        ];
        $res=$db->note_add($add);
        if($res){
            $this->return_msg(200,'添加成功',$res);
        }
        $this->return_msg(0,'添加失败');
    }
    public function note_delete(){
        $data=$this->params;
        $db=new Notemodel();
        $where=$data['ids'];
        $res=$db->note_delete($where);
        if($res){
            $this->return_msg(200,'删除成功');
        }
        $this->return_msg(0,'删除失败');
    }
    public function note_edit(){
        $data=$this->params;
        $db=new Notemodel();
        $edit=[
            'user_id'=>$data['uid'],
            'note_con'=>$data['note_con'],
        ];
        $where=['id'=>$data['note_id']];
        $res=$db->note_edit($where,$edit);
        if($res){
            $this->return_msg(200,'修改成功',$res);
        }
        $this->return_msg(0,'修改失败');
    }
}
