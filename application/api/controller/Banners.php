<?php
namespace app\api\controller;

use app\api\model\Bannersmodel;

class Banners extends Home{
    public function banners_list(){
        $data=$this->params;
        $db=new Bannersmodel();
        $res=$db->order('banners_sort asc')
            ->field('banners_title as title,banners_url,banners_img as image')
            ->select();
        if($res){
            $this->return_msg(200,'success',$res);
        }
        $this->return_msg(0,'error');
    }
}