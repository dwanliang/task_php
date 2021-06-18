<?php
namespace app\admin_api\model;

use think\Model;

class Bannersmodel extends Model{
    protected $table='ts_banners';
    protected $autoWriteTimestamp = true;

    public function banners_find($where){
        $res=$this->where($where)->find();
        return $res;
    }
    public function banners_upadte($where,$update){
        $user_data=db($this->table)->where($where)->update($update);
        return $user_data;
    }
    public function add($add){
        $res=$this->insert($add);
        return $res;
    }
    public function edit($where,$edit){
        $res=$this->where($where)->update($edit);
        return $res;
    }
    public function banners_delete($where){
        $res=db($this->table)->delete($where);
        return $res;
    }
}