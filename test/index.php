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
$controller = new userController();

$controller->implant(new user());


$controller->ploy('group','group_id','app_');

//$controller->getModel()->displayField([]);
//$controller->getModel()->filterField(['id']);

//$controller->setParam(['id'=>1]);

//var_export($controller->view());

//print_r($controller->show());
//$controller->show();






