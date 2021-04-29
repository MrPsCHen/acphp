<?php


namespace Adapter;


class Controller
{
    protected $AdapterModel;
    protected $output = [];
    public $param = [];
    public static $modelClass;

    public function __construct()
    {
        $this->implant();
        $this->AdapterModel && $this->AdapterModel->setParam($this->param);
    }

    public function implant(Model $adapterModel = null){
        if($this->AdapterModel = $adapterModel){

        }else if(class_exists(self::$modelClass)){
            $class = self::$modelClass;
            $this->AdapterModel = new $class();
        }else{
            return false;
        }
        return true;
    }

    public function show(){
        $this->AdapterModel->ployAutoParam();
        $this->output = $this->AdapterModel->ploySelect()->Back();
//        return Helper::success([
//            'page'  =>'page',
//            'limit' =>'limit',
//            'total' =>'total',
//            'rows'  =>$this->output
//        ]);
    }


    public function view(){
        $this->AdapterModel->ployAutoParam();
        $this->output = $this->AdapterModel->ployFind();


        return Helper::success($this->output);
    }

    public function del(){

    }

    public function add(){

    }

    public function save(){

    }

    /**
     * @param string $table
     * @param string $primary
     * @param string $prefix
     * @param null $frontTable
     *
     */
    public function ploy(string $table,string $primary, string $prefix= '',$frontTable = null){
        $this->AdapterModel->ploy($table,$primary,$prefix,$frontTable);
//        $this->AdapterModel->ployTable($table,$primary,$prefix);
    }


    public function Out(){
        return Helper::success([
            'page'  =>'page',
            'limit' =>'limit',
            'total' =>'total',
            'rows'  =>$this->output
        ]);
    }

    public function setParam(array $array = []){
        if($this->AdapterModel){
            $this->AdapterModel->setParam($array);
            return true;
        }
        return false;

    }

    public function getModel(){
        return $this->AdapterModel;
    }


}