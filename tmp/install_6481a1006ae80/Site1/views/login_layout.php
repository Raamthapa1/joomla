<?php
$app = JFactory::getApplication();
$themeOptions = $app->getTemplate(true)->params;
$fileName = $themeOptions->get('login', '');
if ($fileName) {
    include_once dirname(__FILE__) . '/login_layout_' . $fileName . '.php';
}