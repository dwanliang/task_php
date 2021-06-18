<?php

namespace app\api\model;

use think\Model;

class Coursemodel extends Model
{
    protected $table='ts_course';
    protected $autoWriteTimestamp = true;

    public function course_see($where){
        $res=$this->where($where)->select();
        return $res;
    }
    public function course_find($where){
        $res=$this->where($where)->find();
        return $res;
    }
    public function course_add($add){
        $res=db($this->table)->insert($add);
        return $res;
    }
    public function course_edit($where,$update){
        $res=db($this->table)->where($where)->update($update);
        return $res;
    }
    public function course_delete($delete){
        $res=db($this->table)->delete($delete);
        return $res;
    }
}
