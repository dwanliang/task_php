<?php
namespace app\api\controller;

use app\api\model\Formmodel;
use think\Db;
use PHPExcel;//tp5.1用法
use PHPExcel_IOFactory;
use think\Exception;

class Form extends Home {

    public function form_list(){
        $data=$this->params;
        $db=new Formmodel();
        $where='not exists(select 1 from form_coll co where co.form_id=fo.id and co.user_id='.$data['uid'].')';
        $res=$db->alias('fo')
            ->join('groups_user gr','gr.groups_id=fo.groups_id')
            ->join('ts_user us','gr.user_id=us.id')
            ->where('fo.time_end','>',time())
            ->order('fo.time_start desc')
            ->where('us.id',$data['uid'])
            ->where($where)
            ->field('fo.*,fo.id')
            ->paginate(8, false,['page'=>$data['page']]);
        $data=$res->items();
        $data=[
            'data'=>$data,
            'total'=>$res->total(),//总数
            'last_page'=>$res->lastPage(),//总页数
            'current_page'=>$res->currentPage()//当前页数
        ];
        if(!empty($data['data'])){
            foreach ($data['data'] as $k=>$v){
                $data['data'][$k]['time_start']=date('Y-m-d H:i:s',$v['time_start']);
                $data['data'][$k]['time_end']=$v['time_end']*1000;
            }
            $this->return_msg(200,'成功',$data);
        }
        $this->return_msg(0,'无数据');

    }
    public function form_delete(){
        $data=$this->params;
        $db=new Formmodel();
        $where=$data['ids'];
        try {
            if(isset($data['type'])){
                db('form_template')->delete($where);
            }else{
                $db->form_delete($where);
            }

        }catch (Exception $e){
            $this->return_msg(0,'删除失败,失败原因：'.$e->getMessage());
        }
        $this->return_msg(200,'删除成功');
    }
    public function form_con(){
        $data=$this->params;
        $db=new Formmodel();
        $data=$db->alias('fo')
            ->where('fo.id',$this->params['id'])->select();
        $data=[
            'data'=>$data,
        ];
        if(!empty($data['data'])){
            foreach ($data['data'] as $k=>$v){
                $data['data'][$k]['time_start']=date('Y-m-d H:i:s',$v['time_start']);
                $data['data'][$k]['time_end']=$v['time_end']*1000;
            }
            $this->return_msg(200,'成功',$data);
        }
        $this->return_msg(0,'无数据');
    }
    /**
     * notes: 我的表单
     * date: 2021/1/14
     * time: 16:43
     * @throws \think\exception\DbException
     */
    public function my_form(){
        $data=$this->params;
        $db=new Formmodel();
        $res=db('ts_form')
            ->where(['user_id'=>$data['uid']])
            ->order('time_start desc')
            ->paginate(8, false,['page'=>$data['page']]);

        $data=$res->items();
        $data=[
            'data'=>$data,
            'total'=>$res->total(),//总数
            'last_page'=>$res->lastPage(),//总页数
            'current_page'=>$res->currentPage()//当前页数
        ];

        if(!empty($data['data'])){
            foreach ($data['data'] as $k=>$v){
                $data['data'][$k]['count']=db('form_coll')->where('form_id',$v['id'])->count();
                $data['data'][$k]['time_end']=$v['time_end']*1000;
            }
            $this->return_msg(200,'成功',$data);
        }
        $this->return_msg(0,'无数据');
    }
    /**
     * notes: 我的表单模板
     * date: 2021/1/14
     * time: 16:43
     * @throws \think\exception\DbException
     */
    public function form_template(){
        $data=$this->params;
        $db=new Formmodel();
        if(isset($data['template_id'])){
            $res=db('form_template')
                ->where(['id'=>$data['template_id']])
                ->order('add_time desc')
                ->field('id,form_con,form_title')
                ->find();
            $data=[
                'title'=>$res['form_title'],
                'data'=>json_decode($res['form_con'],true),
            ];
        }else{
            $res=db('form_template')
                ->where(['user_id'=>$data['uid']])
                ->order('add_time desc')
                ->field('id,form_title')
                ->paginate(8, false,['page'=>$data['page']]);
            $data=$res->items();
            foreach ($data as $k=>$v){
                $num1=rand(0,6);
                $num2=$this::randd(0,6,$num1);
                $data[$k]['color1']=$num1;
                $data[$k]['color2']=$num2;
            }
            $data=[
                'data'=>$data,
                'total'=>$res->total(),//总数
                'last_page'=>$res->lastPage(),//总页数
                'current_page'=>$res->currentPage()//当前页数
            ];
        }


        if(!empty($data['data'])){
            $this->return_msg(200,'成功',$data);
        }
        $this->return_msg(0,'无数据');
    }
    public static function randd($num1,$num2,$not){
        $num=rand($num1,$num2);
        if($num==$not){
            $num=self::randd($num1,$num2,$not);
        }
        return $num;
    }

