<?php
defined('_JEXEC') or die;

/**
 * Contains page rendering helpers.
 */
class CorePage
{

    public $page;

    public function __construct($page)
    {
        $this->page = $page;
    }

    public function isFrontEditing()
    {
        $option = JRequest::getCmd('option');
        $controller = JRequest::getCmd('controller');
        if ($option == 'com_config' && $controller == 'config.display.modules') {
            return true;
        }

        $view = JRequest::getCmd('view');
        $layout = JRequest::getCmd('layout');
        $aid = JRequest::getCmd('a_id');
        if ($aid && $view == 'form' && $layout == 'edit') {
            return true;
        }

        return false;
    }

    public function renderHeader($indexDir, $params = null) {
        $lang = checkAndGetLanguage();
        if ($lang) {
            $indexDir = $indexDir . '/headerTranslations/' . $lang;
        }
        ob_start();
        include_once  "$indexDir/header.php";
        $header = ob_get_clean();
        if ($params) {
            $dataIds = json_decode($params->get('dataIds', ''));
            if ($dataIds) {
                foreach ($dataIds as $key => $value) {
                    $header = str_replace('[page_' . $key . ']', 'index.php?option=com_content&amp;view=article&amp;id=' . $value, $header);
                }
            }
        }
        echo $header;
    }

    public function renderFooter($indexDir, $params = null) {
        $lang = checkAndGetLanguage();
        if ($lang) {
            $indexDir = $indexDir . '/footerTranslations/' . $lang;
        }
        ob_start();
        include_once  "$indexDir/footer.php";
        $footer = ob_get_clean();
        if ($params) {
            $dataIds = json_decode($params->get('dataIds', ''), true);
            if ($dataIds) {
                foreach ($dataIds as $key => $value) {
                    $footer = str_replace('[page_' . $key . ']', 'index.php?option=com_content&amp;view=article&amp;id=' . $value, $footer);
                }
            }
        }
        echo $footer;
    }

    public function fixVmStyles() {
        $document = JFactory::getDocument();
        $styleSheets = $document->_styleSheets;
        $newStyleSheets = array();
        foreach ($styleSheets as $styleFilePath => $styleSheet) {
            if (preg_match('/vm\-(l|r)tr/', $styleFilePath)) {
                continue;
            }
            $newStyleSheets[$styleFilePath] = $styleSheet;
        }
        $document->_styleSheets = $newStyleSheets;
    }

    public function fixVmScripts($content)
    {
        $document = JFactory::getDocument();
        $scripts = $document->_scripts;
        $index = 0;
        foreach ($scripts as $filePath => $script) {
            $index++;
            if (preg_match('/com\_virtuemart.+vmprices\.js/', $filePath)) {
                $before = array_slice($scripts, 0, $index - 1);
                $after = array_slice($scripts, $index);
                $newFilePath = $document->baseurl . '/templates/' . $document->template . '/scripts/vmprices.js';
                $new = array($newFilePath => $script);
                $scripts = array_merge($before, $new, $after);
            }
        }
        $document->_scripts = $scripts;

        $content = str_replace('Virtuemart.product($("form.product"));', 'Virtuemart.product($(".product"));', $content);
        return $content;
    }

    public function renderLayout()
    {
        if ($this->page->getType() != 'html')
            return;
        $option = JRequest::getCmd('option');
        $view = JRequest::getCmd('view');
        $task = JRequest::getCmd('task');

        $content = $this->page->getBuffer('component');

        if (!$this->isFrontEditing()) {
            $content = str_replace('hasTooltip', '', $content);
        }

        

        $this->page->setBuffer($content, 'component');

        if ($option == 'com_content' && $view == 'category') {
            $currentLayout = JRequest::getCmd('layout', '');
            $compParams = JComponentHelper::getParams('com_content');
            $commonLayout = $compParams->get('category_layout');
            if ($currentLayout || strpos($commonLayout, 'blog') !== false) {
                $view = 'blog';
            }
        }

        switch ($option) {
            case "com_users":
                switch ($view) {
                    case "login":
                        $this->renderLayoutByType('login');
                        return;
                }
                break;
            case "com_content":
                switch ($view) {
                    case "article":
                        $this->renderLayoutByType('post');
                        return;
                    case "blog":
                    case "featured":
                    case "archive":
                        $this->renderLayoutByType('blog');
                        return;
                }
                break;
            
        }
        $this->renderDefaultLayout();
    }

    public function renderComponent()
    {
        echo CoreStatements::message();
        echo CoreStatements::component();
    }

    public static  $positionPlaceholders = array();
    public static $foundPositionPlaceholders = array();

    public static function parsePositionPlaceholders($matches)
    {
        $placeholder = array_search($matches[0], CorePage::$foundPositionPlaceholders);
        if (!$placeholder) {
            CorePage::$positionPlaceholders[] = str_replace('/>', ' positionNumber="' . (count(CorePage::$positionPlaceholders) + 1) . '" />', $matches[0]);
            $count = preg_match('/count=[\'"](\d+)[\'"]/', $matches[0], $countMatches) ? (int) $countMatches[1] : 1;
            $placeholder = '[[position_' . count(CorePage::$positionPlaceholders) . ($count > 1 ? "_$count" : '') . ']]';
            CorePage::$foundPositionPlaceholders[$placeholder] = $matches[0];
        }
        return $placeholder;
    }

    public function renderLayoutByType($type, $isVm = false) {
        $this->beforeRenderLayout();
        if ($isVm) {
            $this->fixVmStyles();
        }
        include_once dirname(dirname(dirname(__FILE__))) . '/views/' . $type . '_layout.php';
        $this->afterRenderLayout();
    }

    public function beforeRenderLayout()
    {
        $content = $this->page->getBuffer('component');
        $content = preg_replace_callback(
            '/<jdoc[\s\S]+?\/>/',
            array('CorePage', 'parsePositionPlaceholders'),
            $content
        );
        $this->page->setBuffer($content, 'component');
    }

    public function afterRenderLayout()
    {
        echo implode('', CorePage::$positionPlaceholders);
    }

    public function renderDefaultLayout()
    {
        ob_start();
        include_once dirname(dirname(dirname(__FILE__))) . '/html/com_content/article/default_styles.php';
        JFactory::getDocument()->addCustomTag(ob_get_clean());
        include_once dirname(dirname(dirname(__FILE__))) . '/views/default_layout.php';
    }

}
