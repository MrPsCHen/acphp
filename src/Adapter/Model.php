<?php


namespace Adapter;


use think\db\exception\DbException as Exception;
use think\db\Raw;
use think\facade\Db;

class Model
{
    protected $table;
    protected $prefix;
    protected $param            = [];
    protected $param_like       = [];
    protected $param_in         = [];

    protected $auto_param_stats = false;
    protected $cursor;
    protected $cursor_back;
    protected $cursor_to_array  = [];
    protected $cursor_table     = [];
    protected $cursor_extra     = [];
    protected $cursor_master    = null;

    protected $error_message    = '';

    protected $_page            = 1;
    protected $_size            = 20;

    public function __construct(string $table = '',string $prefix = '')
    {
        if(get_class() == get_class($this))return null;
        $this->judgePrefix($prefix);
        $this->judgeTable($table);
        if($this->table){
            $table_name = $this->prefix.$this->table;
            $this->cursor_table[$table_name] = new Table($this->table,$this->prefix);
            $this->cursor_table[$table_name] ->setMaster();
            $this->cursor_table[$table_name] ->ployTable($this->cursor_table);
            $this->cursor = Db::table($this->prefix.$this->table);
        }
    }
/*-----------------------------------------------------------------------------------------------*/
/*
 * 基本查询方法
 */

    public function insert(){


    }
    public function delete(array $where = []){
        if(empty($where)){
            $this->autoParam();
        }else{
            $this->autoParam($where);
        }
        if(empty($this->param)){
            $this->error_message = '需添加删除条件';
            return false;
        }
        $this->cursor->where($this->param);
        if(!$this->cursor->delete()){
            $this->error_message = "数据不存在";
            return false;
        }
        return true;
    }


    public function select()
    {
        $this->ployField();
        $this->ployJoin();
        $this->autoParam();
        $this->cursor->where($this->param);
        $this->cursor->page($this->_page,$this->_size);
        $this->cursor_back = $this->cursor->select();

        $this->cursor_to_array = $this->cursor_back->toArray();
        $this->ployExtra();
        return $this;
    }

    /**
     * 更新记录
     * @access public
     * @param mixed $data 数据
     * @return integer
     * @throws Exception
     */
    public function update(array $data = []){
        return $this->cursor->update($data);
    }

    /**
     * 批量插入记录
     * @access public
     * @param array   $dataSet 数据集
     * @param integer $limit   每次写入数据限制
     * @return integer
     */
    public function insertAll(array $dataSet=[],int $limit = 0){
        return $this->cursor->insertAll($dataSet,$limit);
    }

    /**
     * COUNT查询
     * @access public
     * @param string|Raw $field 字段名
     * @return int
     */
    public function count(string $field = '*'){
        $this->ployField();
        $this->ployJoin();
        $this->cursor->where($this->param);
//        dd($this->cursor->fetchSql()->count($field));
        return $this->cursor->count($field);
    }
    public function find(){
        $this->ployField();
        $this->ployJoin();
        $this->cursor->where($this->param);

        $this->cursor_back = $this->cursor->find();


        $this->ployExtra();
        return $this->cursor_back;
    }

    public function add(array $extra = []){
        $this->param = array_merge($extra,$this->param);
        $table = reset($this->cursor_table);
        unset($this->param[$table->getPrimary()]);
        $this->autoParam($this->param);
        return $this->save($this->param);
    }

    /**
     * 保存数据 自动判断insert或者update
     * @access public
     * @param  array $data        数据
     * @param  bool  $forceInsert 是否强制insert
     * @return string
     */
    public function save(array $data = [],array $extra_condition = []){

        if($this->auto_param_stats){

            $table = $this->getMasterTable();
            if(!$table->has()){
                $this->error_message = '数据表不存在';
                return false;
            }
            $data = $table->checkoutField($this->param);
            if(empty($data)){
                $this->error_message = '缺少参数'.(app()->isDebug()?(":".implode(',',$table->getFieldNotNull())):"");
                return false;
            }



            if(!$table->verfiyData($data)){

                $this->error_message = $table->error();
                return false;
            }

            if(isset($this->param[$table->getPrimary()])){

                $this->cursor->where([$table->getPrimary()=>$this->param[$table->getPrimary()]]);
            }

            if(!($back = $this->cursor->save($data))){

                $this->error_message = '未作修改';
                return false;
            }
            return true;
        }

        return $this->cursor->save($data);
    }
    public function change(){

    }
    public function setInc(){}//字段增
    public function setDec(){}//字段减
    public function toArray(){
        return $this->cursor_to_array;
    }

    public function back(){
        return $this->cursor_back;
    }

/*-----------------------------------------------------------------------------------------------*/
/*
 * 修饰方法
 */
    public function where(array $condition = [],string $table = ''){
        $table = $this->choseTable($table);
        if(!$table)$table = reset($this->cursor_table);
        $this->cursor->where($table->ployCondition($condition));
        return $this;
    }
    public function order(string $field_name,string $sort_type = 'ASC'){
        $this->cursor->order($field_name,$sort_type);
    }
    public function group(){}


/*-----------------------------------------------------------------------------------------------*/
/*
 * view聚合查询方法
 */
    public function ploy(Table $table,string $frontPrimary){
        $this->cursor_table[$table->getTable()] = $table;
        $this->cursor_table[$table->getTable()] ->frontPrimary = $frontPrimary;
    }


