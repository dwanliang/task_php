<?php
namespace app\admin_api\controller;

use app\admin_api\model\Adminmodel;
use app\admin_api\model\Permissionsmodel;
use app\admin_api\model\Rolemodel;
use think\Exception;

class Role extends Home
{

    public function role_list()
    {
        $data = $this->params;
        $db   = new Rolemodel();
        if (isset($data['id'])) {
            $res = $db->find($data['id']);
        } else {
            $limit = $data['limit'];
            $page  = $data['page'];
            $res   = $db
                ->order('role_sort asc')
                ->field("*,from_unixtime(add_time,'%Y-%m-%d') as add_time")
                ->paginate($limit, false, ['page' => $page]);
            $dats  = $res->items();
        }
        $this->return_msg(200, 'success', $res);
    }

    public function add()
    {
        $data             = $this->params;
        $db               = new Rolemodel();
        $data['add_time'] = time();
        $res              = $db->insert($data);
        if ($res) {
            $this->return_msg(200, '添加成功', [], '添加角色');
        }
        $this->return_msg(0, '添加失败', [], '添加角色');
    }

    public function edit()
    {
        $data = $this->params;
        $db   = new Rolemodel();
        try {
            $res = $db->where('id', $data['id'])->update($data);
        } catch (Exception $e) {
            $this->return_msg(0, 'error', [], '编辑角色');
        }
        $this->return_msg(200, 'success', [], '编辑角色');
    }

    public function delete()
    {
        $data  = $this->params;
        $db    = new Rolemodel();
        $where = $data['id'];
        try {
            $res = $db->role_delete($where);
        } catch (Exception $e) {
            $this->return_msg(0, $e, '删除角色');
        }
        $this->return_msg(200, '删除成功', [], '删除角色');
    }

    public function role_permissions()
    {
        $data             = $this->params;
//        $db               = new Rolemodel();
        $role_id          = $data['role_id'];
        $per_db           = new Permissionsmodel();
        $permissions_info = $per_db
            ->where('permissions_Parentid', 0)
            ->field('id,permissions_name')
            ->select();
        foreach ($permissions_info as $k => $re) {
            $Children= $per_db->where('permissions_Parentid', $re['id'])
                ->field('id,permissions_name')
                ->select();
            $permissions_info[$k]['children'] = $Children;
        }
        $checkeds = db('role_permissions')->where(['role_id'=>$role_id,'state'=>1])->select();
        $checked  = [];
        foreach ($checkeds as $k => $re) {
            $checked[] = $re['permissions_id'];
        }
        $data['data']   = $permissions_info;
        $data['checke'] = $checked;
        $this->return_msg(200, 'success', $data);
    }

    public function setpermissions()
    {
        $data    = $this->params;
        $db      = new Rolemodel();
        $add     = [];
        $role_id = $data['role_id'];
        foreach ($data['permissions_ids'] as $k => $re) {
            $add[] = ['role_id' => $role_id, 'permissions_id' => $re,'add_time'=>time()];
        }
        foreach ($data['checked'] as $k => $re) {
            $delete = ['role_id' => $role_id, 'permissions_id' => $re];
            $del=db('role_permissions')->where($delete)->delete();
        }
        try {
            $res = db('role_permissions')->insertAll($add);
        }catch (Exception $e){
            $this->return_msg(0,'error'.$e->getMessage(),[],'设置角色权限');
        }
        $this->return_msg(200,'success',[],'设置角色权限');
    }

}