    /** 表单添加
     * notes:
     * date: 2020/12/29
     * time: 16:18
     */
    public function form_add(){
        $data=$this->params;
        $formmodel=new Formmodel();
        $insert_data=[
            'user_id'=>$data['uid'],
            'time_end'=>strtotime($data['time_end']),
            'form_title'=>$data['form_title'],
            'form_con'=>$data['form_con'],
            'submit_type'=>$data['submit_type'],
            'time_start'=>time()
        ];
        if($data['submit_type']=='team'){
            $insert_data['groups_id']=$data['groups_id'];
        }
        $res=$formmodel->form_insert($insert_data);
        if($res){
            $this->return_msg(200,'添加成功');
        }
        $this->return_msg(0,'添加失败');
    }
    public function form_template_add(){
        $data=$this->params;
        $formmodel=new Formmodel();
        $insert_data=[
            'form_title'=>$data['form_title'],
            'form_con'=>$data['form_con'],
            'add_time'=>time()
        ];
        if(isset($data['uid'])){
            $insert_data['user_id']=$data['uid'];
        }
        $res=$formmodel->form_template_add($insert_data);
        if($res){
            $this->return_msg(200,'模板添加成功');
        }
        $this->return_msg(0,'添加失败');
    }
    public function form_template_edit(){
        $data=$this->params;
        $formmodel=new Formmodel();
        $update=[
            'form_title'=>$data['form_title'],
            'form_con'=>$data['form_con'],
        ];
        $where=['id'=>$data['template_id']];
        try {
            $res=$formmodel->form_template_edit($where,$update);
        }catch (Exception $e){
            $this->return_msg(0,'修改失败');
        }
        $this->return_msg(200,'修改成功');
    }
    public function form_template_delete(){
        $data=$this->params;
        $formmodel=new Formmodel();
        $where=['id'=>$data['template_id']];
        $res=$formmodel->form_template_delete($where);
        if($res){
            $this->return_msg(200,'删除成功');
        }
        $this->return_msg(0,'删除失败');
    }

    /**
     * notes: 表单提交
     * date: 2020/12/30
     * time: 17:18
     */
    public function form_coll(){
        $data=$this->params;
        $formmodel=new Formmodel();
        $op=Db::table('ts_form')->where('id',$data['id'])->find();
        $insert_data=[
            'form_id'=>$data['id'],
            'coll_con'=>$data['coll_con'],
            'coll_title'=>$data['coll_title'],
            'coll_time'=>time()
        ];
        if(isset($data['uid'])){
            $insert_data['user_id']=$data['uid'];
            $res=Db::table('form_coll')->where('user_id',$data['uid'])->where('form_id',$data['id'])->find();
            if($res){
                $this->return_msg(0,'你已经提交过该表单，请勿重复提交！',$res);
            }
        }

        if(time()>$op['time_end']){
            $this->return_msg(0,'结束时间已到，提交失败！');
        }
        $res=$formmodel->form_coll($insert_data);
        if($res){
            $this->return_msg(200,'提交成功');
        }
        $this->return_msg(0,'提交失败');
    }

