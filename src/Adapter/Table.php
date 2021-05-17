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
        'tinytext'  =>'string',
        'blob'      =>'string',
        'text'      =>'string',
        'mediumblob'=>'string',
        'mediumtext'=>'string',
        'longblob'  =>'string',
        'longtext'  =>'string',

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
    protected $front_join_type           = 'LEFT';

    protected $ploy_tables;
    protected $ploy_table_master    = false;
    protected $error_message        = '';

    public function __construct(string $table,string $prefix = '')
    {
        $this->table     = $table;
        $this->prefix    = $prefix;
        $this->structureTable();

    }

    public function has(){
        return !empty($this->table);
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
    public function getUnique(){
        return $this->field_unique;
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

    public function verfiyData(array $array = []){

        if(!isset($array[$this->primary_field])){
            return $this->verfiyInsert($array) && $this->verfiyField($array);
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
        if(empty($fields_arr = $this->field_unique))return true;
        $this->array_merge_one($fields_arr,$array,true);
        $unq = Db::table($this->getTable());

        foreach ($fields_arr as $key=>$item){
            $unq->whereOr([$key=>$item]);
        }
        if($unq->count()){
            $this->error_message = "字段数据不可重复".(app()->isDebug()?(":".implode(',',$this->field_unique)):'');
            return false;
        }

        return $this->verfiyField($array);
    }

    /**
     * @param array $array
     * @return bool
     * 验证字段是否合规
     */
    public function verfiyField(array $array = []){
        $field_flip = array_flip($this->field_full);

        foreach ($array as $key=>$item){
            $len = $this->field_length[$field_flip[$key]];
            switch ( $type = self::FIELD_TYPE[$this->field_type[$field_flip[$key]]]){
                case 'string':
                    if(!is_string($item)) {
                        $this->error_message = '字段数据类错误'.(app()->isDebug()?(':'.$key.'=>'.$type):'');
                        return false;
                    }
                    if(strlen($item)>$len){
                        $this->error_message = '字段数据不在范围'.(app()->isDebug()?(':'.$key.'长度为['.$len.']'):'');
                        return false;
                    }
                    break;
                case 'numeric':
                    if(!is_numeric($item)){
                        $this->error_message = '字段数据类错误'.(app()->isDebug()?(':'.$key.'=>'.$type):'');
                        return false;
                    }
                    break;
                case 'date':
                    if(date('Y-m-d H:i:s',strtotime($data))!=$data){
                        $this->error_message = '字段数据类错误'.(app()->isDebug()?(':'.$key.'=>'.$type):'');
                        return false;
                    }
                default:
                    break;
            }


        }
        $this->error_message = '字段数据类型错误';
        return true;
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




}