<?php
/**
 * 2026 ARGSEGURIDAD
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 *
 * @author    ARGSEGURIDAD <info@argseguridad.com>
 * @copyright 2026 ARGSEGURIDAD
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class Argsellers extends Module
{
    public function __construct()
    {
        $this->name = 'argsellers';
        $this->tab = 'front_office_features';
        $this->version = '3.1.2';
        $this->author = 'ARGSEGURIDAD';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Gestor de Vendedores');
        $this->description = $this->l('Administración dinámica y visual de los asesores comerciales mediante %vendedores%.');

        $this->ps_versions_compliancy = array('min' => '1.7.0.0', 'max' => defined('_PS_VERSION_') ? _PS_VERSION_ : '1.7.99.99');
    }

    public function install()
    {
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'argsellers` (
            `id_seller` INT(11) NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(255) NOT NULL,
            `role` VARCHAR(255) NOT NULL,
            `phone` VARCHAR(32) NOT NULL,
            `email` VARCHAR(255) NOT NULL,
            `image` VARCHAR(255) NOT NULL,
            `active` TINYINT(1) NOT NULL DEFAULT 1,
            `position` INT(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id_seller`)
        ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;';

        if (!Db::getInstance()->execute($sql)) {
            return false;
        }

        Configuration::updateValue('ARGSELLERS_PHONE_PREFIX', '+54 9 11');
        Configuration::updateValue('ARGSELLERS_EMAIL_SUFFIX', '@argseguridad.com');
        Configuration::updateValue('ARGSELLERS_SECTORS', json_encode(array('Ventas', 'Gremio', 'Proyectos', 'Revendedores', 'Asesoría Técnica', 'Mayorista')));

        if (!$this->installTab()) {
            return false;
        }

        $imgDir = _PS_MODULE_DIR_ . $this->name . '/views/img/';
        if (!file_exists($imgDir)) {
            mkdir($imgDir, 0755, true);
        }

        $this->seedDefaultSellers();

        return parent::install() &&
            $this->registerHook('displayHeader') &&
            $this->registerHook('filterHtmlContent');
    }

    public function seedDefaultSellers()
    {
        $count = (int)Db::getInstance()->getValue('SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'argsellers`');
        if ($count == 0) {
            $sellers_seed = array(
                array('id' => 1, 'name' => 'Gabriel', 'role' => 'Ventas / Gremio', 'phone' => '1154553739', 'email' => 'contacto@argseguridad.com'),
                array('id' => 2, 'name' => 'Cristian', 'role' => 'Ventas / Gremio', 'phone' => '1135678196', 'email' => 'cristian@argseguridad.com'),
                array('id' => 3, 'name' => 'Guido', 'role' => 'Ventas / Gremio', 'phone' => '1154553750', 'email' => 'guido@argseguridad.com'),
                array('id' => 4, 'name' => 'Pablo', 'role' => 'Ventas / Gremio', 'phone' => '1167811902', 'email' => 'pablo@argseguridad.com'),
                array('id' => 5, 'name' => 'Matías', 'role' => 'Proyectos', 'phone' => '1154553771', 'email' => 'matias@argseguridad.com'),
                array('id' => 6, 'name' => 'Julián', 'role' => 'Sellers / Mayorista', 'phone' => '1121563362', 'email' => 'julian@argseguridad.com')
            );
            foreach ($sellers_seed as $s) {
                Db::getInstance()->execute("
                    INSERT INTO `" . _DB_PREFIX_ . "argsellers` (`id_seller`, `name`, `role`, `phone`, `email`, `image`, `active`, `position`)
                    VALUES (" . (int)$s['id'] . ", '" . pSQL($s['name']) . "', '" . pSQL($s['role']) . "', '" . pSQL($s['phone']) . "', '" . pSQL($s['email']) . "', '', 1, " . (int)$s['id'] . ")
                ");
            }
        }
    }

    public function uninstall()
    {
        $sql = 'DROP TABLE IF EXISTS `' . _DB_PREFIX_ . 'argsellers`;';
        Db::getInstance()->execute($sql);

        Configuration::deleteByName('ARGSELLERS_PHONE_PREFIX');
        Configuration::deleteByName('ARGSELLERS_EMAIL_SUFFIX');
        Configuration::deleteByName('ARGSELLERS_SECTORS');

        $this->uninstallTab();

        return parent::uninstall();
    }

    private function installTab()
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = 'AdminArgsSellers';
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Vendedores';
        }
        $tab->id_parent = (int)Tab::getIdFromClassName('AdminParentThemes');
        $tab->module = $this->name;
        return $tab->add();
    }

    private function uninstallTab()
    {
        $id_tab = (int)Tab::getIdFromClassName('AdminArgsSellers');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }
        return true;
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submitArgsellersConfig')) {
            $phone_prefix = Tools::getValue('ARGSELLERS_PHONE_PREFIX');
            $email_suffix = Tools::getValue('ARGSELLERS_EMAIL_SUFFIX');

            Configuration::updateValue('ARGSELLERS_PHONE_PREFIX', $phone_prefix);
            Configuration::updateValue('ARGSELLERS_EMAIL_SUFFIX', $email_suffix);

            $new_sector = trim(Tools::getValue('new_sector_name'));
            if (!empty($new_sector)) {
                $sectors = json_decode(Configuration::get('ARGSELLERS_SECTORS'), true);
                if (!is_array($sectors)) {
                    $sectors = array();
                }
                if (!in_array($new_sector, $sectors)) {
                    $sectors[] = $new_sector;
                    Configuration::updateValue('ARGSELLERS_SECTORS', json_encode($sectors));
                }
            }

            $output .= $this->displayConfirmation($this->l('Ajustes guardados correctamente.'));
        }

        $github_repo = Configuration::get('ARGSELLERS_GITHUB_REPO');
        if (!$github_repo) {
            $github_repo = 'magnus2201/argsellers';
        }
        $github_token = Configuration::get('ARGSELLERS_GITHUB_TOKEN');

        if (Tools::isSubmit('submitArgsellersGithubConfig')) {
            $github_repo = trim(Tools::getValue('ARGSELLERS_GITHUB_REPO'));
            $github_token = trim(Tools::getValue('ARGSELLERS_GITHUB_TOKEN'));

            Configuration::updateValue('ARGSELLERS_GITHUB_REPO', $github_repo);
            Configuration::updateValue('ARGSELLERS_GITHUB_TOKEN', $github_token);

            $output .= $this->displayConfirmation($this->l('Configuración de GitHub guardada.'));
        }

        $this->context->smarty->assign(array(
            'ARGSELLERS_PHONE_PREFIX' => Configuration::get('ARGSELLERS_PHONE_PREFIX'),
            'ARGSELLERS_EMAIL_SUFFIX' => Configuration::get('ARGSELLERS_EMAIL_SUFFIX'),
            'ARGSELLERS_SECTORS' => json_decode(Configuration::get('ARGSELLERS_SECTORS'), true),
            'ARGSELLERS_GITHUB_REPO' => $github_repo,
            'ARGSELLERS_GITHUB_TOKEN' => $github_token,
            'ARGSELLERS_ADMIN_URL' => $this->context->link->getAdminLink('AdminArgsSellers'),
            'ARGSELLERS_VERSION' => $this->version,
            'ARGSELLERS_BACKUP_VERSION' => Configuration::get('ARGSELLERS_BACKUP_VERSION'),
        ));

        $banner_html = '
        <div class="panel" style="margin-top: 25px; border-left: 5px solid #0284c7; background: #f0f9ff; padding: 22px 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.08);">
            <h3 style="margin-top: 0; margin-bottom: 10px; color: #0284c7; font-size: 20px; font-weight: 700; display: flex; align-items: center;">
                <i class="icon-info-circle" style="font-size: 24px; margin-right: 10px;"></i>
                Instrucciones de Uso
            </h3>
            <p style="font-size: 19px; font-weight: 600; color: #1e293b; margin: 0; line-height: 1.5;">
                Para insertar el bloque de vendedores en la página escribí <code style="font-size: 22px; color: #0284c7; background: #e0f2fe; padding: 4px 12px; border-radius: 6px; font-weight: 800; border: 1px solid #bae6fd;">%vendedores%</code> en el Page builder
            </p>
        </div>';

        return $output . $this->renderConfigForm() . $banner_html;
    }

    public function renderConfigForm()
    {
        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Configuración Global de Asesores Comerciales'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Prefijo de WhatsApp por Defecto'),
                        'name' => 'ARGSELLERS_PHONE_PREFIX',
                        'desc' => $this->l('Se antepondrá automáticamente al número ingresado (ej: +54 9 11).'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Sufijo de Email por Defecto'),
                        'name' => 'ARGSELLERS_EMAIL_SUFFIX',
                        'desc' => $this->l('Se completará automáticamente si solo ingresas el usuario de correo.'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Agregar Nuevo Sector / Rol'),
                        'name' => 'new_sector_name',
                        'desc' => $this->l('Escribe un nuevo sector (ej: "Soporte", "Gremio") para agregarlo a la lista de selección.'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Guardar Ajustes'),
                    'class' => 'btn btn-default pull-right'
                )
            ),
        );

        $fields_form_github = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Auto-Actualizador desde GitHub Repository'),
                    'icon' => 'icon-cloud-upload'
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Repositorio GitHub (usuario/repo)'),
                        'name' => 'ARGSELLERS_GITHUB_REPO',
                        'desc' => $this->l('Ejemplo: magnus2201/argsellers'),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Token de Acceso Personal de GitHub (Opcional para repos privados)'),
                        'name' => 'ARGSELLERS_GITHUB_TOKEN',
                        'desc' => $this->l('Solo necesario si el repositorio de GitHub es privado (ej: ghp_xxxxxxxxxxxx).'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Guardar Configuración GitHub'),
                    'class' => 'btn btn-default pull-right',
                    'name' => 'submitArgsellersGithubConfig'
                )
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitArgsellersConfig';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => array(
                'ARGSELLERS_PHONE_PREFIX' => Configuration::get('ARGSELLERS_PHONE_PREFIX'),
                'ARGSELLERS_EMAIL_SUFFIX' => Configuration::get('ARGSELLERS_EMAIL_SUFFIX'),
                'new_sector_name' => '',
                'ARGSELLERS_GITHUB_REPO' => Configuration::get('ARGSELLERS_GITHUB_REPO') ? Configuration::get('ARGSELLERS_GITHUB_REPO') : 'magnus2201/argsellers',
                'ARGSELLERS_GITHUB_TOKEN' => Configuration::get('ARGSELLERS_GITHUB_TOKEN'),
            ),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        );

        return $helper->generateForm(array($fields_form, $fields_form_github));
    }

    public function hookDisplayHeader($params)
    {
        $css_uri = 'modules/' . $this->name . '/views/css/front.css?v=' . $this->version;
        $this->context->controller->registerStylesheet(
            'modules-argsellers-front',
            $css_uri,
            array('media' => 'all', 'priority' => 150)
        );

        if (isset($this->context->smarty)) {
            $this->context->smarty->registerFilter(
                'output',
                array($this, 'smartyOutputFilter')
            );
        }
    }

    public function smartyOutputFilter($output, $smarty)
    {
        $has_vendedores = (strpos($output, '%vendedores%') !== false || strpos($output, '[argsellers]') !== false || strpos($output, '{vendedores}') !== false);
        if (!$has_vendedores) {
            return $output;
        }

        $grid_html = $this->renderSellersGrid();
        if (empty($grid_html)) {
            return $output;
        }

        $output = str_replace('%vendedores%', $grid_html, $output);
        $output = str_replace('[argsellers]', $grid_html, $output);
        $output = str_replace('{vendedores}', $grid_html, $output);

        return $output;
    }

    public function hookFilterHtmlContent($params)
    {
        if (isset($params['html'])) {
            $has_vendedores = (strpos($params['html'], '%vendedores%') !== false || strpos($params['html'], '[argsellers]') !== false || strpos($params['html'], '{vendedores}') !== false);
            if ($has_vendedores) {
                $grid_html = $this->renderSellersGrid();
                if (!empty($grid_html)) {
                    $params['html'] = str_replace('%vendedores%', $grid_html, $params['html']);
                    $params['html'] = str_replace('[argsellers]', $grid_html, $params['html']);
                    $params['html'] = str_replace('{vendedores}', $grid_html, $params['html']);
                }
            }
        }
        return $params;
    }

    public function renderSellersGrid()
    {
        try {
            $sellers = Db::getInstance()->executeS('
                SELECT * FROM `' . _DB_PREFIX_ . 'argsellers`
                WHERE `active` = 1
                ORDER BY `position` ASC
            ');

            if (empty($sellers)) {
                $sellers = Db::getInstance()->executeS('
                    SELECT * FROM `' . _DB_PREFIX_ . 'argsellers`
                    ORDER BY `position` ASC
                ');
            }

            if (empty($sellers)) {
                return '';
            }

            $phone_prefix = Configuration::get('ARGSELLERS_PHONE_PREFIX');
            if (!$phone_prefix) {
                $phone_prefix = '+54 9 11';
            }

            $email_suffix = Configuration::get('ARGSELLERS_EMAIL_SUFFIX');
            if (!$email_suffix) {
                $email_suffix = '@argseguridad.com';
            }

            foreach ($sellers as &$s) {
                $clean_phone = preg_replace('/[^0-9]/', '', $s['phone']);
                if (substr($clean_phone, 0, 2) === '11' && strlen($clean_phone) == 10) {
                    $clean_phone = '549' . $clean_phone;
                } elseif (substr($clean_phone, 0, 3) === '549' || substr($clean_phone, 0, 2) === '54') {
                    // Already formatted
                } else {
                    $clean_phone = preg_replace('/[^0-9]/', '', $phone_prefix . $clean_phone);
                }
                $s['wa_url'] = 'https://api.whatsapp.com/send?phone=' . $clean_phone . '&text=' . rawurlencode('Hola ' . $s['name'] . ', tengo una consulta desde la web.');

                $s['qr_url'] = 'https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=' . urlencode($s['wa_url']);

                if (empty($s['image'])) {
                    $s['image_url'] = $this->_path . 'views/img/default.png';
                } else {
                    $s['image_url'] = $this->_path . 'views/img/' . $s['image'];
                }
            }

            $this->context->smarty->assign(array(
                'argsellers_list' => $sellers,
                'argsellers_path' => $this->_path,
            ));

            return $this->display(__FILE__, 'views/templates/hook/argsellers.tpl');
        } catch (Exception $e) {
            return '<!-- argsellers render error: ' . htmlspecialchars($e->getMessage()) . ' -->';
        }
    }

    public function runUpgradeModule()
    {
        if (class_exists('Module')) {
            $up_file = _PS_MODULE_DIR_ . $this->name . '/upgrade/upgrade-3.1.2.php';
            if (file_exists($up_file)) {
                include_once($up_file);
                if (function_exists('upgrade_module_3_1_2')) {
                    return upgrade_module_3_1_2($this);
                }
            }
        }
        return true;
    }
}
