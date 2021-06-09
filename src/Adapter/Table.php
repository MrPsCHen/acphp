<?php


namespace Adapter;


use think\facade\Db;

class Table
{

    const FIELD_TYPE = [
        'int'       =>'numeric',
        'tinyint'   =>'numeric',
        'smallint'  =>'numeric',
        'mediumint' =>'numeric',
        'integer'   =>'numeric',
        'bigint'    =>'numeric',
        'float'     =>'numeric',
        'double'    =>'numeric',
        'decimal'   =>'numeric',
        'date'      =>'date',
        'time'      =>'date',
        'year'      =>'date',
        'datetime'  =>'date',
        'timestamp' =>'date',
        'char'      =>'string',
        'varchar'   =>'string',
        'tinyblob'  =>'string',
        'blob'      =>'string',
        'mediumblob'=>'string',
        'longblob'  =>'string',
        'tinytext'  =>'text',
        'text'      =>'text',
        'mediumtext'=>'text',
        'longtext'  =>'text',

    ];


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
    protected $front_join_type      = 'LEFT';
    protected $front_alias          = '';

    protected $extra_alias          = '';//别名
    protected $extra_field          = '';//匹配字段

    protected $ploy_tables;
    protected $ploy_table_master    = false;
    protected $error_message        = '';

    protected $output_field         = [];

    protected $table_where          = [];

    public $isPloy                  = false;
    public $isExtra                 = false;

    public $extra_where             = [];
    public $message                 = [];

    public function __construct(string $table,string $prefix = '')
    {
        $this->table     = $table;
        $this->prefix    = $prefix;
        return $this->structureTable();

    }

    public function has(){
        return !empty($this->table);
    }

    public function hasField(string $field){
        return in_array($field,$this->field_full)?true:false;
    }

    public function where(array $where = []){
        $this->table_where = $where;
        return $this;
    }
    public function getWhere(){
        return $this->table_where;
    }


    public function setPrefix(string $prefix = ''){
        !empty($prefix) && $this->prefix = $prefix;
    }
    public function setMaster(){
        $this->ploy_table_master = true;
    }

