<?php

namespace app\api\controller;

use app\api\model\Dakamodel;
use app\api\model\Taskmodel;
use app\api\model\Opermodel;
use think\Request;
use think\Db;

class Task extends Home
{
    /**
     * 显示所有列表
     * @throws \think\exception\DbException
     */
    public function task_list()
    {
        $data=$this->params;
        $db=new Taskmodel();
        $uid=$data['uid'];
        if (isset($data['id'])){
            $id=$data['id'];
            $data=$db->alias('ta')
                ->where('ta.id',$this->params['id'])->select();
            $op=Db::table('ts_oper')->where('task_id',$id)->where('user_id',$uid)->select();

            foreach ($op as $v){
                if($v['type']==1 && $v['state']==1){
                    $data[0]['top']=true;
                }
                if($v['type']==2  && $v['state']==1){
                    $data[0]['coll']=true;
                }
                if($v['type']==3  && $v['state']==1){
                    $data[0]['over']=true;
                }
            }
            $data=[
                'data'=>$data,
            ];
        }else{
            $wheres='';
            $where=[];
            if(isset($data['coll'])){
                $wheres='exists(select 1 from ts_oper o where ta.id=o.task_id and type=2 and state=1)';
            }
            if(isset($data['groups_id'])){
                $where=['ta.groups_id'=>$data['groups_id']];
            }

            $res=$db->alias('ta')
                ->join('groups_user gr','gr.groups_id=ta.groups_id')
                ->join('ts_user us','gr.user_id=us.id')
                ->leftJoin('ts_oper op','op.task_id=ta.id and op.user_id='.$uid.' and op.type=1 and op.state=1')
//                ->join('us.id=ta.user_id')
                ->where('ta.time_end','>',time())
                ->order(['op.type'=>'desc','ta.time_start'=>'desc'])
                ->where('exists(select 1 from ts_user where us.id='.$uid.')')
                ->where($wheres)
                ->where($where)
                ->field('ta.*,ta.id,us.user_name,op.type')
                ->paginate(8, false,['page'=>$data['page']]);
            $data=$res->items();
            $data=[
                'data'=>$data,
                'total'=>$res->total(),//总数
                'last_page'=>$res->lastPage(),//总页数
                'current_page'=>$res->currentPage()//当前页数
            ];
        }
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
     * notes: 任务添加
     * date: 2020/12/19
     * time: 17:10
     */
    public function task_add(){
        $data=$this->params;
        if(strtotime($data['time_end'])<time()){
            $this->return_msg(0,'结束时间必须大于当前时间');
        }
        $db=new Taskmodel();
        $add=[
            'user_id'=>$data['user_id'],
            'groups_id'=>$data['groups_id'],
            'time_end'=>strtotime($data['time_end']),
            'title'=>$data['title'],
            'task_con'=>$data['task_con'],
            'time_start'=>time()
        ];
        $res=$db->insert($add);
        if($res){
            $this->return_msg(200,'添加成功');
        }
    }
    public function my_task(){
        $data=$this->params;
        $db=new Taskmodel();
        $size=$data['size'];
        $page=$data['page'];
        $res=$db->alias('ts')
            ->where('ts.user_id',$data['uid'])
            ->join('groups gr','gr.id=ts.groups_id')
            ->join('ts_user tu','tu.id=ts.user_id')
            ->field('gr.groups_name,ts.*,tu.user_name')
            ->order('ts.time_start desc')
            ->paginate($size, false,['page'=>$page]);
        $data=$res->items();
        $datas=[
            'data'=>$data,
            'total'=>$res->total(),//总数
            'last_page'=>$res->lastPage(),//总页数
            'current_page'=>$res->currentPage()//当前页数
        ];
        if(!empty($datas['data'])){
            foreach ($datas['data'] as $k=>$v){
                $datas['data'][$k]['time_start']=date('Y-m-d H:i:s',$v['time_start']);
                $datas['data'][$k]['time_end']=$v['time_end']*1000;
            }
            $this->return_msg(200,'成功',$datas);
        }
        $this->return_msg(0,'无数据');
    }
    public function task_delete(){
        $data=$this->params;
        $db=new Taskmodel();
        $where=$data['ids'];
        $res=$db->task_delete($where);
        if($res){
            $this->return_msg(200,'删除成功');
        }
        $this->return_msg(0,'删除失败');
    }
    public function my_taskcon(){
        $data=$this->params;
        $db=new Taskmodel();
        $res=$db->alias('ta')
            ->where('ta.id',$data['id'])
            ->rightJoin('groups_user gu','ta.groups_id=gu.groups_id')
            ->leftJoin('ts_oper to','to.user_id=gu.user_id and to.task_id=ta.id and to.type=3 and to.state=1')
//            ->leftJoin('daka_user du','du.user_id=gu.user_id')
            ->leftJoin('ts_user tu','tu.id=gu.user_id')
            ->field('to.oper_time,to.state,tu.user_name,tu.user_head,tu.id user_id')
            ->select();
        foreach ($res as $k=>$v){
            if($v['oper_time']){
                $res[$k]['oper_time']=date('Y-m-d H:i:s',$v['oper_time']);
            }
        }
        if($res){
            $this->return_msg(200,'success',$res);
        }
        $this->return_msg(0,'error');
    }
    /**
     * notes: 任务收藏、置顶、完成
     * date: 2020/12/25
     * time: 15:46
     */
    public function task_oper(){
        $data=$this->params;
        $oper_data=[
            'task_id'=>$data['task_id'],
            'user_id'=>$data['uid'],
            'oper_time'=>time(),
            'type'=>$data['type'],
            'state'=>$data['state']
        ];
        $opermodel=new Opermodel();
        $res=$opermodel->task_oper($data,$oper_data);
        if($res){
            $this->return_msg(200,'操作成功',$oper_data);
        }
        $this->return_msg(0,'操作失败',$oper_data);
    }

}
