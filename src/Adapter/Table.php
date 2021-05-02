<?php


namespace Adapter;


use think\facade\Db;

class Table
{
    protected $table                = '';//表名
    protected $prefix               = '';//表前缀

    protected $primary_field        = '';//主键字段
    protected $field_full           = [];//全部字段
    protected $field_not_null       = [];//非空字段
    protected $field_param          = [];//参数字段
    protected $field_default        = [];//默认字段参数
    protected $field_unique         = [];//唯一字段
    protected $field_notes          = [];//字段注释
    protected $field_type           = [];//类型
    protected $field_length         = [];//字段长度

    protected $display_field        = [];//显示字段
    protected $filter_field         = [];//过滤字段
    protected $field_overall        = [];//全体字段:为重复字段添加前缀
    protected $field_output         = [];//全局字段:输出
    protected $field_output_alias   = [];

    protected $front_primary        = '';
    protected $front_table          = '';
    protected $front_join_type           = 'LEFT';

    protected $ploy_tables;
    protected $ploy_table_master    = false;

    public function __construct(string $table,string $prefix = '')
    {
        $this->table     = $table;
        $this->prefix    = $prefix;
        $this->structureTable();

    }



    public function hasField(string $field){
        return in_array($field,$this->field_full)?true:false;
    }


    public function setPrefix(string $prefix = ''){
        !empty($prefix) && $this->prefix = $prefix;
    }
    public function setMaster(){
        $this->ploy_table_master = true;
    }
    public function getMaster(){
        return $this->ploy_table_master;
    }
    public function setOverFiled(array $field = []){$this->field_overall = $field;}

    public function ployTable(array &$ploy_tables = []){
        $this->ploy_tables = $ploy_tables;
    }

    public function getPrimary(){
        return $this->primary_field;
    }

    /**
     * @return array
     */
    public function ployField(bool $show_master_prefix = true){
        $this->screenField();
        $back = $this->outField($show_master_prefix = false);
        while ($item = next($this->ploy_tables)){
            $item->screenField();
            $back = array_merge($back,$item->outField());
        }
        return $back;
    }

    /**
     * @param bool $show_prefix
     * @return array 输出字段
     */
    public function outField(bool $show_prefix = true){
        foreach ($this->field_output as &$item){
            $field_name = isset($this->field_output_alias[$item])?$this->field_output_alias[$item]:$this->table."_".$item;
            $field_name = $show_prefix ? $field_name:$item;
            $item = $this->getTable().'.'.$item. ' AS '.$field_name;
        }

        return $this->field_output;
    }

    /**
     * @param array $array
     * 设置字段别名
     */
    public function alias(array $array = null){
        $this->field_output_alias = $array;

    }

    /**
     * 设置关联表的对于字段
     */
    public function frontPrimary(string $front_primary = null){
        if($front_primary === null)return $this->front_primary;
        $this->front_primary = $front_primary;
    }
    /**
     * 设置关联表的表名
     */
    public function frontTable(string $front_table = null){
        if($front_table === null)return $this->front_table;
        $this->front_table = $front_table;
    }


    /**
     *
     */
    public function screenField(){
        $fields = $this->field_full;
        foreach ($fields as $key=>$item){
            if(!empty($this->display_field)&&!in_array($item,$this->display_field))unset($fields[$key]);
            if(in_array($item,$this->filter_field))unset($fields[$key]);
        }
        $this->field_output = $fields;
        return $fields;
    }

    /**
     * @param bool $prefix
     * @return string
     */
    public function getTable(bool $prefix = true){
       return ($prefix?$this->prefix:'').$this->table;
    }

    public function getCondition(){
        return "{$this->frontTable()}.{$this->front_primary} = {$this->getTable()}.{$this->primary_field}";
    }



    public function getJoinType(string $front_join_type = null){
        if($front_join_type === null)return $this->front_join_type;
        $this->front_join_type = $front_join_type;
    }


    public function display(array $field = []){
        $this->display_field = $field;
    }
    public function filter(array $field = []){
        $this->filter_field = $field;
    }


//
//    protected $compound_table   = [];//复合表
//
//    /**
//     * @var string
//     */
//    public $ploy_primary_field  = '';//聚合字段
//
//    public function __construct(string $table,string $prefix = '')
//    {
//        $this->table     = $table;
//        $this->prefix    = $prefix;
//        $this->dissectTable();
//        $this->field_overall = $this->field_full;
//
//    }
//
//    /**
//     * 解析数据表
//     */
    public function structureTable(){

        Db::table($this->prefix.$this->table);
        foreach (Db::query('SHOW FULL COLUMNS FROM '.$this->prefix.$this->table) as $k => $v){
            $v['Key'] === 'PRI' && $this->primary_field = $v['Field'];
            $v['Key'] !== 'PRI' && $this->field_param[] = $v['Field'];
            $v['Key'] === 'UNI' && $this->field_unique[] = $v['Field'];

            strtoupper($v['Null']) === 'NO' && $v['Key'] !== 'PRI' && $this->field_not_null[] = $v['Field'];

            !empty($v['Default']) && $this->field_default[$v['Field']] = $v['Default'];
            $this->field_full[] = $v['Field'];
            $this->field_notes[$v['Field']] = $v['Comment'];
            list($this->field_type[],$this->field_length[]) = $this->formatType($v['Type']);

            $v['Default'] == 'CURRENT_TIMESTAMP' && $v['Type'] == 'datetime' && $this->field_default[$v['Field']] = date("Y-m-d h:i:s", time());
        }
    }
//
//
    /**
     * @param string $type
     * @return false|string[]
     */
    private function formatType(string $type){
        if(count($_type = explode('(',trim($type,')'))) <2){
            $_type[] = '0';
        }
        return $_type;
    }

}