    public function setExtraField(string $field){
        $this->extra_field = $field;
    }
    public function setExtraAslias(string $aslias = ''){
        $this->extra_alias = $aslias;
    }
    public function getExtraField(){
        return $this->extra_field;
    }
    public function getExtraAslias(){
        return $this->extra_alias;
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
    public function getUnique(){
        return $this->field_unique;
    }

    /**
     * @return array
     */
    public function getFieldNotNull(): array
    {
        return $this->field_not_null;
    }

    public function getFieldPrimary(){
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

    public function ployCondition(array $condition = []){

        $field = $this->field_full;

        $this->conditionCheckUp($condition,$field);
        return $this->conditionPrefix($condition);
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
    public function setfrontAlias(string $alias){
        $this->front_alias = $alias;
    }
    public function getFrontAlias(){
        return $this->front_alias;
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

    public function verfiyData(array $array = [],array $param = []){

        if(!isset($array[$this->primary_field])){
            return $this->verfiyInsert($array) && $this->verfiyField($array,$param);
        }else{
            return $this->verfiyField($array);
        }

    }

    /**
     * @param array $array
     * @return bool
     * 验证插入数据是否完整[唯一性]
     */
    public function verfiyInsert(array $array = []){
        $fields = array_keys($array);
        foreach ($this->field_not_null as $key=>$item){
            if(!in_array($item,$fields)){
                $this->error_message = '缺少字段'.(app()->isDebug()?":$item":'');
                return false;
            }
        }

        if (empty($fields_arr = $this->field_unique))return true;


        $this->array_merge_one($fields_arr,$array,true);

        $unq = Db::table($this->getTable());

        foreach ($fields_arr as $key=>$item){
            $unq->whereOr([$key=>$item]);
        }
        if($unq->count()){
            $filed = array_flip($this->field_unique);
            foreach ($filed as $key=>$val){
                $filed[$key] = $key;
            }
            $filed = array_merge($filed,$this->message);
            $this->error_message = "字段值重复:".(app()->isDebug()?(":".implode(',',$this->field_unique)):'');
            $this->error_message = implode(',',$filed).'不可重复';
            return false;
        }

        return $this->verfiyField($array);
    }

    /**
     * @param array $array
     * @return bool
     * 验证字段是否合规
     */
    public function verfiyField(array $array = [] ,&$param = []){
        $field_flip = array_flip($this->field_full);
        $output_field = $this->output_field = $array;



        foreach ($array as $key=>$item){
            if(!isset($field_flip[$key]))continue;
            $len = $this->field_length[$field_flip[$key]];

            switch ( $type = self::FIELD_TYPE[$this->field_type[$field_flip[$key]]]){
                case 'string':
                    if(strlen($item)>$len){
                        $this->error_message = '字段数据超出范围'.(app()->isDebug()?(':'.$key.'长度为['.$len.']'):'');
                        $this->error_message = "字段【{$key}】长度范围为{$len}";
                        if(isset($this->message[$key])) $this->error_message = $this->message[$key]."长度不可超过$len";
                        return false;
                    }
                    break;
                case 'numeric':
                    if(!is_numeric($item)){
                        $this->error_message = "字段【{$key}】数据类为数字";
                        if(isset($this->message[$key])) $this->error_message = $this->message[$key];
//                        $this->error_message = '字段数据类错误'.(app()->isDebug()?(':'.$key.'=>'.$type):'');
                        return false;
                    }
                    break;
                case 'date':
                    if(is_numeric($item) && !(ctype_digit($item) && $item <= 2147483647) ||!strtotime($item)){

//                        $this->error_message = '字段数据类错误'.(app()->isDebug()?(':'.$key.'=>'.$type):'');
                        $this->error_message = "字段【{$key}】数据类为时间格式";
                        if(isset($this->message[$key])) $this->error_message = $this->message[$key];
                        return false;
                    }
                    if(ctype_digit($item) && $item <= 2147483647){
                        $this->output_field[$key] = date('Y-m-d H:i:s', $item);
                    }
                default:
                    break;
            }

        }
        $this->error_message = '字段数据类型错误【未识别的到】';
        return true;
    }

    public function ouputField(){
        return $this->output_field;
    }


    public function error(){
        return $this->error_message;
    }


    /**
     * 检查字段
     */
    public function checkoutField(array $array,bool $is_full = false){

        $tmp = [];

        foreach ($array as $key => $item){
            if(in_array($key,$is_full?$this->field_full:$this->field_param)){
                $tmp[$key] = $item;
            }
        }
        return $tmp;
    }

    /**
     * 解析数据表
     */
    public function structureTable(){

        if(!empty($this->table))
        foreach (Db::query('SHOW FULL COLUMNS FROM '.$this->getTable()) as $k => $v){
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

        return true;
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

    /**
     * @param array $array
     * @param array $template
     * 清理多余参数
     */
    private function conditionCheckUp(array &$array,array $template = []){
        foreach ($array as $key =>&$item){
            if(is_numeric($key) && is_array($item)){
                if(isset($item[0]) && is_string($item[0]) && !in_array($item[0],$template)){
                    unset($array[$key]);
                }
            }
            if(is_string($key) && !in_array($key,$template)){
                unset($array[$key]);
            }
        }
    }
    private function conditionPrefix($array){
        $tmp = [];
        foreach ($array as $key =>&$item){
            if(is_array($item) && isset($item[0])){
                $item[0] = "{$this->getTable()}.{$item[0]}";
                $tmp[$key] = $item;
            }
            if(is_string($key)){
                $tmp["{$this->getTable()}.{$key}"] = $item;
            }

        }

        return $tmp;
    }

    protected function array_merge_one(array &$one, array $other,bool $del_empty_flag = false){

        foreach ($one as $k=>$v){
            if(is_numeric($k)){
                $one[$v] = '';
                unset($one[$k]);
            }
            isset($other[$v]) && $one[$v] = $other[$v];
            isset($other[$k]) && $one[$k] = $other[$k];

        }

        if($del_empty_flag)
            foreach ($one as $k=>$v){
                if(is_array($one[$k]))continue;
                if(strlen($one[$k])<=0)unset($one[$k]);
            }
    }

    /**
     * @param array $extra_where
     */
    public function setExtraWhere(array $extra_where = []): Table
    {
        $this->extra_where = $extra_where;
        return $this;
    }

    /**
     * @param array $message
     */
    public function setMessage(array $message): void
    {
        $this->message = $message;
    }


    



}