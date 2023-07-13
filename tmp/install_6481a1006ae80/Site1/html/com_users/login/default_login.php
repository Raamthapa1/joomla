<?php
defined('_JEXEC') or die;

$themePath = dirname(dirname(dirname(dirname(__FILE__))));
require_once $themePath . DIRECTORY_SEPARATOR . 'functions.php';


$app = JFactory::getApplication();
$themeOptions = $app->getTemplate(true)->params;
$fileName = $themeOptions->get('login', '');
if ($fileName) {
    include_once dirname(__FILE__) . '/' . $fileName . '.php';
}