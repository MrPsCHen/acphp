<?php


use think\facade\Db;

require_once "./vendor/autoload.php";

Db::setConfig(include "./test/database.php");

class user extends \Adapter\Model {
    protected $prefix = 'app_';
}

class group extends \Adapter\Model{
    protected $prefix = 'app_';
}

class userController extends \Adapter\Controller {

}
userController::setModelNamespace("\\app\\name\\");

$controller = new userController(new user());



$controller->ploy('group','group_id','app_');
$controller->ploy('label','label_id','app_');
//$controller->choseTable('app_label')->alias(['name'=>'label']);

$controller->getModel()->extra('app_label','id','labels');
//$controller->getModel()->filterField(['id']);

//$controller->setParam(['id'=>1]);

//var_export($controller->view());

//print_r($controller->show());
//print_r($controller->show());
print_r($controller->show());





