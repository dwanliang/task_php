<?php

namespace app\admin_api\model;

use think\Model;

class Permissionsmodel extends Model {
    protected $table='ts_permissions';
    protected $autoWriteTimestamp = true;

    public function upadte($where,$update){
        $user_data=db($this->table)->where($where)->update($update);
        return $user_data;
    }
    public function add($add){
        $res=$this->insert($add);
        return $res;
    }
    public function permissions_delete($where){
        $res=db($this->table)->delete($where);
        return $res;
    }
}