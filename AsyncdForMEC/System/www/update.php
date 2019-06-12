<?php
include_once(PATH . SPATH . '/www/controller.php');
class update extends controller
{
    public function __construct() {
        parent::__construct();
    }

    public function run(){
        $this->headers['Access-Control-Allow-Origin'] = '*';
        $app_ini = parse_ini_file(PATH.SPATH.'/Conf/app.ini','ture');
        $dirlist = scandir($app_ini['app']['appfile_dir']);
        unset($dirlist[array_search('.', $dirlist)]);
        unset($dirlist[array_search('.', $dirlist)]);
        $dirlist = $this->quick_sort(array_values($dirlist));
        if(is_dir($app_ini['app']['appfile_dir'].'/'.end($dirlist))){
            $version = end($dirlist);
        }else{
            $version = '1.0.2.0';
        }
        return array(
            'data' => json_encode(
                array(
                    'code'=>200,
                    'msg'=>'获取成功',
                    'data'=>array(
                        'AppName'=>'Scanintegral',
                        'AppVersion'=>$version,
                        'AppFileUrl'=>'http://10.0.15.203:33001/mec/'.$version.'/Scanintegral.msi'
                    )
                )
            )
        );
    }
    //快速排序
    private function quick_sort($arr) {
        //先判断是否需要继续进行
        $length = count($arr);
        if($length <= 1) {
            return $arr;
        }
        //选择第一个元素作为基准
        $base_num = $arr[0];
        //遍历除了标尺外的所有元素，按照大小关系放入两个数组内
        //初始化两个数组
        $left_array = array(); //小于基准的
        $right_array = array(); //大于基准的
        for($i=1; $i<$length; $i++) {
            if(version_compare( $base_num, $arr[$i], '>' )) {
                //放入左边数组
                $left_array[] = $arr[$i];
            } else {
                //放入右边
                $right_array[] = $arr[$i];
            }
        }
        //再分别对左边和右边的数组进行相同的排序处理方式递归调用这个函数
        $left_array = $this->quick_sort($left_array);
        $right_array = $this->quick_sort($right_array);
        //合并
        return array_merge($left_array, array($base_num), $right_array);
    }
}

//return new update();