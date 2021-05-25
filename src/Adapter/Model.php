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

    protected $last_inster_id   = -1;

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
        }else{

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
        foreach ($this->param as $key =>$item){
            if(empty($item))unset($this->param[$key]);
        }

        foreach ($this->param as $key =>$value){
            if(is_string($key) && !is_numeric($key)){
                $this->param[$this->prefix.$this->table.'.'.$key] = $value;
                unset($this->param[$key]);
            }
        }

        $this->cursor->where($this->param);
        $this->cursor->page($this->_page,$this->_size);

//        dd($this->cursor->fetchSql()->select());
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

        return Db::table($this->prefix.$this->table)->where($this->param)->update($data);
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
        $this->cursor->where($this->param);
        return $this->cursor->count($field);
    }
    public function find(){
        $this->ployField();
        $this->ployJoin();
        foreach ($this->param as $key =>$value){
            if(is_string($key) && !is_numeric($key)){
                $this->param[$this->prefix.$this->table.'.'.$key] = $value;
                unset($this->param[$key]);
            }
        }
        $this->cursor->where($this->param);

        $this->cursor_back = $this->cursor->find();

        $this->cursor_to_array = $this->cursor_back;
        $this->ployExtra();
        $this->cursor_back = $this->cursor_to_array;
        return $this->cursor_back;
    }

    public function add(array $extra = [],string $table_name = ''){
        $table = $this->getMasterTable();
        empty($table_name) && $table_name = $this->prefix.$this->table;

        if(!$table->verfiyData($this->param)){
            $this->error_message = $table->error();
            return false;
        }

        $this->param = array_merge($extra,$this->param);
        $table = reset($this->cursor_table);
        unset($this->param[$table->getPrimary()]);
        $this->autoParam($this->param);
        foreach ($this->param as $key =>$val){
            $newKey = $table_name.'.'.$key;
            unset($this->param[$key]);
            $this->param[$newKey] = $val;
        }
        if(empty($this->param)){
            $this->error_message = '参数为空';
            return false;
        }
        $back = $this->cursor->insert($this->param);
        $this->last_inster_id = $this->cursor->getLastInsID();

        return $back;
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


            if(!$table->verfiyData($this->param)){
                $this->error_message = $table->error();
                return false;
            }

            $data = array_merge($this->param,$table->ouputField());
            $data = $this->outField($data);
            if(isset($this->param[$table->getPrimary()])){

                $this->cursor->where([$table->getPrimary()=>$this->param[$table->getPrimary()]]);
            }


            $this->cursor->where($extra_condition);

            if(!($back = $this->cursor->save($data))){

                $this->error_message = $this->cursor->count()>0?'未作修改':'数据不存在';
                return false;
            }
            return true;
        }

        return $this->cursor->save($data);
    }
    public function change(){

    }
    public function inc(string $field, float $step = 1){
//        dd(Db::table($this->prefix.$this->table)->where($this->param)->inc($field,$step)->fetchSql()->update());
        return Db::table($this->prefix.$this->table)->where($this->param)->inc($field,$step)->update();
    }//字段增
    public function dec(string $field, float $step = 1){
        return Db::table($this->prefix.$this->table)->where($this->param)->dec($field,$step)->update();
    }//字段减
    public function toArray(){
        return $this->cursor_to_array;
    }

    public function back(){
        return empty($this->cursor_to_array)?$this->cursor_back:$this->cursor_to_array;
        return $this->cursor_back;
    }


/*-----------------------------------------------------------------------------------------------*/
/*高级查询
 *-----------------------------------------------------------------------------------------------*/
    public function startTrans(){
        $this->cursor->startTrans();
    }

    public function commit(){
        $this->cursor->commit();
    }

    public function rollback(){
        $this->cursor->rollback();
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
        return $this;
    }
    public function group(){}

    public function fetchSql(bool $fetch = true){
        return $this->cursor->fetchSql($fetch);

    }


/*-----------------------------------------------------------------------------------------------*/
/*
 * view聚合查询方法
 */
    public function ploy(Table $table,string $frontPrimary){
        $this->cursor_table[$table->getTable()] = $table;
        $this->cursor_table[$table->getTable()] ->frontPrimary = $frontPrimary;
        $this->cursor_table[$table->getTable()] ->isPloy = true;
    }


    /**
     * join table
     * @param string $table table_name
     * @param string $frontPrimary the field name in master
     * @param string $prefix prefix
     * @param string|null $frontTable
     */
    public function ployTable(string $table,string $frontPrimary, string $prefix= '',string $alias = '',string $frontTable = null){
        if($frontTable === null)$frontTable = reset($this->cursor_table);
        else{
            $frontTable = (isset($this->cursor_table[$frontTable])?$this->cursor_table[$frontTable]:false);
            $frontTable = $frontTable || isset($this->cursor_table[$this->judgePrefix().$frontTable])?$this->cursor_table[$this->judgePrefix().$frontTable]:false;
        }

        $this->cursor_table[$prefix.$table] = new Table($table,$prefix);
        $this->cursor_table[$prefix.$table] ->frontPrimary($frontPrimary);
        $this->cursor_table[$prefix.$table] ->frontTable($frontTable->getTable());
        $this->cursor_table[$prefix.$table] ->setFrontAlias($alias);
        $this->cursor_table[$prefix.$table] ->isPloy = true;
        $this->cursor_table[$this->prefix.$this->table]->ployTable($this->cursor_table);
        !empty($prefix) && ($this->cursor_table[$prefix.$table]->setPrefix($prefix));

    }
