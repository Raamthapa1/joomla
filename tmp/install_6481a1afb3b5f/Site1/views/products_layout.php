<?php
$app = JFactory::getApplication();
$themeOptions = $app->getTemplate(true)->params;
$fileName = $themeOptions->get('products', '');
if ($fileName) {
    include_once dirname(__FILE__) . '/products_layout_' . $fileName . '.php';
}