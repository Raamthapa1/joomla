<?php
$app = JFactory::getApplication();
$themeOptions = $app->getTemplate(true)->params;
$fileName = $themeOptions->get('post', '');
if ($fileName) {
    include_once dirname(__FILE__) . '/post_layout_' . $fileName . '.php';
}