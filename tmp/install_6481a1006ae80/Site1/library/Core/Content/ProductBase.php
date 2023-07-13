<?php
defined('_JEXEC') or die;

abstract class CoreContentProductBase
{
    protected $_component;
    protected $_componentParams;
    protected $_product;

    public $title;
    public $titleLink;
    public $shortDesc;
    public $regularPrice = '';
    public $oldPrice = '';

    protected function __construct($component, $componentParams, $product)
    {
        $this->_component = $component;
        $this->_componentParams = $componentParams;
        $this->_product = $product;

        $this->title = $this->_product->product_name;

        $link = 'index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=' . $this->_product->virtuemart_product_id;
        $this->titleLink = JRoute::_($link, FALSE);

        $this->shortDesc = $this->_product->product_s_desc;

        $currency = CurrencyDisplay::getInstance();
        $regularPrice = $currency->createPriceDiv('salesPrice', '', $this->_product->prices, true, false, 1.0, true, true);
        $oldPrice = $currency->createPriceDiv('basePrice', '', $this->_product->prices, true, false, 1.0, true, true);
        if (!$regularPrice) {
            $regularPrice = $oldPrice;
        }
        $this->regularPrice = $regularPrice;
        $this->oldPrice = $oldPrice !== $regularPrice ? $oldPrice : '';
    }

    public function getImage() {
        $imageUrl = '';
        if (!empty($this->_product->images)) {
            $image = $this->_product->images[0];
            $width = VmConfig::get('img_width_full', 0);
            $height = VmConfig::get('img_height_full', 0);
            if(!empty($width) or !empty($height)){
                $imageHtml = $image->displayMediaThumb("",true,"rel='vm-additional-images'", true, true, false, $width, $height);
            } else {
                $imageHtml = $image->displayMediaFull("",true,"rel='vm-additional-images'");
            }
            preg_match('/src=[\'"]([\s\S]+?)[\'"]/', $imageHtml, $matches);
            if (count($matches) > 1) {
                $imageUrl = $matches[1];
            }
        }
        return $imageUrl;
    }

    public function getQuantityProps() {
        $props = array('notify' => '', 'label' => '', 'html' => '');
        if ($this->_product->show_notify) {
            $notifyUrl = JRoute::_('index.php?option=com_virtuemart&view=productdetails&layout=notify&virtuemart_product_id=' . $this->_product->virtuemart_product_id);
            $props['notify'] = '<a class="notify u-btn" href="' . $notifyUrl . '" >' .  vmText::_('COM_VIRTUEMART_CART_NOTIFY') . '</a>';
        }

        $tmpPrice = (float) $this->_product->prices['costPrice'];
        $wrongAmountText = vmText::_('COM_VIRTUEMART_WRONG_AMOUNT_ADDED');
        if (!(VmConfig::get('askprice', true) && empty($tmpPrice)) && $this->_product->orderable) {
            $init = 1;
            if (!empty($this->_product->min_order_level) && $init < $this->_product->min_order_level) {
                $init = $this->_product->min_order_level;
            }

            $step = 1;
            if (!empty($this->_product->step_order_level)) {
                $step = $this->_product->step_order_level;
                if (!empty($init)) {
                    if ($init < $step) {
                        $init = $step;
                    } else {
                        $init = ceil($init / $step) * $step;
                    }
                }
                if (empty($this->_product->min_order_level)) {
                    $init = $step;
                }
            }

            $maxOrder = '';
            if (!empty($this->_product->max_order_level)) {
                $maxOrder = ' max="' . $this->_product->max_order_level . '" ';
            }

            $props['html'] = <<<HTML
            <input type="text" class="quantity-input js-recalculate" name="quantity[]" data-errStr="$wrongAmountText"
                value="$init" data-init="$init" data-step="$step" $maxOrder />
HTML;
            $props['label'] = vmText::_('COM_VIRTUEMART_CART_QUANTITY');
        }
        return $props;
    }

    public function getButtonProps($setDynamicQuantity = false) {
        $props = array('text' => '', 'link' => $this->titleLink, 'html' => '');
        if (!VmConfig::get('use_as_catalog', 0)) {
            $buttonHtml = shopFunctionsF::renderVmSubLayout('addtocart', array('product'=> $this->_product));
            if (strpos($buttonHtml, 'addtocart-button-disabled') !== false) {
                $props['text'] = vmText::_('COM_VIRTUEMART_ADDTOCART_CHOOSE_VARIANT');
            } else {
                $props['text'] = vmText::_('COM_VIRTUEMART_CART_ADD_TO');
                $productId = $this->_product->virtuemart_product_id;
                $productName = $this->_product->product_name;
                $formAction = JRoute::_('index.php?option=com_virtuemart', false);
                $quantityHtml = '<input type="hidden" class="quantity-input js-recalculate" name="quantity[]" value="1">';
                if ($setDynamicQuantity) {
                    $quantityHtml = '[[dynamic_quantity]]';
                }
                $props['html'] = <<<HTML
<form method="post" class="form-product js-recalculate" action="$formAction" autocomplete="off" >
			[[button]]
			<input type="hidden" name="option" value="com_virtuemart"/>
			<input type="hidden" name="view" value="cart"/>
			<input type="hidden" name="virtuemart_product_id[]" value="$productId"/>
			<input type="hidden" name="pname" value="$productName"/>
			<input type="hidden" name="pid" value="$productId"/>
			$quantityHtml
            <noscript><input type="hidden" name="task" value="add"/></noscript>
HTML;
                $itemId = vRequest::getInt('Itemid', false);
                if ($itemId) {
                    $props['html'] .= '<input type="hidden" name="Itemid" value="'.$itemId.'"/>';
                }

                $props['html'] .= '</form>';
            }
        }
        return $props;
    }

