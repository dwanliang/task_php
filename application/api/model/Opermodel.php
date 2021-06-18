<?php

namespace app\api\model;

use think\Model;
use think\Db;

class Opermodel extends Model{

    public function task_oper($data,$oper_data){
        $where=['task_id'=>$data['task_id'],'type'=>$data['type'],'user_id'=>$data['uid']];
        $oper=db('ts_oper')->where($where)->find();
        if(!$oper){
            $res=$this->oper_insert($oper_data);
        }else{
            $update=['state'=>$data['state']];
            $res=$this->oper_edit($update,$where);
        }
        return $res;
    }
    public function oper_edit($update,$where){
        return $res=db('ts_oper')->where($where)->update($update);
    }
    public function oper_insert($oper_data){
        return $res=db('ts_oper')
            ->insert($oper_data);
    }
}
