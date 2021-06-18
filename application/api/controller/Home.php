<?php

namespace app\api\controller;

use \Firebase\JWT\JWT;
use think\facade\Cache;
use think\Request;
use think\Validate;

class Home extends Base
{
    protected $request;//用来处理数据
    protected $valdata;//用来验证数据/参数
    protected $params;//过滤后的数据/参数
    protected $rules=[
        'Task'=>[
            'task_list'=>[
                'id|参数'=>'number',
                'uid|用户'=>'require|number'
            ],
            'task_add'=>[
                'groups_id|组织'=>'require|number',
                'time_end|结束时间'=>'require|date',
                'title|标题'=>'require|max:255',
                'task_con|任务内容'=>'require'
            ],
            'task_oper'=>[
                'task_id|任务'=>'require|number',
                'uid|用户'=>'require|number',
                'type|类型'=>'require|number',
                'state|状态'=>'require|number'
            ],
            'my_task'=>[],
            'task_delete'=>[],
            'my_taskcon'=>[]
        ],
        'Banners'=>[
            'banners_list'=>[]
        ],
        'Groups'=>[
            'groups_list'=>[
                'uid'=>'require|number',
            ]
        ],
        'Form'=>[
            'form_add'=>[
                'uid|用户'=>'require|number',
                'groups_id|团队'=>'require|number',
                'time_end|结束时间'=>'require|date',
                'form_title|表单标题'=>'require',
                'form_con|表单内容'=>'require',
            ],
            'form_list'=>[
//                'uid|用户'=>'require|number'
            ],
            'form_con'=>[
                'id'=>'require'
            ],
            'form_coll'=>[
//                'uid|用户'=>'require|number',
                'coll_con|内容'=>'require',
                'coll_title|标题'=>'require',
            ],
            'form_data'=>[
                'id|参数'=>'require',
                'uid|用户'=>'require',
            ],
            'form_daochu'=>[],
            'form_template_add'=>[
                'form_con|表单内容'=>'require',
                'form_title|模板标题'=>'require',
            ],
            'form_template'=>[],
            'my_form'=>[],
            'form_template_edit'=>[],
            'form_template_delete'=>[],
            'form_delete'=>[]
        ],
        'Team'=>[
            'team_list'=>[],
            'team_delete'=>[],
            'team_user_delete'=>[],
            'team_admin'=>[],
            'team_edit'=>[],
            'team_msg'=>[],
            'msg_add'=>[
                'msg_con|公告内容'=>'require',
                'groups_id|团队'=>'require'
            ],
            'team_add'=>[
                'groups_name|团队名称'=>'require'
            ],
            'team_search'=>[
                'text|搜索内容'=>'require|chsAlphaNum'
            ],
            'team_new'=>[],
            'team_user_add'=>[
                'groups_id|团队'=>'require',
            ],

        ],
        'User'=>[
            'user_add'=>[
                'user_name|账号'=>'require|chsAlphaNum',
                'email|邮箱'=>'email'
            ],
            'login'=>[
                'user_name|账号'=>'require|chsAlphaNum',
                'password|密码'=>'require'
            ],
            'pass_reset'=>[
                'user_name|账号'=>'chsAlphaNum',
                'password|新密码'=>'require',
            ],
            'user_list'=>[],
            'user_edit'=>[
                'data'=>'require',
            ],
            'user_head'=>[
//                'file'=>'require|file'
            ],
            'user_wx'=>[
                'code|唯一标识'=>'require'
            ],
            'get_code'=>[
                'email|邮箱'=>'email'
            ],
            'qq_login'=>[],
            'my_friend'=>[],
            'friend_where'=>[],
            'user_new_add'=>[],
            'user_new'=>[],
            'user_new_edit'=>[],
            'user_search'=>[]
        ],
        'Daka'=>[
            'daka_list'=>[],
            'daka'=>[],
            'daka_add'=>[
                'groups_id|团队'=>'require',
                'time_start|开始时间'=>'require',
                'time_end|结束时间'=>'require',
            ],
            'get_week_arr'=>[],
            'my_daka'=>[],
            'daka_delete'=>[
                'ids'=>'require'
            ],
            'my_dakacon'=>[
                'date|日期'=>'require'
            ]
        ],
        'Course'=>[
            'course_add'=>[
                'courseName|课程名称'=>'require',
                'courseAddress|上课地点'=>'require|chsAlphaNum',
                'courseTeacher|任课老师'=>'require|chsAlphaNum',
                'time_end|下课时间'=>'require',
                'time_start|上课时间'=>'require',
                'week|星期'=>'require'
            ],
            'course_list'=>[],
            'course_edit'=>[],
            'course_delete'=>[
                'ids|ID'=>'require'
            ]
        ],
        'Note'=>[
            'note_list'=>[],
            'note_edit'=>[
//                'note_con'=>'require'
            ],
            'note_delete'=>[
                'ids|备忘录'=>'require'
            ],
            'note_add'=>[
                'note_con|备忘录内容'=>'require',
                'note_title|标题'=>'require'
            ]
        ],
        'Mail'=>[
            'mail_index'=>[

            ],
            'demo'=>[]
        ],
        'Chatroom'=>[
            'chat_con'=>[],
            'chat_where'=>[],
            'chat_list'=>[],
            'chatroom_add'=>[],
            'chat_con_add'=>[],
            'room_delete'=>[],
        ]
    ];
    protected function initialize(){
        parent::initialize();
//        $this->check_time($this->request->header());//验证请求时间
        if(request()->isOptions()){//
            exit;
        }
        $this->check_params($this->request->except(['time','token']));
        if($this->request->controller()!=='User' && $this->request->action()!=='login' && $this->request->controller()!=='Form'){
            $this->check_token($this->request->param(),$this->request->header());//验证token
            $this->user_dongjie();
        }
    }

    /**
     * notes:验证是否超时
     * @param $arr
     */
    public function check_time($arr){
        if (!isset($arr['time']) || intval($arr['time']) <= 1) {
            $this->return_msg(400, '环境不正确！');
        }
        if (time() - intval($arr['time']) > 60) {
            $this->return_msg(401, '请求超时！');
        }
    }

    /**
     * notes: 返回json数据
     * @param $code
     * @param string $msg
     * @param array $data
     */
    public function return_msg($code, $msg, $data = []){
        $return_data['code'] = $code;
        $return_data['msg']  = $msg;
        $return_data['data'] = $data;
        echo json_encode($return_data);die;
    }
    public function user_dongjie(){
        $user_info=db('ts_user')->find($this->params['uid']);
        if($user_info['dongjie']){
            $this->return_msg(401,'账号已被冻结！');
        }
    }
    /**
     * notes:验证token()
     * date: 2020/12/17
     * time: 11:07
     * @param $arr
     */
    public function check_token($arr,$header){
        if(!isset($header['dwan-verification']) || empty($header['dwan-verification'])){//判断token是否为空
            $this->return_msg(401,'token不存在');
        }
        $token=$header['dwan-verification'];
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
        $header=$this->request->header();
        if(isset($header['dwan-verification']) && $token=$header['dwan-verification']!=='undefined'){//判断token是否为空
            $token=$header['dwan-verification'];
            $arr['uid']=Cache::get($token);
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