    public function getGallery() {
        $galleryImages = array();

        if (empty($this->_product->images)) {
            return $galleryImages;
        }

        if (count($this->_product->images) === 1) {
            return array($this->getImage());
        }

        $start_image = VmConfig::get('add_img_main', 1) ? 0 : 1;
        for ($i = $start_image; $i < count($this->_product->images); $i++) {
            $image = $this->_product->images[$i];
            if(VmConfig::get('add_img_main', 1)) {
                $imageHtml = $image->displayMediaThumb('class="product-image" style="cursor: pointer" data-descr="' . $image->file_description . '"', false, '', true, '');
            } else {
                if(VmConfig::get('add_thumb_use_descr', false)) {
                    $image->file_meta = $image->file_description;
                }
                $imageHtml = $image->displayMediaThumb('', true, "rel='vm-additional-images'", true, '');
            }
            preg_match('/src=[\'"]([\s\S]+?)[\'"]/', $imageHtml, $matches);
            if (count($matches) > 1) {
                array_push($galleryImages, $matches[1]);
            }
        }

        return $galleryImages;
    }

    public function getTabs() {
        $tabs = array();

        $descTabTitle = vmText::_('COM_VIRTUEMART_PRODUCT_DESC_TITLE');
        $descTabContent = $this->_product->product_desc;
        $descTabContent .= shopFunctionsF::renderVmSubLayout('customfields',array('product'=>$this->_product,'position'=>'normal'));
        $descTab = array('title' => $descTabTitle, 'content' => $descTabContent, 'guid' => strtolower(substr(createGuid(), 0, 4)));
        array_push($tabs, $descTab);

        $revTabContent = $this->_component->loadTemplate('reviews');
        if ($revTabContent) {
            $revTabTitle = vmText::_('COM_VIRTUEMART_REVIEWS');
            $revTab = array('title' => $revTabTitle, 'content' => $revTabContent, 'guid' => strtolower(substr(createGuid(), 0, 4)));
            array_push($tabs, $revTab);
        }

        return $tabs;
    }

    public function getVariations($position = 'addtocart') {
        $variations = array();
        if (!empty($this->_product->customfieldsSorted[$position])) {
            $customfields = $this->_product->customfieldsSorted[$position];
            foreach ($customfields as $customfield) {
                if (property_exists($customfield, 'display') && strpos($customfield->display, '<select ') !== false) {
                    preg_match_all('/<select([\s\S]+?)>([\s\S]+?)<\/select>/', $customfield->display, $selectMatches, PREG_SET_ORDER);
                    foreach ($selectMatches as $index => $selectMatch) {
                        $selectHtml = $selectMatch[1];

                        $s_classes = '';
                        preg_match('/class="([\s\S]+?)"/', $selectHtml, $classMatch);
                        if (count($classMatch) > 0) {
                            $selectHtml = preg_replace('/class="[\s\S]+?"/', '', $selectHtml);
                            $s_classes = str_replace('vm-chzn-select', '', $classMatch[1]);
                            $s_classes = str_replace('no-vm-bind', '', $s_classes);
                        }

                        $attributesMatch = explode(' ', $selectHtml);
                        $attributes = array();
                        foreach ($attributesMatch as $attr) {
                            if (trim($attr) && !preg_match('/^(id|class|style)/', $attr) && strpos($attr, '=') !== false) {
                                array_push($attributes, $attr);
                            }
                        }

                        preg_match_all('/<option[\s\S]+?value=[\'"]([\s\S]*?)[\'"][\s\S]*?>([\s\S]+?)<\/option>/', $selectMatch[2], $matches);
                        $optionTags = $matches[0];
                        $values = $matches[1];
                        $text = $matches[2];
                        $options = array();
                        foreach ($values as $key => $value) {
                            $option = array(
                                'text' => $text[$key],
                                'value' => $value,
                            );
                            $option['selected'] = strpos($optionTags[$key], 'selected') !== false ? true : false;
                            array_push($options, $option);
                        }

                        $variation = array(
                            'title' => $index == 0 ? $customfield->custom_title : '',
                            'options' => $options,
                            's_attributes' => implode(' ', $attributes),
                            's_classes' => $s_classes,
                        );
                        array_push($variations, $variation);
                    }
                }
            }
        }
        return $variations;
    }

    public function includeScripts($templateType = '') {
        vmJsApi::jPrice();
        vmJsApi::cssSite();
        vmJsApi::jDynUpdate();

        if($templateType === 'productdetails' && VmConfig::get('jdynupdate', true)) {
            $j = <<<SCRIPT
Virtuemart.container = jQuery('.productdetails-view');
Virtuemart.containerSelector = '.productdetails-view';
SCRIPT;
            vmJsApi::addJScript('ajaxContent', $j);

            $j = <<<SCRIPT
jQuery(document).ready(function($) {
	Virtuemart.stopVmLoading();
	var msg = '';
	$('a[data-dynamic-update=\"1\"]').off('click', Virtuemart.startVmLoading).on('click', {msg:msg}, Virtuemart.startVmLoading);
	$('[data-dynamic-update=\"1\"]').off('change', Virtuemart.startVmLoading).on('change', {msg:msg}, Virtuemart.startVmLoading);
});
SCRIPT;
            vmJsApi::addJScript('vmPreloader', $j);
        }
        if($templateType === 'products') {
            $j = <<<SCRIPT
jQuery('select').on('change', function() {
    window.location.href = this.value;
});
SCRIPT;
            vmJsApi::addJScript('vmOrderByList', $j);
        }

        echo vmJsApi::writeJS();
    }
}
