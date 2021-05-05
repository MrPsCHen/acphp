<?php


namespace Adapter;


class Controller
{
    private $AdapterModels = [];
    protected $namespace = '';
    protected $AdapterModel;
    protected $output = [];
    protected $table = [];
    protected $error_message = '';
    public $param = [];
    public static $modelClass;
    public static $modelNamespace = '';

    public function __construct(Model $model = null)
    {
        $this->param = request()->param();
        $this->autoLoadModel($model);


    }

    public function autoLoadModel(Model $model = null){
        self::setModelNamespace();

        if(is_subclass_of($model,Model::class)){
            $this->AdapterModel = $model;
            $this->AdapterModel && $this->AdapterModel->autoParam($this->param);
        }else{
            if (is_string($this->table)){
                $model_name = self::$modelNamespace.$this->table;
                $this->AdapterModel = new $model_name();
            }else{
                foreach ($this->table as $item){
                    $this->AdapterModels[$item]=$this->implant(new $item());
                }
            }
        }

        if(!$this->AdapterModel)$this->AdapterModel = new Model();
        $this->AdapterModel->autoParam($this->param);
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
        $this->output = $this->AdapterModel->select();
//        return $this->output->back();
        return Helper::success([
            'page'  =>$this->AdapterModel->page(),
            'limit' =>$this->AdapterModel->limit(),
            'total' =>$this->AdapterModel->count(),
            'rows'  =>$this->output->back()
        ]);
    }


    public function view(){
        $this->AdapterModel->autoParam();
        $this->output = $this->AdapterModel->find();
        return Helper::success($this->output);
    }

    public function del(){
        return Helper::auto($this->AdapterModel->delete(),[$this->AdapterModel->error()]);
    }

    public function add(){
        return Helper::auto($this->AdapterModel->add(),[$this->AdapterModel->error()]);
    }

    public function save(array $extra_condition = []){
        $this->AdapterModel->autoParam();
        return Helper::auto($this->AdapterModel->save(),[$this->AdapterModel->error()]);
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

    public static function setModelNamespace(string $namespace = ''){
        if(empty($namespace)){
            self::$modelNamespace = '\\app\\';
            self::$modelNamespace.= empty($app = app('http')->getName())?'':$app.'\\';
            self::$modelNamespace.= "model\\";

            return;
        }else{
            self::$modelNamespace = $namespace;
            return;
        }
        self::$modelNamespace = '\\app\\model\\';
        echo self::$modelNamespace;
    }


    /**
     * 必填参数
     */
    public function required(array $required){
        foreach ($required as $item){
            if(!isset($this->param[$item])) {
                $this->error_message = '缺少参数'.(app()->isDebug()?(":".$item):'');
                return false;
            }
        }
        return true;
    }

    public function error(){
        return $this->error_message;
    }


}