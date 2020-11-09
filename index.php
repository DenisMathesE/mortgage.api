<?php

require_once("/home/repo/mortgage/config.php");
require_once("/home/repo/mortgage/mysql.php");
require_once("classes/auth.php");
require_once("classes/validator.php");
require_once("classes/borrower.php");
require_once("classes/order.php");
require_once("classes/action.php");
require_once("classes/platform_client.php");
require_once("classes/platform_user.php");
require_once("classes/feed.php");
require_once("functions.php");
include_once("/home/repo/php-amqplib/init.php");

$db_mysql = new db;
$db_mysql->connect(1);

$auth = new Auth( $db_mysql );

$am = new ampq();

//if ( !$auth->isAuth() ) {
//    header('HTTP/1.0 403 Forbidden');
//    $auth->end();
//    exit();
//}

//echo $auth->key." - key_\n";
//echo $auth->isAuth()." - isAuth_\n";

if ( !$auth->isAuth() ) {
    header("HTTP/1.1 403 Forbidden");
    exit();
}

$action = new Action($db_mysql, $auth);
$action->am = $am;
$action->start();

$action->end();