    /**
     * join table
     * @param string $table table_name
     * @param string $frontPrimary the field name in master
     * @param string $prefix prefix
     * @param string|null $frontTable
     */
    public function ployTable(string $table,string $frontPrimary, string $prefix= '',string $frontTable = null){


        if($frontTable === null)$frontTable = reset($this->cursor_table);
        else{
            $frontTable = (isset($this->cursor_table[$frontTable])?$this->cursor_table[$frontTable]:false);
            $frontTable = $frontTable || isset($this->cursor_table[$this->judgePrefix().$frontTable])?$this->cursor_table[$this->judgePrefix().$frontTable]:false;
        }

        $this->cursor_table[$prefix.$table] = new Table($table,$prefix);
        $this->cursor_table[$prefix.$table] ->frontPrimary($frontPrimary);
        $this->cursor_table[$prefix.$table] ->frontTable($frontTable->getTable());

        $this->cursor_table[$this->prefix.$this->table]->ployTable($this->cursor_table);
        !empty($prefix) && ($this->cursor_table[$prefix.$table]->setPrefix($prefix));
    }
/*-----------------------------------------------------------------------------------------------*/
/*
 * 标签聚合查询方法
 */
    public function extra(string $table,string $extra_field,string $alias = null){
        $table = $this->choseTable($table);
        $master_table = reset($this->cursor_table);
        if($master_table->hasField($extra_field) &&$table){
            $this->cursor_extra = [$table,$extra_field,$alias];
        }else{
            return false;
        }


    }





/*-----------------------------------------------------------------------------------------------*/
/*
 * 工具方法
 */
    /**
     * @param array $param
     * 自动参数
     */
    public function autoParam(array $param = []){
        if(empty($param))$param = $this->param;
        isset($param['page'])   && is_numeric($param['page'])   && ($this->_page = (int)$param['page']);
        isset($param['limit'])  && is_numeric($param['limit'])  && ($this->_size = (int)$param['limit']);
        isset($param['size'])   && is_numeric($param['size'])   && ($this->_size = (int)$param['size']);
        $table = reset($this->cursor_table);
        $table && $this->param = $table->checkoutField($param,true);
        $this->auto_param_stats = true;
    }


    /**
     * 检查表是否存在
     */
    private function checkTable(){

    }

    /**
     * @param array $field
     * @param string|null $table
     */
    public function display(array $field,string $table = null){
        is_null($table) && $table = $this->table;
        foreach ($this->cursor_table as &$item){
            if($table === $item->getTable() || $table === $item->getTable(false)) {
                $item->display($field);
                break;
            }
        }

    }
    public function filter(array $field, string $table = null){
        is_null($table) && $table = $this->table;
        foreach ($this->cursor_table as &$item){
            if($table === $item->getTable() || $table === $item->getTable(false)) {
                $item->filter($field);
                break;
            }
        }
    }

    /**
     * 选择表
     * @param string|null $table
     */
    public function choseTable(string $table = null){
        if(is_null($table))reset($this->cursor_table);
        if(isset($this->cursor_table[$table]))return $this->cursor_table[$table];
        if(isset($this->cursor_table[$this->judgePrefix().$table]))return $this->cursor_table[$this->judgePrefix().$table];
        return false;
    }

    public function setIike(array $array){
        $this->param_like = $array;
    }
    public function setIn($array){
        $this->param_in = $array;
    }

    public function error(){
        return $this->error_message;
    }

    public function getMasterTable(){
        if($master = reset($this->cursor_table))return $master;
        return new Table('');
    }
    public function getParam(){
        return $this->param;
    }
    public function getCursor(){
        return $this->cursor;
    }
    public function page(){
        return $this->_page;
    }

    public function limit(){
        return $this->_size;
    }

/*-----------------------------------------------------------------------------------------------*/
/*
 *  辅助方法
 */


    /**
     * @param string $table
     * @return false|string
     * 通过类名取得表名
     */
    protected function judgeTable(string $table = ''){
        if(!empty($table))
            return $this->table = $table;
        if(empty(($this->table)))
            return $this->table = basename(str_replace('\\', '/', get_class($this)));;

    }

    /**
     * @param string $prefix
     * @return mixed|string
     * 通过配置文件取得前缀
     */
    protected function judgePrefix(string $prefix = ''){
        if(!empty($prefix))
            return $this->prefix = $prefix;
        if(empty($this->prefix) && function_exists('env'))
            return $this->prefix = env('database.prefix','');
    }



    /**
     * 聚合字段
     */
    protected function ployField(){

        if(empty($this->cursor_table))throw new AdapterException('未初始化数据表');
        $cursor_table = $this->cursor_table[$this->prefix.$this->table]->ployField();
        $this->cursor->field($cursor_table);

        return $this;
    }

    /**
     * 聚合表
     */
    protected function ployJoin(){
        while ($item = next($this->cursor_table)){
            $this->cursor->join($item->getTable(),$item->getCondition(),$item->getJoinType());
        }
    }

    /**
     * 标签查询
     */
    protected function ployExtra(){
        $extra_back = [];
        if($this->cursor_extra){
            $extra_back = Db::table($this->cursor_extra[0]->getTable())
                        ->where([$this->cursor_extra[0]->getPrimary()=>array_column($this->cursor_to_array,$this->cursor_extra[1])])
                        ->select()
                        ->toArray();
        }
        if(!empty($this->cursor_extra))
        foreach ($this->cursor_to_array as &$item){
            if(isset($item[$this->cursor_extra[1]])){
                foreach ($extra_back as $key=>$value){
                    if($value[$this->cursor_extra[1]] == $item[$this->cursor_extra[1]]){
                        $item[$this->cursor_extra[2]??$this->cursor_extra[0]->getTable()] = $value;
                    }
                }
            }
        }

    }
    
    protected function _like(){
        
    }





}