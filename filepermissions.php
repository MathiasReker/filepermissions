<?php
/**
 *  @author    Mathias Reker
 *  @copyright 2019 Mathias Reker
 *  @license   https://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class FilePermissions extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'filepermissions';
        $this->tab = 'administration';
        $this->version = '1.0.2';
        $this->author = 'Mathias Reker';
        $this->need_instance = 1;
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('File permissions');
        $this->description = $this->l('This tool will change directory permissions to 755 and file permissions to 644');
        $this->ps_versions_compliancy = [
            'min' => '1.7',
            'max' => _PS_VERSION_,
        ];
    }

    public function install()
    {
        return parent::install();
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    protected function chmodFileFolder($dir)
    {
        $perms = [];
        $perms['file'] = 0644;
        $perms['folder'] = 0755;

        $dh = @opendir($dir);

        if ($dh) {
            while (false !== ($file = readdir($dh))) {
                if ('.' != $file && '..' != $file) {
                    $fullpath = $dir . '/' . $file;
                    if (!is_dir($fullpath)) {
                        if (chmod($fullpath, $perms['file'])) {
                            $this->ok_file .= '<span style="font-weight:bold;">File</span> '
                            . $fullpath . ' permissions changed to ' . decoct($perms['file']) . '<br>';
                        } else {
                            $this->nok_file .= '<span style="font-weight:bold;">Failed</span> to set file permissions on '
                            . $fullpath . '<br>';
                        }
                    } else {
                        if (chmod($fullpath, $perms['folder'])) {
                            $this->ok_dir .= '<span style="font-weight:bold;">Directory</span> '
                            . $fullpath . ' permissions changed to ' . decoct($perms['folder']) . '<br>';
                            $this->chmodFileFolder($fullpath);
                        } else {
                            $this->nok_dir .= '<span style="font-weight:bold;">Failed</span> to set directory permissions on '
                            . $fullpath . '<br>';
                        }
                    }
                }
            }
            closedir($dh);
        }
    }

    public function getContent()
    {
        $this->ok_dir = '';
        $this->ok_file = '';
        $this->nok_dir = '';
        $this->nok_file = '';

        $html = '<h1>' . $this->trans('This tool will fix unsecure file- and folderpermissions', [], 'Modules.filepermissions.Admin')
        . '</h1><br>';

        if (Tools::isSubmit('submitChangePermissions')) {
            $this->chmodFileFolder(_PS_ROOT_DIR_);

            if (!empty($this->ok_dir)) {
                $html .= '<h2>' . $this->trans('Directories has been changed to 755', [], 'Modules.filepermissions.Admin')
                . '</h2>';
                $html .= $this->displayConfirmation($this->ok_dir);
            }
            if (!empty($this->ok_file)) {
                $html .= '<h2>' . $this->trans('Files has been changed to 644', [], 'Modules.filepermissions.Admin')
                . '</h2>';
                $html .= $this->displayConfirmation($this->ok_file);
            }
            if (!empty($this->nok_dir)) {
                $html .= '<h2>' . $this->trans('Directories has not been changed to 755', [], 'Modules.filepermissions.Admin')
                . '</h2>';
                $html .= $this->displayWarning($this->nok_dir);
            }
            if (!empty($this->nok_file)) {
                $html .= '<h2>' . $this->trans('Files has not been changed to 644', [], 'Modules.filepermissions.Admin')
                . '</h2>';
                $html .= $this->displayWarning($this->nok_file);
            }
        }

        return $html . $this->renderForm();
    }

    protected function renderForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitFilepermissionsModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
        . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        return $helper->generateForm([$this->getConfigForm()]);
    }

    protected function getConfigForm()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->trans('change directory permissions to 755 and file permissions to 644', [], 'Modules.filepermissions.Admin'),
                    'icon' => 'icon-cogs',
                ],
                'submit' => [
                    'title' => $this->trans('Fix permissions', [], 'Modules.filepermissions.Admin'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'submitChangePermissions',
                ],
            ],
        ];
    }

    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }
}
