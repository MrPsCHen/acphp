<?php


namespace Adapter;


class Controller
{
    /**
     * @var bool 简易方法
     */
    protected $adapter_function_show = false;
    protected $adapter_function_view = false;
    protected $adapter_function_save = false;
    protected $adapter_function_del  = false;
    protected $adapter_function_add  = false;

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


    /**
     * 自动加载模型
     * 模型名称与数据表对应
     * 允许使用前缀
     * @param Model|null $model 模型对象
     */
    public function autoLoadModel(Model $model = null){
        self::setModelNamespace();
        if(is_subclass_of($model,Model::class)){
            $model->autoParam($this->param);
            $this->AdapterModel = $model;
            return;
        }else{
            $model_class = self::$modelNamespace.(basename(str_replace('\\', '/', strtolower(get_class($this)))));
            if(class_exists($model_class)){
                $this->AdapterModel = new $model_class();
                $this->AdapterModel ->autoParam($this->param);
                return ;
            }else{
                throw new AdapterException('不存在数据表模型:'.$model_class);
            }
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
        if($this->adapter_function_show)return Helper::fatal('Invalid access');

        $this->AdapterModel->autoParam($this->param);
        $this->output = $this->AdapterModel->select();
        return Helper::success([
            'page'  =>$this->AdapterModel->page(),
            'limit' =>$this->AdapterModel->limit(),
            'total' =>$this->AdapterModel->count(),
            'rows'  =>$this->output->back()
        ]);
    }


    public function view(){
        if($this->adapter_function_view)return Helper::fatal('Invalid access');
        $this->AdapterModel->autoParam();
        $this->output = $this->AdapterModel->find();
        return Helper::success($this->output);
    }

    public function del(){
        if($this->adapter_function_del)return Helper::fatal('Invalid access');
        return Helper::auto($this->AdapterModel->delete(),[$this->AdapterModel->error()]);
    }

    public function add(){
        if($this->adapter_function_add)return Helper::fatal('Invalid access');
        return Helper::auto($this->AdapterModel->add(),[$this->AdapterModel->error()]);
    }

    public function save(array $extra_condition = []){
        if($this->adapter_function_save)return Helper::fatal('Invalid access');
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
    }


    /**
     * 必填参数
     */
    public function required(array $required){


        if(empty(array_diff($required,array_intersect($required,array_keys($this->param))))){
            foreach ($this->param as $key => $item){
                if(empty($item)){
                    $this->error_message = $key.' 字段不能为空';
                    return false;
                }
            }
        }else{
            $this->error_message = '缺少必传字段:'.implode(array_diff($required,array_keys($this->param)),',');
            return false;
        }

        return true;

    }

    public function error(){
        return $this->error_message;
    }


}