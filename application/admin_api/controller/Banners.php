<?php
namespace app\admin_api\controller;

use app\admin_api\model\Bannersmodel;
use think\Exception;

class Banners extends Home{
    public function banners_list(){
        $data=$this->params;
        $db=new Bannersmodel();
        if(isset($data['id'])){
            $res=$db
                ->field('banners_title,banners_url,banners_sort,banners_img')
                ->find($data['id']);
            $res['banners_img']=[
                ['name'=>'','url'=>$res['banners_img'],]
            ];
        }else{
            $limit = $data['limit'];
            $page  = $data['page'];
            $res=$db->order('banners_sort')
                ->field("*,from_unixtime(add_time,'%Y-%m-%d') as add_time")
                ->paginate($limit, false, ['page' => $page]);
        }
        if($res){
            $this->return_msg(200, 'success', $res);
        }
        $this->return_msg(0, '无数据');
    }
    public function add(){
        $data             = $this->params;
        $db               = new Bannersmodel();
        $data['add_time'] = time();
        $file = request()->file('file');
        unset($data['file']);
        if($file){
            $path="/upload/banners";
            $banners_img=$this->uploadImg($file,$path);
            if(!$banners_img){
                $this->return_msg(0,'error',[],'添加轮播图');
            }
            $data['banners_img']=yuming.$banners_img;
        }else{
            $this->return_msg(0,'请上传轮播图图片');
        }
        $res              = $db->insert($data);
        if ($res) {
            $this->return_msg(200, '添加成功', [], '添加轮播图');
        }
        $this->return_msg(0, '添加失败', [], '添加轮播图');
    }
    public function delete(){
        $data  = $this->params;
        $db    = new Bannersmodel();
        $where = $data['id'];
        try {
            $res = $db->banners_delete($where);
        } catch (Exception $e) {
            $this->return_msg(0, 'error,错误原因：'.$e->getMessage(), '删除轮播图');
        }
        $this->return_msg(200, '删除成功', [], '删除轮播图');
    }
    public function edit(){
        $data=$this->params;
        $db=new Bannersmodel();
        $file = request()->file('file');
        unset($data['file']);
        if($file){
            $path="/upload/banners";
            $banners_img=$this->uploadImg($file,$path);
            if(!$banners_img){
                $this->return_msg(0,'',[],'编辑轮播图');
            }
            $data['banners_img']=yuming.$banners_img;
        }
        try {
            $where=['id'=>$data['id']];
            $res=$db->edit($where,$data);
        }catch (Exception $e){
            $this->return_msg(0,'编辑失败,错误原因:'.$e->getMessage(),[],'编辑轮播图');
        }
        $this->return_msg(200,'编辑成功',[],'编辑轮播图');
    }
}