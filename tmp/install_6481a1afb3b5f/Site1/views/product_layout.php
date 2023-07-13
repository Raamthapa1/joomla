<?php
$app = JFactory::getApplication();
$themeOptions = $app->getTemplate(true)->params;
$fileName = $themeOptions->get('product', '');
if ($fileName) {
    include_once dirname(__FILE__) . '/product_layout_' . $fileName . '.php';
}