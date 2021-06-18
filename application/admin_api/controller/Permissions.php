<?php
namespace app\admin_api\controller;

use app\admin_api\model\Adminmodel;
use app\admin_api\model\Permissionsmodel;
use think\Exception;

class Permissions extends Home{

    public function permissions_list(){
        $data=$this->params;
        $db=new Permissionsmodel();
        if(isset($data['id'])){
            $res=$db->find($data['id']);
        }else{
            $limit=$data['limit'];
            $page=$data['page'];
            $res=$db->where('permissions_Parentid',0)
                ->order('permissions_sort asc')
                ->field("*,from_unixtime(add_time,'%Y-%m-%d') as add_time")
                ->paginate($limit, false,['page'=>$page]);
            $dats=$res->items();
            foreach ($dats as $k=>$re){
                $Children=$db->where('permissions_Parentid',$re['id'])
                    ->order('permissions_sort asc')
                    ->field("*,from_unixtime(add_time,'%Y-%m-%d') as add_time")
                    ->select();
                $dats[$k]['children']=$Children;
//                if($re['permissions_Parentid']==0){
//                    $permissions_name='父级';
//                }else{
//                    $permissions_name=$db->find($re['permissions_Parentid'])['permissions_name'];
//                }
//                $dats[$k]['permissions_Parent']=$permissions_name;
            }
        }

        $this->return_msg(200,'success',$res);
    }
    public function permissions_parent(){
        $data=$this->params;
        $db=new Permissionsmodel();
        $res=$db->order('permissions_sort asc')
            ->where('permissions_Parentid',0)
            ->field('permissions_name,id')
            ->select();
        $ress[0]=['permissions_name'=>'父级','id'=>0];
        foreach ($res as $k=>$re){
            $ress[$k+1]=$re;
        }
        $this->return_msg(200,'success',$ress);
    }
    public function add(){
        $data=$this->params;
        $db=new Permissionsmodel();
        $data['add_time']=time();
        $res=$db->insert($data);
        if($res){
            $this->return_msg(200,'添加成功',[],'添加权限');
        }
        $this->return_msg(0,'添加失败',[],'添加权限');
    }
    public function edit(){
        $data=$this->params;
        $db=new Permissionsmodel();
        try {
            $res=$db->where('id',$data['id'])->update($data);
        }catch (Exception $e){
            $this->return_msg(0,'error',[],'编辑权限');
        }
        $this->return_msg(200,'success',[],'编辑权限');
    }
    public function delete(){
        $data=$this->params;
        $db=new Permissionsmodel();
        $where=$data['id'];
        try {
            $res=$db->permissions_delete($where);
        }catch (Exception $e){
            $this->return_msg(0,$e,'删除权限');
        }
        $this->return_msg(200,'删除成功',[],'删除权限');
    }
}