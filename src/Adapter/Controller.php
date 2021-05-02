<?php


namespace Adapter;


class Controller
{
    protected $AdapterModel;
    protected $output = [];
    public $param = [];
    public static $modelClass;
    public static $modelNamespace = '';

    public function __construct(Model $model = null)
    {
        if(is_subclass_of($model,Model::class)){
            $this->AdapterModel = $model;
            $this->AdapterModel && $this->AdapterModel->autoParam($this->param);
        }else{
            $this->implant($model);
        }
    }

    public function implant(Model $adapterModel = null){
        if(!is_null($adapterModel)&&!(($this->AdapterModel = $adapterModel) instanceof  Model)){

            return false;
        }
        self::$modelClass = get_class($this);
        $class = self::$modelClass.get_class($this);


        if(class_exists($class) && ($AdapterModel = new $class()) instanceof Model){

            $this->AdapterModel = $AdapterModel;
            return true;
        }
        return false;
    }


    public function show(){
        $this->AdapterModel->autoParam();

        return $this->output = $this->AdapterModel->select()->toArray();
//        return Helper::success([
//            'page'  =>'page',
//            'lim.it' =>'limit',
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
        if($this->AdapterModel){
            $this->AdapterModel->ployTable($table,$primary,$prefix,$frontTable);
        }

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

    public function getModel(string $table = ''){
        return $this->AdapterModel;
    }

    public function choseTable(string $table = null){
        return $this->AdapterModel->choseTable($table);
    }

    public static function setModelNamespace(string $namespace){
        self::$modelNamespace = $namespace;
    }


}