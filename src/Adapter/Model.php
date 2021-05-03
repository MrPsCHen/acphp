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
    protected $cursor_back;
    protected $cursor_to_array = [];
    protected $cursor_table = [];
    protected $cursor_extra = [];

    protected $_page;
    protected $_size;

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

    public function insert(){}
    public function delete(){}
    public function select()
    {
        $this->ployField();
        $this->ployJoin();
        $this->cursor_back = $this->cursor->select();

        $this->cursor_to_array = $this->cursor_back->toArray();
        $this->ployExtra();
        return $this;
    }
    public function update(){}

    public function insertAll(){}

    public function count(){}
    public function find(){}
    public function save(){}
    public function add(){}
    public function change(){}
    public function setInc(){}//字段增
    public function setDec(){}//字段减
    public function toArray(){
        return $this->cursor_to_array;
    }

/*-----------------------------------------------------------------------------------------------*/
/*
 * 修饰方法
 */
    public function where(array $condition = []){
        $this->cursor->where($condition);
        return $this;
    }
    public function order(){}
    public function group(){}


/*-----------------------------------------------------------------------------------------------*/
/*
 * view聚合查询方法
 */
    public function ploy(Table $table,string $frontPrimary){
        $this->cursor_table[$table->getTable()] = $table;
        $this->cursor_table[$table->getTable()] ->frontPrimary = $frontPrimary;
    }

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

        isset($param['page'])   && is_numeric($param['page'])   && $this->_page = (int)$this->_page;
        isset($param['limit'])  && is_numeric($param['limit'])  && $this->_page = (int)$this->_page;
        isset($param['size'])   && is_numeric($param['size'])   && $this->_page = (int)$this->_page;

        $this->param = $param;

    }
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
        $cursor_table = $this->cursor_table[$this->prefix.$this->table]->ployField();
        $this->cursor->field($cursor_table);
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
        foreach ($this->cursor_to_array as &$item){
            if(isset($item[$this->cursor_extra[1]])){
                foreach ($extra_back as $key=>$value){

                    if($value[$this->cursor_extra[1]] == $item[$this->cursor_extra[1]]){
                        $item[$this->cursor_extra[2]??$this->cursor_extra[0]->getTable()] = $value;
                    }
                }
            }
        }
//        $extra_back = Db::table($this->cursor_extra->getTable());


//        foreach ($this->cursor_extra as $key =>$item){
//            $extra_back[$item[1]] = [Db::table($item[0]->getTable())->where([$item[0]->getPrimary()=>array_column($this->cursor_to_array,$item[1])])->select(),$item[2]];
//
//        }
//
//        foreach ($this->cursor_to_array as &$item){
//            $field = array_keys($item);
//            foreach ($extra_back as $key => &$extra_item){
//
//            }
//        }

    }





}