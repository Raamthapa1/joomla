<?php
defined('_JEXEC') or die;

$themePath = dirname(dirname(dirname(dirname(__FILE__))));
require_once $themePath . DIRECTORY_SEPARATOR . 'functions.php';


Core::load("Core_Content");

$component = new CoreContent($this, $this->params);
$allItems = array_merge($this->lead_items, $this->intro_items);
$all = count($allItems);

$app = JFactory::getApplication();
$themeOptions = $app->getTemplate(true)->params;
$fileName = $themeOptions->get('blog', '');
if ($fileName) {
    include_once dirname(dirname(__FILE__)) . '/category/' . $fileName . '.php';
}