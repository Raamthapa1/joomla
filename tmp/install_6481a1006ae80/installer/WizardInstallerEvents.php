<?php

defined('JPATH_BASE') or die();

/**
 * Class WizardInstallerEvents
 */
class WizardInstallerEvents extends JPlugin
{
    /**
     * Status type
     */
    const STATUS_ERROR     = 'error';

    /**
     * Status type
     */
    const STATUS_INSTALLED = 'installed';

    /**
     * Status type
     */
    const STATUS_UPDATED   = 'updated';

    /**
     * Messages list
     *
     * @var array
     */
    protected static $messages = array();

    /**
     * Top level installer
     *
     * @var
     */
    protected $toplevel_installer;

    /**
     * Set top installer
     *
     * @param object $installer Installer object
     */
    public function setTopInstaller($installer)
    {
        $this->toplevel_installer = $installer;
    }

    /**
     * WizardInstallerEvents constructor.
     *
     * @param object $subject Subject
     * @param array  $config  Config
     */
    public function __construct(&$subject, $config = array())
    {
        parent::__construct($subject, $config);

        jimport('joomla.filesystem.file');
        jimport('joomla.filesystem.folder');

        $wizard_css_file = dirname(__FILE__) . '/../style.css';
        $wizard_js_file = dirname(__FILE__) . '/../stepzation.js';
        $wizard_html_file = dirname(__FILE__) . '/../wizard.html';

        $tmp_path          = JPATH_ROOT . '/tmp';

        if (JFolder::exists($tmp_path)) {
            // Copy install.css to tmp dir for inclusion
            JFile::copy($wizard_css_file, $tmp_path . '/style.css');
            JFile::copy($wizard_js_file, $tmp_path . '/stepzation.js');
            JFile::copy($wizard_html_file, $tmp_path . '/wizard.html');
        }
    }

    /**
     * Add message to list
     *
     * @param array  $package Package
     * @param string $status  Status value
     * @param string $message Text message
     */
    public static function addMessage($package, $status, $message = '')
    {
        self::$messages[] = call_user_func_array(array('WizardInstallerEvents', $status), array($package, $message));
    }

    /**
     * Get error html content
     *
     * @param array  $package Package
     * @param string $msg     Message text
     *
     * @return string
     */
    public static function error($package, $msg)
    {
        ob_start();
        ?>
        <ul>
            <li class="wizardinstall-failure">
                <span class="wizardinstall-icon"><span></span></span>
                <span class="wizardinstall-row">The <?php echo ucfirst(trim($package['name'] . ' installation failed'));?></span>
                <span class="wizardinstall-errormsg">
            <?php echo $msg; ?>
        </span>
            </li>
        </ul>
        <?php
        $out = ob_get_clean();

        return $out;
    }

    /**
     * Get installed html page
     *
     * @param array $package Package
     *
     * @return string
     */
    public static function installed($package)
    {
        ob_start();
        ?>
        <span class="wizardinstall-row">The <?php echo ucfirst(trim($package['name'] . ' installed successfully'));?></span>
        <div style="margin: 20px 0 0 0;">Click the <strong>"Set Default"</strong> button to activate the template.</div>
        <?php
        $out = ob_get_clean();

        return $out;
    }

    /**
     * Get updated html page
     *
     * @param array $package Package
     *
     * @return string
     */
    public static function updated($package)
    {
        ob_start();
        ?>
        <span class="wizardinstall-row">The <?php echo ucfirst(trim($package['name'] . ' updated successfully'));?></span>
        <div style="margin: 20px 0 0 0;">Click the <strong>"Set Default"</strong> button to activate the template.</div>
        <?php
        $out = ob_get_clean();

        return $out;
    }

    /**
     * On extension after install
     *
     * @param object $installer Installer object
     * @param int    $eid       Id
     */
    public function onExtensionAfterInstall($installer, $eid)
    {
        $lang = JFactory::getLanguage();
        $lang->load('install_override', dirname(__FILE__), $lang->getTag(), true);
        $this->toplevel_installer->set('extension_message', $this->getMessages());
    }

    /**
     * On extension after update
     *
     * @param object $installer Installer object
     * @param int    $eid       Id
     */
    public function onExtensionAfterUpdate($installer, $eid)
    {
        $lang = JFactory::getLanguage();
        $lang->load('install_override', dirname(__FILE__), $lang->getTag(), true);
        $this->toplevel_installer->set('extension_message', $this->getMessages());
    }

    protected static function loadWizardHtml()
    {
        jimport('joomla.filesystem.file');
        jimport('joomla.filesystem.folder');
        $buffer = '';

        // Drop out Style
        if (file_exists(JPATH_ROOT . '/tmp/wizard.html')) {
            $buffer .= JFile::read(JPATH_ROOT . '/tmp/wizard.html');
        }

        return $buffer;
    }

    /**
     * Get messages html content
     *
     * @return string
     */
    protected function getMessages()
    {
        $themeName = 'Site1';
        $pluginName = 'nicepage';

        $basename = basename(dirname(dirname(dirname(__FILE__))));
        $installerPath = JURI::root(true) . '/' . $basename . '/install_extensions/wizard.php';
        $activateTheme = $installerPath . '?action=activate_theme&template=' . strtolower($themeName);
        $installPlg = $installerPath . '?action=install_plg&plugin=' . $pluginName;
        $checkPlg = $installerPath . '?action=check_plg&plugin=' . $pluginName;
        $importContent = $installerPath . '?action=import&plugin=' . $pluginName . '&template=' . strtolower($themeName);

        $createPageUrl = JURI::root(true) . '/administrator/index.php?option=com_' . $pluginName . '&task=' . $pluginName . '.start';
        $liveSiteUrl = JURI::root(true);

        $buffer = '';
        $buffer .= '<div id="wizardinstall">';
        $buffer .= implode('', self::$messages);
        $buffer .= '</div>';
        if (preg_match('/successful/', $buffer)) {
            JHtml::_('jquery.framework');
            $wizardHtml = self::loadWizardHtml();
            $buffer = str_replace(
                array('[buffer]','[pluginName]', '[activateTheme]', '[installPlg]', '[checkPlg]', '[importContent]', '[createPageUrl]', '[liveSiteUrl]'),
                array($buffer, $pluginName, $activateTheme, $installPlg, $checkPlg, $importContent, $createPageUrl, $liveSiteUrl),
                $wizardHtml
            );
        }

        return $buffer;
    }
}
