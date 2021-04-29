<?php


namespace Adapter;


class Helper
{
    public static $CODE = 200;
    public static $MSG  = null;
    public static $DATA = null;

    public static function success($data = [],string $msg = 'success'){
        self::$MSG  = $msg;
        self::$DATA = is_array($data)?$data:[];
        return self::assemble();
    }

    public static function warning(string $msg = 'warning',int $code = 201,array $data = []){
        self::$CODE = $code;
        self::$MSG  = $msg;
        self::$DATA = $data;
        return self::assemble();
    }

    public static function fatal(string $msg = 'error',int $code = 202,array $data = []){
        self::$CODE = $code;
        self::$MSG  = $msg;
        self::$DATA = $data;
        return self::assemble();
    }

    private static function assemble(){
        $back = ['code'=>self::$CODE];
        $back['msg'] = self::$MSG = empty(self::$MSG)?'':self::$MSG;
        $back['data'] = empty(self::$DATA)?[]:self::$DATA;
        if(function_exists("json")){
            return json($back);
        }
        else{
            return json_encode($back);
        }

    }


    /**
     * @param bool $status
     * @param array $msg
     * @param array $data
     * @return \think\response\Json
     */
    public static function auto(bool $status = true,array $msg = [],array $data = []){
        if($status){
            return self::success($data,empty($msg[1])?'success':$msg[1]);
        }else{
            return self::warning(empty($msg[0])?'error':$msg[0],201,$data);
        }
    }

}