<?php

namespace app\api\model;

use think\Model;

class Notemodel extends Model
{
    protected $table='ts_note';
    protected $autoWriteTimestamp = true;

    public function note_see($where){
        $res=$this->where($where)->select();
        return $res;
    }
    public function note_find($where){
        $res=$this->where($where)->find();
        return $res;
    }
    public function note_add($add){
        $res=db($this->table)->insert($add);
        return $res;
    }
    public function note_edit($where,$update){
        $res=db($this->table)->where($where)->update($update);
        return $res;
    }
    public function note_delete($delete){
        $res=db($this->table)->delete($delete);
        return $res;
    }
}
