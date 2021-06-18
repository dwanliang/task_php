<?php

namespace app\admin_api\controller;

use think\facade\Cache;
use think\Request;
use think\Validate;
use Firebase\JWT\JWT;

class Home extends Base
{
    protected $request;//用来处理数据
    protected $valdata;//用来验证数据/参数
    protected $params;//过滤后的数据/参数
    protected $rules=[
        'Index'=>[
            'index'=>[]
        ],
        'Login'=>[
            'login'=>[
                'username|账号'=>'require|chsAlphaNum',
                'password|密码'=>'require|chsAlphaNum'
            ]
        ],
        'Admin'=>[
            'add'=>[
                'username|账号'=>'require|chsAlphaNum',
                'password|密码'=>'require|chsAlphaNum'
            ],
            'admin_list'=>[],
            'edit'=>[
                'username|账号'=>'require|chsAlphaNum',
                'password|密码'=>'chsAlphaNum'
            ],
            'admin_role'=>[],
            'setrole'=>[],
            'delete'=>[],
            'admin_log_list'=>[]
        ],
        'User'=>[
            'user_list'=>[
//                'page|页码'=>'require|number',
//                'limit'=>'require|number'
            ],
            'delete'=>['id'=>'require'],
            'add'=>[
                'username|账号'=>'require|chsAlphaNum',
                'password|密码'=>'require|chsAlphaNum'
            ],
            'dongjie'=>[
                'id'=>'require'
            ],
            'edit'=>[
                'username|账号'=>'require|chsAlphaNum',
                'password|密码'=>'chsAlphaNum',
            ],
            'search'=>[]
        ],
        'Permissions'=>[
            'permissions_list'=>[],
            'permissions_parent'=>[],
            'add'=>[],
            'edit'=>[
                'permissions_name'=>'require',
                'permissions_route'=>'require',
                'permissions_describe'=>'require',
                'permissions_sort'=>'require',
                'permissions_Parentid'=>'require',
            ],
            'delete'=>[
                'id|ID'=>'require'
            ]
        ],
        'Role'=>[
            'role_list'=>[],
            'delete'=>[],
            'add'=>[],
            'edit'=>[],
            'role_permissions'=>[],
            'setpermissions'=>[]
        ],
        'Banners'=>[
            'banners_list'=>[],
            'add'=>[
                'banners_title|轮播图标题'=>'require',
                'banners_sort|轮播图排序'=>'require|number',
                'banners_url|轮播图链接'=>'require',
            ],
            'delete'=>[
                'id'=>'require'
            ],
            'edit'=>[
                'banners_title|轮播图标题'=>'require',
                'banners_sort|轮播图排序'=>'require|number',
                'banners_url|轮播图链接'=>'require',
            ]
        ]
    ];
    protected function initialize(){
        parent::initialize();
        if(request()->isOptions()){//
            exit;
        }
        //验证参数， 参数过滤
        $this->check_params($this->request->except(['time','token']));

        //除了登录接口都要验证token，验证不通过拦截
        if($this->request->controller()!=='Login' && $this->request->action()!=='login'){
            $this->check_token($this->request->param(),$this->request->header());//验证token
            $this->check_permissions($this->request->controller(),$this->request->action());
        }
    }

    /**
     * notes:验证是否超时
     * @param $arr
     */
    public function check_time($arr){
        if (!isset($arr['time']) || intval($arr['time']) <= 1) {
            $this->return_msg(401, '环境不正确！');
        }
        if (time() - intval($arr['time']) > 60) {
            $this->return_msg(401, '请求超时！');
        }
    }

    public function check_permissions($controller,$action){
        $header=$this->request->header();
        $token=$header['dwanverification'];
        $admin_id=Cache::get($token);
        $permission_info=db('admin_role ar')
            ->where('ar.admin_id',$admin_id)
            ->join('ts_role tr','tr.id=ar.role_id')
            ->join('role_permissions rp','rp.role_id=ar.role_id')
            ->join('ts_permissions tp','tp.id=rp.permissions_id')
            ->field('tp.permissions_route')
            ->select();
        $route=$controller.'/'.$action;
        if($route=='Permissions/permissions_parent'){
            return;
        }
        $falg=false;
//        print_r($permission_info);die;
        foreach ($permission_info as $k=>$re){
            if($re['permissions_route']==$route || strstr($route, $re['permissions_route'])){
                $falg=true;
                break;
            }
        }
        if(!$falg){
            $this->return_msg(403,'权限不够',[],$route,'权限不够');
        }
    }
    /**
     * notes: 返回json数据
     * @param $code
     * @param string $msg
     * @param array $data
     */
    public function return_msg($code, $msg, $data = [],$log_name='',$explain=''){
        $return_data['code'] = $code;
        $return_data['msg']  = $msg;
        $return_data['data'] = $data;
        if($log_name!==''){
            if($code==200){
                $this->admin_log($log_name,1,$explain);
            }else{
                $this->admin_log($log_name,0,$explain);
            }
        }
        echo json_encode($return_data);die;
//        return json($return_data,$code);
    }

    /**
     * notes: 记录管理员操作
     * date: 2021/2/20
     * time: 15:42
     * @param $admin_id
     * @param $msg
     * @param $state
     */
    public function admin_log($log_name,$state,$explain){
        $header=$this->request->header();
        $token=$header['dwanverification'];
        $admin_id=Cache::get($token);
        $log_insert=db('admin_log')
            ->insert([
                'admin_id'=>$admin_id,
                'log_name'=>$log_name,
                'log_state'=>$state,
                'time'=>time(),
                'explain'=>$explain
            ]);
    }
    /**
     * notes:验证token()
     * date: 2020/12/17
     * time: 11:07
     * @param $arr
     */
    public function check_token($arr,$header){
        if(!isset($header['dwanverification']) || empty($header['dwanverification'])){//判断token是否为空
            $this->return_msg(401,'token不存在');
        }
        $token=$header['dwanverification'];
        if(!Cache::get($token)){
            $this->return_msg(401,'token不正确,或已过期');//token不正确
        }
        $decoded = JWT::decode($token, example_key, array('HS256'));
        if($decoded->OverTime<time()){
            $this->return_msg(401,'token过期');//token过期
        }
    }

    /**
     * notes: 验证参数， 参数过滤
     * date: 2020/12/18
     * time: 14:41
     * @param $arr
     */
    public function check_params($arr){
        $rule=$this->rules[$this->request->controller()][$this->request->action()];
        $this->valdata=new Validate($rule);
        if(!$this->valdata->check($arr)){
            $this->return_msg(400,$this->valdata->getError());
        }
        $this->params=$arr;
    }

    /**
     * notes: 上传图片
     * date: 2021/1/8
     * time: 16:47
     * @param $img_content
     * @param $path
     * @param $name
     * @return bool|string
     */
    public function uploadImg($file,$path){
        $paths='.'.$path;
        $info = $file->move($paths);
        if($info){
            $path=$path.'/'.date('Ymd').'/';//上传后的路径
            return $path.$info->getFilename();
        }else{
            // 上传失败获取错误信息
            echo $file->getError();die;
        }
    }

}
