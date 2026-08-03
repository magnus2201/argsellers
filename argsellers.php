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
        $this->version = '3.3.0';
        $this->author = 'ARGSEGURIDAD';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Gestor de Vendedores');
        $this->description = $this->l('Administración dinámica y visual de los asesores comerciales mediante shortcodes personalizados entre % %.');

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
        Configuration::updateValue('ARGSELLERS_SHORTCODES', json_encode(array('vendedores')));

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
        Configuration::deleteByName('ARGSELLERS_SHORTCODES');

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

            $new_shortcode = trim(Tools::getValue('new_shortcode_name'));
            if (!empty($new_shortcode)) {
                $clean_sc = trim(str_replace('%', '', $new_shortcode));
                if (!empty($clean_sc)) {
                    $shortcodes = json_decode(Configuration::get('ARGSELLERS_SHORTCODES'), true);
                    if (!is_array($shortcodes)) {
                        $shortcodes = array('vendedores');
                    }
                    if (!in_array($clean_sc, $shortcodes)) {
                        $shortcodes[] = $clean_sc;
                        Configuration::updateValue('ARGSELLERS_SHORTCODES', json_encode($shortcodes));
                    }
                }
            }

            $output .= $this->displayConfirmation($this->l('Ajustes guardados correctamente.'));
        }

        if (Tools::isSubmit('delete_sector')) {
            $sector_to_delete = Tools::getValue('delete_sector');
            $sectors = json_decode(Configuration::get('ARGSELLERS_SECTORS'), true);
            if (is_array($sectors)) {
                $sectors = array_values(array_filter($sectors, function($s) use ($sector_to_delete) {
                    return $s !== $sector_to_delete;
                }));
                Configuration::updateValue('ARGSELLERS_SECTORS', json_encode($sectors));
                $output .= $this->displayConfirmation($this->l('Sector eliminado correctamente.'));
            }
        }

        if (Tools::isSubmit('delete_shortcode')) {
            $sc_to_delete = trim(str_replace('%', '', Tools::getValue('delete_shortcode')));
            $shortcodes = json_decode(Configuration::get('ARGSELLERS_SHORTCODES'), true);
            if (is_array($shortcodes)) {
                $shortcodes = array_values(array_filter($shortcodes, function($sc) use ($sc_to_delete) {
                    return $sc !== $sc_to_delete;
                }));
                if (empty($shortcodes)) {
                    $shortcodes = array('vendedores');
                }
                Configuration::updateValue('ARGSELLERS_SHORTCODES', json_encode($shortcodes));
                $output .= $this->displayConfirmation($this->l('Shortcode eliminado correctamente.'));
            }
        }

        return $output . $this->renderConfigForm() . self::getInstructionBanner();
    }

    protected function renderConfigForm()
    {
        $sectors = json_decode(Configuration::get('ARGSELLERS_SECTORS'), true);
        if (!is_array($sectors)) {
            $sectors = array();
        }

        $sectors_html = '<div style="margin-bottom: 20px;"><h4>Sectores / Roles Disponibles:</h4><ul style="list-style: none; padding-left: 0;">';
        foreach ($sectors as $sector) {
            $delete_url = $this->context->link->getAdminLink('AdminModules', true) . '&configure=' . $this->name . '&delete_sector=' . urlencode($sector);
            $sectors_html .= '<li style="margin-bottom: 5px; background: #f8f9fa; padding: 6px 12px; border-radius: 4px; display: inline-block; margin-right: 8px;">' . htmlspecialchars($sector) . ' <a href="' . $delete_url . '" style="color: #dc3545; margin-left: 8px; font-weight: bold;" onclick="return confirm(\'¿Eliminar este sector?\');">&times;</a></li>';
        }
        $sectors_html .= '</ul></div>';

        $shortcodes = json_decode(Configuration::get('ARGSELLERS_SHORTCODES'), true);
        if (!is_array($shortcodes) || empty($shortcodes)) {
            $shortcodes = array('vendedores');
        }

        $shortcodes_html = '<div style="margin-bottom: 20px;"><h4>Shortcodes Personalizados Activos:</h4><ul style="list-style: none; padding-left: 0;">';
        foreach ($shortcodes as $sc) {
            $sc_clean = trim(str_replace('%', '', $sc));
            $delete_url = $this->context->link->getAdminLink('AdminModules', true) . '&configure=' . $this->name . '&delete_shortcode=' . urlencode($sc_clean);
            $shortcodes_html .= '<li style="margin-bottom: 5px; background: #e0f2fe; color: #0284c7; padding: 6px 14px; border-radius: 6px; display: inline-block; margin-right: 8px; font-weight: bold; border: 1px solid #bae6fd;">%' . htmlspecialchars($sc_clean) . '% <a href="' . $delete_url . '" style="color: #dc3545; margin-left: 10px; font-weight: bold;" onclick="return confirm(\'¿Eliminar este shortcode?\');">&times;</a></li>';
        }
        $shortcodes_html .= '</ul></div>';

        $fields_form = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Configuración General de Vendedores'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'text',
                        'label' => $this->l('Prefijo de Teléfono Global'),
                        'name' => 'ARGSELLERS_PHONE_PREFIX',
                        'desc' => $this->l('Prefijo para todos los números (ej. +54 9 11).'),
                        'required' => true,
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Sufijo de Correo Electrónico Global'),
                        'name' => 'ARGSELLERS_EMAIL_SUFFIX',
                        'desc' => $this->l('Dominio de correo por defecto (ej. @argseguridad.com).'),
                        'required' => true,
                    ),
                    array(
                        'type' => 'free',
                        'label' => $this->l('Sectores Actuales'),
                        'name' => 'sectors_list_html',
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Añadir Nuevo Sector / Rol'),
                        'name' => 'new_sector_name',
                        'desc' => $this->l('Escribe un nuevo sector (ej. Soporte Post-Venta) y haz clic en Guardar para añadirlo a las opciones.'),
                    ),
                    array(
                        'type' => 'free',
                        'label' => $this->l('Shortcodes Personalizados Activos'),
                        'name' => 'shortcodes_list_html',
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Añadir Nuevo Shortcode (se envolverá automáticamente en % %)'),
                        'name' => 'new_shortcode_name',
                        'desc' => $this->l('Escribe solo el nombre interno (ej: "vendedores", "asesores", "equipo"). Se guardará automáticamente como %nombre%.'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Guardar Ajustes'),
                    'class' => 'btn btn-default pull-right',
                ),
            ),
        );

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitArgsellersConfig';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->fields_value['ARGSELLERS_PHONE_PREFIX'] = Configuration::get('ARGSELLERS_PHONE_PREFIX');
        $helper->fields_value['ARGSELLERS_EMAIL_SUFFIX'] = Configuration::get('ARGSELLERS_EMAIL_SUFFIX');
        $helper->fields_value['sectors_list_html'] = $sectors_html;
        $helper->fields_value['new_sector_name'] = '';
        $helper->fields_value['shortcodes_list_html'] = $shortcodes_html;
        $helper->fields_value['new_shortcode_name'] = '';

        return $helper->generateForm(array($fields_form));
    }

    public static function getInstructionBanner()
    {
        $shortcodes = json_decode(Configuration::get('ARGSELLERS_SHORTCODES'), true);
        if (!is_array($shortcodes) || empty($shortcodes)) {
            $shortcodes = array('vendedores');
        }

        $badges_html = '';
        foreach ($shortcodes as $idx => $sc) {
            $sc_clean = trim(str_replace('%', '', $sc));
            if ($idx > 0) {
                $badges_html .= ' o ';
            }
            $badges_html .= '<code style="font-size: 22px; color: #0284c7; background: #e0f2fe; padding: 4px 12px; border-radius: 6px; font-weight: 800; border: 1px solid #bae6fd;">%' . htmlspecialchars($sc_clean) . '%</code>';
        }

        return '
        <div class="panel" style="margin-top: 25px; border-left: 5px solid #0284c7; background: #f0f9ff; padding: 22px 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.08);">
            <h3 style="margin-top: 0; margin-bottom: 10px; color: #0284c7; font-size: 20px; font-weight: 700; display: flex; align-items: center;">
                <i class="icon-info-circle" style="font-size: 24px; margin-right: 10px;"></i>
                Instrucciones de Uso
            </h3>
            <p style="font-size: 19px; font-weight: 600; color: #1e293b; margin: 0; line-height: 1.5;">
                Para insertar el bloque de vendedores en la página escribí ' . $badges_html . ' en el Page builder
            </p>
        </div>';
    }

    public function getActiveShortcodePatterns()
    {
        $shortcodes = json_decode(Configuration::get('ARGSELLERS_SHORTCODES'), true);
        if (!is_array($shortcodes) || empty($shortcodes)) {
            $shortcodes = array('vendedores');
        }

        $patterns = array();
        foreach ($shortcodes as $sc) {
            $clean = trim(str_replace('%', '', $sc));
            if (!empty($clean)) {
                $patterns[] = '%' . $clean . '%';
            }
        }
        if (empty($patterns)) {
            $patterns = array('%vendedores%');
        }
        return $patterns;
    }

    public function hookDisplayHeader()
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
        $patterns = $this->getActiveShortcodePatterns();
        $has_match = false;
        foreach ($patterns as $pattern) {
            if (strpos($output, $pattern) !== false) {
                $has_match = true;
                break;
            }
        }

        if (!$has_match) {
            return $output;
        }

        $grid_html = $this->renderSellersGrid();
        if (empty($grid_html)) {
            return $output;
        }

        foreach ($patterns as $pattern) {
            $output = str_replace($pattern, $grid_html, $output);
        }

        return $output;
    }

    public function hookFilterHtmlContent($params)
    {
        if (isset($params['html'])) {
            $patterns = $this->getActiveShortcodePatterns();
            $has_match = false;
            foreach ($patterns as $pattern) {
                if (strpos($params['html'], $pattern) !== false) {
                    $has_match = true;
                    break;
                }
            }
            if ($has_match) {
                $grid_html = $this->renderSellersGrid();
                if (!empty($grid_html)) {
                    foreach ($patterns as $pattern) {
                        $params['html'] = str_replace($pattern, $grid_html, $params['html']);
                    }
                }
            }
        }
        return $params;
    }

    public function renderSellersGrid()
    {
        try {
            $this->seedDefaultSellers();

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

            foreach ($sellers as &$seller) {
                $raw_phone = preg_replace('/[^0-9]/', '', $seller['phone']);
                $prefix_digits = preg_replace('/[^0-9]/', '', $phone_prefix);
                $seller['clean_whatsapp'] = $prefix_digits . $raw_phone;

                $formatted_num = $raw_phone;
                if (strlen($raw_phone) >= 8) {
                    $formatted_num = substr($raw_phone, 0, 4) . '-' . substr($raw_phone, 4);
                }
                $seller['formatted_phone'] = $phone_prefix . ' ' . $formatted_num;

                $clean_email = trim($seller['email']);
                if (strpos($clean_email, '@') === false) {
                    $seller['full_email'] = trim($clean_email . $email_suffix);
                } else {
                    $seller['full_email'] = $clean_email;
                }
                $seller['gmail_url'] = 'https://mail.google.com/mail/?view=cm&fs=1&to=' . urlencode($seller['full_email']);
            }

            $this->context->smarty->assign(array(
                'argsellers' => $sellers,
                'argsellers_img_path' => $this->context->link->getMediaLink('/modules/' . $this->name . '/views/img/')
            ));

            return $this->display(__FILE__, 'views/templates/hook/argsellers.tpl');
        } catch (Exception $e) {
            return '';
        }
    }

    public function runUpgradeModule()
    {
        if (class_exists('Module')) {
            $up_file = _PS_MODULE_DIR_ . $this->name . '/upgrade/upgrade-3.3.0.php';
            if (file_exists($up_file)) {
                include_once($up_file);
                if (function_exists('upgrade_module_3_3_0')) {
                    return upgrade_module_3_3_0($this);
                }
            }
        }
        return true;
    }
}
