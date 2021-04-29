<?php


namespace Adapter;


use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Db;

class Model
{
    protected $table;
    protected $prefix;
    protected $param = [];

    protected $cursor;
    protected $cursor_table;
    protected $ploy_table = [];

    protected $_page;
    protected $_size;

    public function __construct(string $table = '',string $prefix = '')
    {
        $this->judgePrefix($prefix);
        $this->judgeTable($table);

        $this->cursor_table = new Table($this->table,$this->prefix);
        $this->cursor = Db::table($this->prefix.$this->table);
    }
/*-----------------------------------------------------------------------------------------------*/
/*
 * 基本查询方法
 */

    public function insert(){}
    public function delete(){}
    public function select(){}
    public function update(){}

    public function insertAll(){}

    public function count(){}
    public function find(){}
    public function save(){}
    public function add(){}
    public function change(){}
    public function setInc(){}//字段增
    public function setDec(){}//字段减


/*-----------------------------------------------------------------------------------------------*/
/*
 * 修饰方法
 */
    public function where(){}
    public function order(){}
    public function group(){}


/*-----------------------------------------------------------------------------------------------*/
/*
 * view聚合查询方法
 */
    public function ploy(string $table,string $frontPrimary, string $prefix= '',$frontTable = null){
        $this->ploy_table[$table] = new Table($table,$prefix);
        $this->ploy_table[$table] ->frontPrimary = $frontPrimary;
    }


/*-----------------------------------------------------------------------------------------------*/
/*
 * 标签聚合查询方法
 */
    public function extra(){}




/*-----------------------------------------------------------------------------------------------*/
/*
 * 工具方法
 */
    /**
     * @param array $param
     * 自动参数
     */
    function autoParam(array $param){
        isset($param['page'])   && is_numeric($param['page'])   && $this->_page = (int)$this->_page;
        isset($param['limit'])  && is_numeric($param['limit'])  && $this->_page = (int)$this->_page;
        isset($param['size'])   && is_numeric($param['size'])   && $this->_page = (int)$this->_page;
    }

/*-----------------------------------------------------------------------------------------------*/
/*
 *  辅助方法
 */

