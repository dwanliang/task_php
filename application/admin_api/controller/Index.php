<?php
namespace app\admin_api\controller;

use think\App;
class Index extends Home{
    public function index(){
        $data=$this->params;
//        $systeam_info=[
//            'yuming'=>yuming,//服务器地址
//            'operating_system'=>php_uname('s'),//服务器操作系统
//            'operating_environment'=>php_sapi_name(),//服务器运行环境
//            'PHP_VERSION'=>PHP_VERSION,//PHP版本
//            'MYSQL_VERSION'=>'5.6.4',//数据库版本
//            'think_ver'=> App::VERSION,//THINKPHP版本
//            'upload_limit'=>ini_get('upload_max_filesize'),//上传文件限制大小
//            'implement_time_limit'=>ini_get('max_execution_time'),//执行时间限制
//        ];
        //系统信息
        $systeam_info=[
            ['name'=>'服务器地址','val'=>yuming],
            ['name'=>'服务器操作系统','val'=>php_uname('s')],
            ['name'=>'服务器运行环境','val'=>php_sapi_name()],
            ['name'=>'PHP版本','val'=>PHP_VERSION],
            ['name'=>'数据库版本','val'=>'5.6.4'],
            ['name'=>'THINKPHP版本','val'=>App::VERSION],
            ['name'=>'上传文件限制大小','val'=>ini_get('upload_max_filesize')],
            ['name'=>'执行时间限制','val'=>ini_get('max_execution_time')],
        ];
        //基础统计
        //用户
        $user_sum=db('ts_user')->count();
        $jruser_sum=db('ts_user')->where($this->times('user_time',true))->count();
        $zruser_sum=db('ts_user')->where($this->times('user_time'))->count();
        //团队
        $groups_sum=db('groups')->count();
        $jrgroups_sum=db('groups')->where($this->times('time',true))->count();
        $zrgroups_sum=db('groups')->where($this->times('time'))->count();
        //发布任务
        $task_sum=db('ts_task')->count();
        $jrtask_sum=db('ts_task')->where($this->times('time_start',true))->count();
        $zrtask_sum=db('ts_task')->where($this->times('time_start'))->count();

        $card_line=[
            ['icon'=>'el-icon-user-solid','data'=>[['name'=>'累计用户：','num'=>$user_sum],['name'=>'今日新增：','num'=>$jruser_sum],['name'=>'昨日新增：','num'=>$zruser_sum]]],
            ['icon'=>'iconfont icon-bianzu','data'=>[['name'=>'累计团队：','num'=>$groups_sum],['name'=>'今日新增：','num'=>$jrgroups_sum],['name'=>'昨日新增：','num'=>$zrgroups_sum]]],
            ['icon'=>'iconfont icon-faburenwu','data'=>[['name'=>'累计任务：','num'=>$task_sum],['name'=>'今日任务：','num'=>$jrtask_sum],['name'=>'昨日任务：','num'=>$zrtask_sum]]],
        ];

        //信息变化图表数据
        $wx_source=db('ts_user')->where('source','微信小程序')->count();
        $h5_source=db('ts_user')->where('source','H5')->count();
        $app_source=db('ts_user')->where('source','APP')->count();
        $source=[
            ['value'=>$wx_source,'name'=>'微信小程序'],
            ['value'=>$h5_source,'name'=>'H5'],
            ['value'=>$app_source,'name'=>'APP'],
        ];

        $echarts['source']=$source;

        $data['systeam_info']=$systeam_info;
        $data['card_line']=$card_line;
        $data['echarts']=$echarts;
        $this->return_msg(200,'success',$data);
    }
    public function times($ziduan,$day=false){
        $where=[];
        $yes1 = strtotime( date("Y-m-d 00:00:00",strtotime("-1 day")) );
        $yes2 = strtotime( date("Y-m-d 23:59:59",strtotime("-1 day")) );
        if($day){//今日
            $where[] = [$ziduan,'between',[strtotime(date('Y-m-d')),time()]];
            return $where;
        }else{//昨日
            $where[]=[$ziduan,'between',[$yes1,$yes2]];
            return $where;
        }
    }
}