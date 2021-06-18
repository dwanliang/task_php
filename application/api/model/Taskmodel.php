<?php

namespace app\api\model;

use think\Model;
use think\Db;

class Taskmodel extends Model
{
    protected $table='ts_task';
    protected $autoWriteTimestamp = true;

    public function oper_edit($update,$where){
        return $res=db('ts_task')->where($where)->update($update);
    }
    public function oper_insert($oper_data){
        return $res=db('ts_task')
            ->insert($oper_data);
    }
    public function task_delete($where){
        $res=db($this->table)->delete($where);
        return $res;
    }
}