    /**
     * notes: 表单数据
     * date: 2021/1/4
     * time: 9:48
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function form_data(){
        $data=$this->params;
        $db=new Formmodel();
        $data=$db->alias('fo')
            ->leftJoin('form_coll co','co.form_id=fo.id')
            ->leftJoin('ts_user us','us.id=co.user_id')
            ->where('fo.id',$data['id'])
            ->field('fo.id,fo.form_title,co.*,us.user_name')
            ->select();
        $coll_coll=[];
        $coll_colls=[];
        foreach($data as $k=>$v){
            $con=explode('#',$v['coll_con']);
            array_unshift($con,$v['user_name']);
            foreach ($con as $j=>$res){
                $coll_coll['name'.$j]=$res;
            }
            $coll_colls[]=$coll_coll;
        }
        $title=explode('#',$data[0]['coll_title']);
        array_unshift($title,'用户');
        $data=[
            'data'=>$coll_colls,
            'form_title'=>$data[0]['form_title'],
            'coll_title'=>$title
        ];
        if($data){
            $this->return_msg(200,'成功',$data);
        }
        $this->return_msg(0,'无数据');
    }
    /**
     * 导出xls
     * @auth true
     */
    public function form_daochu(){
        $db=new Formmodel();
        $datas=$this->params;
        $data=$db->alias('fo')
            ->leftJoin('form_coll co','co.form_id=fo.id')
            ->leftJoin('ts_user us','us.id=co.user_id')
            ->where('fo.id',$datas['id'])
            ->field('fo.id,fo.form_title,co.*,us.user_name')
            ->select();
        $coll_coll=[];
        foreach($data as $k=>$v){
            $con=explode('#',$v['coll_con']);
            array_unshift($con,$v['user_name']);
            $coll_coll[]=$con;
        }
        $title=explode('#',$data[0]['coll_title']);
        array_unshift($title,'用户');

        $map = array();
        //3.实例化PHPExcel类
        $objPHPExcel = new PHPExcel();
        //4.激活当前的sheet表
        $objPHPExcel->setActiveSheetIndex(0);
        //5.设置表格头（即excel表格的第一行）
        //$objPHPExcel
        $letter=['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','S','Y','Z'];
        foreach ($title as $k=>$v){
            $objPHPExcel->getActiveSheet()->setCellValue($letter[$k].'1', $v);
        }

        //设置A列水平居中
//        $objPHPExcel->setActiveSheetIndex(0)->getStyle('A')->getAlignment()
//            ->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        //设置单元格宽度
//        $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('A')->setWidth(10);
//        $objPHPExcel->setActiveSheetIndex(0)->getColumnDimension('B')->setWidth(30);


        //6.循环刚取出来的数组，将数据逐一添加到excel表格。
        for($i=0;$i<count($coll_coll);$i++){
            foreach ($coll_coll[$i] as $k=>$v){
                $objPHPExcel->getActiveSheet()->setCellValue($letter[$k].($i+2),$v);
            }
        }

        //7.设置保存的Excel表格名称
        $filename = $datas['title'].'.xls';
        //8.设置当前激活的sheet表格名称；

        $objPHPExcel->getActiveSheet()->setTitle('sheet'); // 设置工作表名

        //8.设置当前激活的sheet表格名称；
        $objPHPExcel->getActiveSheet()->setTitle('防伪码');
        //9.设置浏览器窗口下载表格
        ob_end_clean();//清除缓冲区,避免乱码
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition:inline;filename="'.$filename.'"');
        //生成excel文件
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        //下载文件在浏览器窗口
        $objWriter->save('php://output');
        exit;
    }
}