/*-----------------------------------------------------------------------------------------------*/
/*
 * 标签聚合查询方法
 */
    /**
     * @param string $table         数据表
     * @param array $extra_mate     搜索字段
     * @param string $prefix        数据表前缀
     * @param string $alias         字段别名
     */
    public function _extra(Table $table){
        $this->cursor_extra[] = $table;
        return ;
    }

    /**
     * @param string $table
     * @param string $prefix
     * @param string $extra_field
     * @param string $alias
     * @param bool $master_key
     */
    public function extra(string $table,string $prefix = '',string $extra_field = '',string $alias = '',bool $master_key = false){
        $table_ins = new Table($prefix.$table);

        if(empty($extra_field)){
            $extra_field = $master_key?($this->table.'_id'):($table.'_id');
        }
        $master_key && $table_ins->setMaster();
        $table_ins->setExtraAslias($alias);
        $table_ins->setExtraField($extra_field);
        $this->cursor_extra[] = $table_ins;

        return $table_ins;
    }

    /**
     * 标签查询
     */
    protected function ployExtra(){
        $extra_tmp = '';
        foreach ($this->cursor_extra as $item){
            $db = Db::table($item->getTable());

            if(!empty($item->getWhere()))$db->where($item->getWhere());

            if($item->getMaster()){
            }else{

                $master = $this->choseTable($this->prefix.$this->table);

                if(empty($this->cursor_to_array))return;
                $ids = array_column($this->cursor_to_array,$master->getPrimary());
                if(empty($ids) && isset($this->cursor_to_array[$master->getPrimary()])){
                    $ids = [$this->cursor_to_array[$master->getPrimary()]];
                }
                $field_name = empty($item->getExtraAslias())?$item->getTable():$item->getExtraAslias();
                $back = $db->where([[$item->getExtraField(),'IN',implode(',',$ids)]])->column(array_merge($item->screenField(),[$item->getExtraField()]),$item->getPrimary());

                foreach ($ids as $key=>$val) {
                    foreach ($back as $_key=>$_val){

                        if($_val[$item->getExtraField()] == $val){
                            if(isset($this->cursor_to_array[$key])&&is_array($this->cursor_to_array[$key])){
                                $this->cursor_to_array[$key][$field_name][] = $_val;
                            }else{
                                $this->cursor_to_array[$field_name][] = $_val;
                            }
                        }
                    }
                }
            }
        }

        return ;
    }





/*-----------------------------------------------------------------------------------------------*/
/*
 * 工具方法
 */
    /**
     * @param array $param
     * 自动参数
     */
    public function autoParam(array $param = [],$prefix = ''){
        if(empty($param))$param = $this->param;

        foreach ($param as $key =>$val){
            if(empty($val)){
                unset($param[$key]);
            }
        }
        if(isset($param['page'])   && is_numeric($param['page'])   && ($this->_page = (int)$param['page']))
            unset($param['page']);
        if(isset($param['limit'])  && is_numeric($param['limit'])  && ($this->_size = (int)$param['limit'])) {
            unset($param['limit']);
        }
        if(isset($param['size'])   && is_numeric($param['size'])   && ($this->_size = (int)$param['size'])){
            unset($param['size']);
        }
        $table = reset($this->cursor_table);

        $table && $this->param = $table->checkoutField($param,true);

        $this->auto_param_stats = true;
        return $this->param;
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
        if(empty($table))return reset($this->cursor_table);
        if(isset($this->cursor_table[$table]))return $this->cursor_table[$table];
        if(isset($this->cursor_table[$this->judgePrefix().$table]))return $this->cursor_table[$this->judgePrefix().$table];
        if($this->cursor_table[$table] = new Table($table))return $this->cursor_table[$table];
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

    /**
     *
     */
    public function getLastInsId(){
        return $this->last_inster_id;
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
//        dd($cursor_table);
        $this->cursor->field($cursor_table);

        return $this;
    }

    /**
     * 聚合表
     */
    protected function ployJoin(){
        while ($item = next($this->cursor_table)){
            if($item->isPloy)
            $this->cursor->join([$item->getTable()=>$item->getTable()],$item->getCondition(),$item->getJoinType());
        }
    }




    /**
     * @param array $array
     * @return array
     */
    protected function outField(array $array){
        $tmp = [];
        $table = $this->prefix.$this->table;
        foreach ($array as $key =>&$item){
            $tmp [$table.'.'.$key] = $item;
        }
        return $tmp;
    }
    
    protected function _like(){
        
    }





}