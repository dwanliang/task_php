<?php

namespace app\api\model;

use think\Model;

class Dakamodel extends Model
{
    protected $table='ts_daka';
    protected $autoWriteTimestamp = true;

    public function daka_see($where){
        $res=$this->where($where)->select();
        return $res;
    }
    public function daka_find($where){
        $res=$this->where($where)->find();
        return $res;
    }
    public function daka_user($add){
        $res=db('daka_user')->insert($add);
        return $res;
    }
    public function daka_add($add){
        $res=db($this->table)->insert($add);
        return $res;
    }
    public function daka_delete($where){
        $res=db($this->table)->delete($where);
        return $res;
    }
    public function daka_list($data){
        $uid=$data['uid'];
        $res=$this->alias('dk')
            ->join('groups_user gu','gu.groups_id=dk.groups_id')
            ->leftjoin('groups gr','gu.groups_id=gr.id')
            ->join('ts_user us','gu.user_id=us.id and us.id='.$uid.'')
            ->join('ts_user tu','tu.id=dk.user_id')
            ->leftJoin('daka_user du','du.daka_id=dk.id and du.user_id='.$uid.' and du.daka_time between '.strtotime(date('Y-m-d 00:00:00')).' and '.time().'')
//            ->where('dk.time_end','>',time())
            ->order(['du.state'=>'asc','dk.time_start'=>'desc'])
//            ->where('exists(select 1 from ts_user where us.id='.$uid.')')
            ->field('dk.*,dk.id,tu.user_name,gr.groups_name,du.state')
            ->paginate($data['size'], false,['page'=>$data['page']]);
        $data=$res->items();
        $data=[
            'data'=>$data,
            'total'=>$res->total(),//总数
            'last_page'=>$res->lastPage(),//总页数
            'current_page'=>$res->currentPage()//当前页数
        ];
        if(!empty($data['data'])){
            foreach ($data['data'] as $k=>$v){
                if($v['time_end']<=date('H:i') && !$v['state']){
                    $data['data'][$k]['state']=2;
                }
                if($v['time_start']>=date('H:i') && !$v['state']){
                    $data['data'][$k]['state']=3;
                }
                $data['data'][$k]['time']=date('Y-m-d H:i:s',$v['add_time']);
                $data['data'][$k]['time_start']=strtotime(date('Y-m-d '.$v['time_start']))*1000;
                $data['data'][$k]['time_end']=strtotime(date('Y-m-d '.$v['time_end']))*1000;
                if($v['type']==1 && date('Y-m-d')!==$v['date']){
                    unset($data['data'][$k]);
                } else if($v['type']==2){//每天

                }else if($v['type']==3){//自定义
                    $log=false;
                    foreach (explode(',',$v['date']) as $res){
                        if(date('w')==$res){//判断星期几
                            $log=true;
                            break;
                        }
                    }
                    if(!$log){
                        unset($data['data'][$k]);
                    }
                }
            }
            return $data;
        }
        return false;
    }
    public function daka_con($data){
        $res[0]=$this->alias('dk')
            ->where('dk.id',$data['id'])
            ->leftjoin('groups gr','dk.groups_id=gr.id')
            ->field('dk.*,gr.groups_name')
            ->find();
        foreach ($res as $k=>$v){
            $res[$k]['time']=date('Y-m-d H:i:s',$v['add_time']);
            $res[$k]['time_start']=strtotime(date('Y-m-d '.$v['time_start']))*1000;
            $res[$k]['time_end']=strtotime(date('Y-m-d '.$v['time_end']))*1000;
        }
        $zhou=ceil((time()-$res[0]['add_time'])/604800)+1;//从开始到现在有多少周
        $time=$res[0]['add_time'];
        $DAtt=explode(',',$v['date']);//星期几有效
        $selected=[];
        $num_size=$this->alias('dk')
            ->where('dk.id',$data['id'])
            ->join('groups_user gu','dk.groups_id=gu.groups_id')
            ->count();

        if($res[0]['type']==1){
            $red=db('daka_user')->where(['user_id'=>$data['uid'],'daka_id'=>$data['id']])->where('daka_time','BETWEEN',[strtotime($res[0]['date']), strtotime($res[0]['date'])+86400])->find();
            if(isset($data['my_data'])){//我的打卡详情
                $daka_num1=db('daka_user')->where('daka_id',$data['id'])->where('daka_time','BETWEEN',[strtotime($res[0]['date']), strtotime($res[0]['date'])+86400])->count();
                $num=$num_size-$daka_num1;
                $infos=$num.'人未打';$sat=false;$daka_time='';
                if($num=0){
                    $infos='打卡完毕';$sat=true;$daka_time=date('H:i:s',$red['daka_time']);
                }
            }else{//打卡详情
                $infos='未打卡';$sat=false;$daka_time='';
                if($red){
                    $infos='已打卡';$sat=true;$daka_time=date('H:i:s',$red['daka_time']);
                }
            }
            $selected[]=[
                'date'=>$res[0]['date'],
                'info'=>$infos,
                'sat'=>$sat,
                'time'=>$daka_time
            ];
        }else{
            if($res[0]['type']==2){
                $DAtt=[1,2,3,4,5,6,7];
            }
            for ($i=0;$i<$zhou;$i++){
                $date=$this->get_week_arr($time,$DAtt);
                foreach ($date as $k=>$ret){
                    if($ret>time() || $ret+86400<$res[0]['add_time'])continue;
                    $red=db('daka_user')->where(['user_id'=>$data['uid'],'daka_id'=>$data['id']])->where('daka_time','BETWEEN',[$ret, $ret+86400])->find();
                    if(isset($data['my_data'])){//我的打卡详情
                        $daka_num2=db('daka_user')->where('daka_id',$data['id'])->where('daka_time','BETWEEN',[$ret, $ret+86400])->count();
                        $num=$num_size-$daka_num2;
                        $Date=date('Y-m-d',$ret);$infos=$num.'人未打';$sat=false;$daka_time='';
                        if($num=0){
                            $infos='打卡完毕';$sat=true;$daka_time =date('H:i:s',$red['daka_time']);
                        }
                    }else{//打卡详情
                        $Date=date('Y-m-d',$ret);$infos='未打卡';$sat=false;$daka_time='';
                        if($red){
                            $infos='已打卡';$sat=true;$daka_time=date('H:i:s',$red['daka_time']);
                        }
                    }
                    $selected[]=[
                        'date'=>$Date,
                        'info'=>$infos,
                        'sat'=>$sat,
                        'time'=>$daka_time
                    ];
                }
                $time+=604800;
            }
        }
        $data=[
            'data'=>$res,
            'info'=>$selected
        ];
        return $data;
    }
    /**
     * notes: 获取传入时间戳星期的某个星期几的时间戳
     * date: 2021/2/3
     * time: 9:52
     * @param $timestamp  //初始时间戳
     * @param $v  //星期数组[1,2,5,6,7]
     * @return array
     */
    function get_week_arr($timestamp,$v){
        $kk=[];
        foreach ($v as $k=>$ret){
            switch ($ret) {
                case 1:
                    $kk[]=strtotime(date('Y-m-d', strtotime("this week Monday", $timestamp)));
                    break;
                case 2:
                    $kk[]=strtotime(date('Y-m-d', strtotime("this week Tuesday", $timestamp)));
                    break;
                case 3:
                    $kk[]=strtotime(date('Y-m-d', strtotime("this week Wednesday", $timestamp)));
                    break;
                case 4:
                    $kk[]=strtotime(date('Y-m-d', strtotime("this week Thursday", $timestamp)));
                    break;
                case 5:
                    $kk[]=strtotime(date('Y-m-d', strtotime("this week Friday", $timestamp)));
                    break;
                case 6:
                    $kk[]=strtotime(date('Y-m-d', strtotime("this week Saturday", $timestamp)));
                    break;
                case 7:
                    $kk[]=strtotime(date('Y-m-d', strtotime("this week Sunday", $timestamp)));
                    break;
            }
        }
        return $kk;
    }
}
