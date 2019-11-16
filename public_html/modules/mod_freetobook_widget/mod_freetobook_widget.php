<?php
//don't allow other scripts to grab and execute our file
defined('_JEXEC') or die('Direct Access to this location is not allowed.');

require_once __DIR__ . '/helper.php';

$widget=new FreetobookWidget($params);
$widget->display();

?>

