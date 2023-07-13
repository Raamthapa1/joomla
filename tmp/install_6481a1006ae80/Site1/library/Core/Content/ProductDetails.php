<?php
defined('_JEXEC') or die;

Core::load("Core_Content_ProductBase");

class CoreContentProductDetails extends CoreContentProductBase
{
    public function __construct($component, $componentParams, $product)
    {
        parent::__construct($component, $componentParams, $product);
    }
}