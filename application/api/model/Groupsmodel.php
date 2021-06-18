<?php

namespace app\api\model;

use think\Model;

class Groupsmodel extends Model
{
    protected $table='groups';
    protected $autoWriteTimestamp = true;


    public function groups_find($id=1){
        $res=db($this->table)->find($id);
        return $res;
    }
    public function groups_see($id){
        $res=db($this->table)->select($id);
        return $res;
    }
}