    protected function judgeTable(string $table = ''){

        if(!empty($table))return $this->table = $table;
        if(empty(($this->table))) return $this->table = get_class($this);

    }
    protected function judgePrefix(string $prefix = ''){
        if(!empty($prefix))return $this->prefix = $prefix;

        if(empty($this->prefix) && function_exists('env'))
            return $this->prefix = env('database.prefix','');
    }





//    protected $table        = '';
//    protected $prefix       = '';
//    protected $table_name   = null;
//    protected $adapterTable = null;
//
//    protected $ploy_table    = [];
//    protected $param        = [];
//    protected $where_field  = [];
//
//
//    protected $cursor;//游标对象
//
//    protected $back         ='';
//    protected $error        ='';
//    protected $errno        ='';
//
//    protected $field_overall='';//全部字段
//    protected $field_query  ='';//查询字段
//    /**
//     * @var int
//     */
//    private $_page;
//    /**
//     * @var int
//     */
//    private $_limit;

//
//    public function __construct(array $data = [])
//    {
//        $this->getClassName();
//        $this->adapterTable = new Table($this->table_name,$this->prefix);
//        $this->cursor = Db::table($this->adapterTable->getTable());
//    }
//
//    /**
//     * 聚合表
//     * @param string $table
//     * @param string $primary
//     * @param string $prefix
//     * @return Model
//     */
//    public function ployTable(string $table,string $primary,string $prefix = ''): Model
//    {
//        $tableObj = new Table($table,$prefix);
//        $tableObj->ploy_primary_field = $primary;
//        $this->ploy_table[$table] = $tableObj;
//        $this->adapterTable->ployTable($tableObj);
//        return $this;
//    }
//
//    public function ployWhere(array $array = []){
//        $this->ployAutoParam($array);
//        return $this;
//    }
//
//    /**
//     * 自动参数处理
//     * @param array $array
//     */
//    public function ployAutoParam(array $array = []){
//
//        $this->param = empty($array)?$this->param:$array;
//        isset($this->param['page']) && $this->_page = 1;
//        isset($this->param['limit']) && $this->_limit = 30;
//        $this->handleParam();
//
//    }
//
//
//
//    public function ployOrderBy(array $array = []){
//        $this->cursor->order($array);
//        return $this;
//    }
//
//    public function ployCount(){
//        $this->back = $this->cursor->count();
//        return $this;
//    }
//
//    public function ploySelect(bool $back = false){
//
//        try {
//            $this->back = $this
//                ->cursor
//                ->field($this->adapterTable->renderField())
//                ->where($this->where_field)
//                ->select()
//                ->toArray();
//        } catch (DataNotFoundException $e) {
//            $this->back = [];
//            $this->error = $e->getMessage();
//        } catch (ModelNotFoundException $e) {
//            $this->back = [];
//            $this->error = $e->getMessage();
//        } catch (DbException $e) {
//            $this->error = $e->getMessage();
//        }
//        if($back){
//            return $this->back;
//        }
//
//        return $this;
//    }
//
//    public function ployFind(bool $back = true){
//        try {
//            $this->back = $this
//                        ->cursor
//                        ->field($this->adapterTable->renderField())
//                        ->where($this->where_field)
//                        ->find();
//
//        } catch (DataNotFoundException $e) {
//            $this->error = $e->getMessage();
//        } catch (ModelNotFoundException $e) {
//            $this->error = $e->getMessage();
//        } catch (DbException $e) {
//            $this->error = $e->getMessage();
//        }
//        if($back)
//            return $this->back();
//
//        return $this;
//    }
//
//
//    public function ployUpdate(){
//
//    }
//
//    public function ployDelete(){
//
//    }
//
//    public function ploySave(){
//
//    }
//
//    public function ployInsert(){
//
//    }
//
//    public function ployInsertAll(){
//
//    }
//
//    public function ployErrorMessage(){
//
//    }
//    /*-----------------------------------------------------------------------------------------------*/
//    /*
//     * 外部数据设置
//     */
//    public function setParam(array $param){
//        $this->param = $param;
//    }
//    public function Back(){
//        return $this->back;
//    }
//    /*-----------------------------------------------------------------------------------------------*/
//    /*
//     * 字段渲染处理
//     */
//    //显示主表字段
//    public function displayField(array $array = []){
//
//        $this->adapterTable->display($array);
//    }
//
//    /**
//     * 过滤字段
//     * @param array $array
//     */
//    public function filterField(array $array = []){
//        $this->adapterTable->filter($array);
//    }
//
//    /**
//     * 显示关联表字段
//     */
//    public function ployDisplayField(){
//
//
//    }
//
//
//    /**
//     * 过滤关联表字段
//     */
//    public function ployFilterField(){
//
//    }
//
//    /*-----------------------------------------------------------------------------------------------*/
//    /*
//     * 复合数据查询
//     */
//
//    /**
//     *
//     */
//    public function ployExtraSelect(){
//
//    }
//
//    public function ployExtraFind(){
//
//    }
//    /*-----------------------------------------------------------------------------------------------*/
//    /*
//     * 私有方法
//     */
//    /**
//     * 通过类获取表名
//     */
//    protected function getClassName(){
//        if(empty($this->table)){
//            $this->table = $this->prefix.get_class($this);
//            $this->table_name = get_class($this);
//        }
//    }
//
//    /**
//     * 装配字段
//     */
//    protected function matchField(){
//        foreach ($this->ploy_table as &$item){
////            $this->adapterTable->ployTable($item);
//        }
//    }
//
//
//    /**
//     * 第一个数组作为参考 进行合并value
//     * 该方法直接修改one，无返回值
//     * @param array $one
//     * @param array $other
//     * @param bool $del_empty_flag=false  删除值为空的键值
//     */
//    protected function array_merge_one(array &$one, array $other,bool $del_empty_flag = false){
//        foreach ($one as $k=>$v){
//            if(is_numeric($k)){
//                $one[$v] = '';
//                unset($one[$k]);
//            }
//            isset($other[$v]) && $one[$v] = $other[$v];
//            isset($other[$k]) && $one[$k] = $other[$k];
//
//        }
//        if($del_empty_flag)
//            foreach ($one as $k=>$v){
//                if(is_array($one[$k]))continue;
//                if(strlen($one[$k])<=0)unset($one[$k]);
//            }
//    }
//
//    /**
//     * 参数处理
//     */
//    protected function handleParam(){
//        $this->matchField();
//        $this->field_overall = $this->adapterTable->overallField();
//        $this->array_merge_one($this->where_field,$this->param,true);
//    }

}