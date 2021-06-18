<?php

namespace app\api\model;

use think\Model;
use think\Db;

class Formmodel extends Model
{
    protected $table='ts_form';
    protected $autoWriteTimestamp = true;

    public function oper_edit($update,$where){
        return $res=db('ts_task')->where($where)->update($update);
    }
    public function form_insert($oinsert_data){
        return $res=db($this->table)
            ->insert($oinsert_data);
    }
    public function form_template_add($oinsert_data){
        return $res=db('form_template')
            ->insert($oinsert_data);
    }
    public function form_template_edit($where,$oinsert_data){
        return $res=db('form_template')->where($where)
            ->update($oinsert_data);
    }
    public function form_template_delete($where){
        return $res=db('form_template')->where($where)
            ->delete();
    }
    public function form_coll($oinsert_data){
        return $res=db('form_coll')
            ->insert($oinsert_data);
    }
    public function form_delete($where){
        $res=db($this->table)->delete($where);
        return $res;
    }
}
