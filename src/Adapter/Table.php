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
    protected $field_output_alias   = '';

    protected $ploy_tables;
    public function __construct(string $table,string $prefix = '')
    {
        $this->table     = $table;
        $this->prefix    = $prefix;
        $this->structureTable();

    }





    public function setPrefix(string $prefix = ''){
        !empty($prefix) && $this->prefix = $prefix;
    }

    public function ployTable(array &$ploy_tables){
        $this->ploy_tables = $ploy_tables;
        foreach ($this->ploy_tables as $key=>$item){
            if(!($item instanceof Table)) unset($this->ploy_tables[$key]);
        }
    }

    /**
     * @param bool $isMaster
     */
    public function getField(bool $isMaster = true){
        return $this->screenField();
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
        return $fields;
    }

    /**
     * @param bool $prefix
     * @return string
     */
    public function getTable(bool $prefix = true){
       return ($prefix?$this->prefix:'').$this->table;
    }

    public function display(array $field = []){
        $this->display_field = $field;
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
//
//
//
//
//
//    protected function formatTable(array $array){
//
//    }
//
//
//    public function getFieldFull(){
//        return $this->field_full;
//    }
//
//    public function getTable(){
//        return $this->prefix.$this->table;
//    }
//
//
//    /**
//     * 装配字段
//     */
//    public function matchField(){
//
//    }
//
//    /**
//     * 渲染字段
//     * @param string $alias
//     * @return array
//     */
//    public function renderField(string $alias = null){
//        $this->trimField();
//
//        $alias =$this->table;
//
//        $tmp = [];
//        foreach ($this->field_output as &$val){
//            $alias_str = empty($alias)?'':" AS `{$alias}_{$val}`";
//            $tmp[] ="`{$this->prefix}{$this->table}.{$val}`".$alias_str;
//        }
//
//
//        print_r($tmp);
//
//
//
//        return [];
//    }
//
//
//    /**
//     * 参数验证
//     */
//    public function verifyParam(){
//
//    }
//
//
//    /**
//     * 唯一性验证
//     */
//    public function verifyUnique(){
//
//    }
//
//
//    /**
//     * 完整性验证:必填字段
//     */
//    public function verifyComplete(){
//
//    }
//
//
//    /**
//     * 复合表
//     * @param Table $adapterTable
//     */
//    public function ployTable(Table $adapterTable){
//        $this->compound_table[$adapterTable->getTable()] = $adapterTable;
//    }
//
//
//    public function display(array $array){
//        $this->display_field = $array;
//    }
//    public function filter(array $array){
//        $this->filter_field = $array;
//    }
//
//    public function overallField(){
//
//        foreach ($this->compound_table as & $item){
//            $this->field_overall = array_merge($this->field_overall,$item->field_full);
//        }
//
//        $this->field_overall = array_unique($this->field_overall);
//
//        return $this->field_overall;
//    }
//
//
//
//    protected function assignField(){
//
//    }
//
//
//    /**
//     * 修剪字段,将修剪后的字段输出到output;
//     */
//    protected function trimField(){
//        $this->field_output = $this->field_full;
//
//        foreach ($this->filter_field as $key => &$item){
//
//             if(in_array($item,$this->field_output)){
//                 unset($this->field_output[$key]);
//             }
//
//        }
//
//        if(!empty($this->display_field)){
//            foreach ($this->field_output as $key => &$item){
//                if(!in_array($this->field_output[$key],$this->display_field))unset($this->field_output[$key]);
//            }
//        }
//
//
//
//
//    }

}