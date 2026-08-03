<?php
/**
 * 2026 ARGSEGURIDAD
 * Admin controller for managing sellers in PrestaShop backoffice v2.7.3
 */

require_once dirname(__FILE__) . '/../../classes/ArgsellerModel.php';

class AdminArgsSellersController extends ModuleAdminController
{
    protected $position_identifier = 'id_seller';

    public function __construct()
    {
        $this->table = 'argsellers';
        $this->className = 'ArgsellerModel';
        $this->identifier = 'id_seller';
        $this->bootstrap = true;

        parent::__construct();

        $this->fields_list = array(
            'id_seller' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'width' => 30
            ),
            'image' => array(
                'title' => $this->l('Foto'),
                'align' => 'center',
                'orderby' => false,
                'search' => false,
                'callback' => 'displayImage'
            ),
            'name' => array(
                'title' => $this->l('Nombre'),
                'width' => 'auto'
            ),
            'role' => array(
                'title' => $this->l('Sector / Rol'),
                'width' => 'auto'
            ),
            'phone' => array(
                'title' => $this->l('Teléfono WhatsApp'),
                'width' => 'auto',
                'callback' => 'displayFormattedPhone'
            ),
            'email' => array(
                'title' => $this->l('Correo Electrónico'),
                'width' => 'auto',
                'callback' => 'displayFormattedEmail'
            ),
            'active' => array(
                'title' => $this->l('Estado'),
                'active' => 'status',
                'type' => 'bool',
                'align' => 'center',
                'width' => 50,
                'orderby' => false
            ),
            'position' => array(
                'title' => $this->l('Posición'),
                'filter_key' => 'a!position',
                'position' => 'position',
                'align' => 'center',
                'class' => 'fixed-width-md'
            )
        );

        $this->bulk_actions = array(
            'delete' => array(
                'text' => $this->l('Borrar seleccionados'),
                'confirm' => $this->l('¿Quieres borrar los vendedores seleccionados?')
            )
        );

        $this->addRowAction('edit');
        $this->addRowAction('delete');

        $this->_defaultOrderBy = 'position';
        $this->_defaultOrderWay = 'ASC';
    }

    public function renderList()
    {
        $list_html = parent::renderList();

        $banner_html = '
        <div class="panel" style="margin-top: 25px; border-left: 5px solid #0284c7; background: #f0f9ff; padding: 22px 25px; border-radius: 8px; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.08);">
            <h3 style="margin-top: 0; margin-bottom: 10px; color: #0284c7; font-size: 20px; font-weight: 700; display: flex; align-items: center;">
                <i class="icon-info-circle" style="font-size: 24px; margin-right: 10px;"></i>
                Instrucciones de Uso
            </h3>
            <p style="font-size: 19px; font-weight: 600; color: #1e293b; margin: 0; line-height: 1.5;">
                Para insertar el bloque de vendedores en la pagina escribí <code style="font-size: 22px; color: #0284c7; background: #e0f2fe; padding: 4px 12px; border-radius: 6px; font-weight: 800; border: 1px solid #bae6fd;">%vendedores%</code> en el Page builder
            </p>
        </div>';

        return $list_html . $banner_html;
    }

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();

        // 1-Click Update Module button
        $this->page_header_toolbar_btn['update_module'] = array(
            'href' => self::$currentIndex . '&action=updateModule&token=' . $this->token,
            'desc' => $this->l('Actualizar Módulo'),
            'icon' => 'process-icon-refresh icon-cloud-upload',
        );

        // Global Settings button
        $this->page_header_toolbar_btn['configure_module'] = array(
            'href' => $this->context->link->getAdminLink('AdminModules', true) . '&configure=' . $this->module->name,
            'desc' => $this->l('Ajustes Globales'),
            'icon' => 'process-icon-cogs icon-cogs',
        );

        // Rollback / Restore Previous Version button (if backup exists)
        $backup_dir = _PS_MODULE_DIR_ . 'argsellers_backup/';
        $backup_ver = Configuration::get('ARGSELLERS_BACKUP_VERSION');
        if (file_exists($backup_dir . 'argsellers.php')) {
            $this->page_header_toolbar_btn['rollback_module'] = array(
                'href' => self::$currentIndex . '&action=rollbackModule&token=' . $this->token,
                'desc' => sprintf($this->l('Versión Anterior (%s)'), $backup_ver ? 'v' . $backup_ver : 'Backup'),
                'icon' => 'process-icon-undo icon-undo',
            );
        }
    }

    public function postProcess()
    {
        // Intercept action=updateModule or action=rollbackModule
        if (Tools::getValue('action') === 'updateModule') {
            $this->processUpdateModule();
            return;
        }

        if (Tools::getValue('action') === 'rollbackModule') {
            $this->processRollbackModule();
            return;
        }

        if (Tools::isSubmit('submitAdd' . $this->table)) {
            $selected_sectors = Tools::getValue('sectors_selected');
            if (is_array($selected_sectors) && !empty($selected_sectors)) {
                $_POST['role'] = implode(' / ', $selected_sectors);
            } else {
                $_POST['role'] = 'General';
            }

            $phone_input = Tools::getValue('phone_input');
            $_POST['phone'] = preg_replace('/[^0-9]/', '', $phone_input);

            $email_input = trim(Tools::getValue('email_user_input'));
            $email_suffix = Configuration::get('ARGSELLERS_EMAIL_SUFFIX');
            if (!$email_suffix) $email_suffix = '@argseguridad.com';

            if (strpos($email_input, '@') === false) {
                $_POST['email'] = $email_input . $email_suffix;
            } else {
                $_POST['email'] = $email_input;
            }

            if (isset($_FILES['image']) && !empty($_FILES['image']['tmp_name'])) {
                $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                $allowed = array('jpg', 'jpeg', 'png', 'webp', 'gif');

                if (in_array($extension, $allowed)) {
                    $filename = uniqid('profile_') . '.' . $extension;
                    $destination = _PS_MODULE_DIR_ . $this->module->name . '/views/img/' . $filename;

                    if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                        $id_seller = (int)Tools::getValue('id_seller');
                        if ($id_seller) {
                            $seller = new ArgsellerModel($id_seller);
                            if ($seller->image && file_exists(_PS_MODULE_DIR_ . $this->module->name . '/views/img/' . $seller->image)) {
                                @unlink(_PS_MODULE_DIR_ . $this->module->name . '/views/img/' . $seller->image);
                            }
                        }
                        $_POST['image'] = $filename;
                    } else {
                        $this->errors[] = $this->l('Error al guardar la foto de perfil en el servidor.');
                    }
                } else {
                    $this->errors[] = $this->l('Formato de imagen no válido. Solo se permiten JPG, PNG, WEBP o GIF.');
                }
            }
        }
        return parent::postProcess();
    }

    public function processUpdateModule()
    {
        $github_repo = Configuration::get('ARGSELLERS_GITHUB_REPO');
        if (!$github_repo) {
            $github_repo = 'magnus2201/argsellers';
        }
        
        $github_token = Configuration::get('ARGSELLERS_GITHUB_TOKEN');

        // Version before update
        $version_before = $this->module->version;

        // Automatically create a backup of the current version BEFORE applying update
        $backup_dir = _PS_MODULE_DIR_ . 'argsellers_backup/';
        $current_dir = _PS_MODULE_DIR_ . 'argsellers/';
        if (file_exists($backup_dir)) {
            $this->recursiveRemoveDir($backup_dir);
        }
        $this->rcopy($current_dir, $backup_dir);
        Configuration::updateValue('ARGSELLERS_BACKUP_VERSION', $version_before);
        
        // Priority 1: GitHub Repository Archive ZIP URL
        // Priority 2: Raw repository argsellers.zip URL
        $urls_to_try = array(
            'https://github.com/' . $github_repo . '/archive/refs/heads/main.zip',
            'https://raw.githubusercontent.com/' . $github_repo . '/main/argsellers.zip'
        );

        $zip_file = _PS_MODULE_DIR_ . $this->module->name . '_temp_update.zip';
        $file_downloaded = false;
        $download_error = '';
        $last_http_code = 0;

        foreach ($urls_to_try as $download_url) {
            // 1. Try cURL fetch
            if (function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $download_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) PrestaShop-Updater');

                if (!empty($github_token)) {
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Authorization: token ' . $github_token,
                        'Accept: application/vnd.github.v3.raw'
                    ));
                }

                $file_data = curl_exec($ch);
                $curl_error = curl_error($ch);
                $last_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($last_http_code == 200 && !empty($file_data) && strlen($file_data) > 500) {
                    file_put_contents($zip_file, $file_data);
                    $file_downloaded = true;
                    break;
                } else {
                    $download_error = $curl_error ? $curl_error : 'HTTP ' . $last_http_code;
                }
            } else {
                $download_error = 'cURL no disponible en el servidor';
            }

            // 2. Fallback stream context fetch
            if (!$file_downloaded) {
                $opts = array(
                    'http' => array(
                        'method' => 'GET',
                        'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) PrestaShop-Updater\r\n" .
                                    (!empty($github_token) ? "Authorization: token " . $github_token . "\r\n" : ""),
                        'timeout' => 30
                    ),
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false
                    )
                );
                $stream_data = @file_get_contents($download_url, false, stream_context_create($opts));
                if (!empty($stream_data) && strlen($stream_data) > 500) {
                    file_put_contents($zip_file, $stream_data);
                    $file_downloaded = true;
                    break;
                } else {
                    $download_error = 'Stream fetch failed';
                }
            }
        }

        // Extract and copy files cleanly
        $version_after = $version_before;
        if (file_exists($zip_file) && class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($zip_file) === true) {
                $temp_extract = _PS_MODULE_DIR_ . $this->module->name . '_extracted_temp/';
                if (!file_exists($temp_extract)) {
                    mkdir($temp_extract, 0755, true);
                }
                $zip->extractTo($temp_extract);
                $zip->close();

                // Locate source folder inside zip
                $subfolders = glob($temp_extract . '*', GLOB_ONLYDIR);
                $source_dir = $temp_extract;
                if (!empty($subfolders) && file_exists($subfolders[0] . '/argsellers.php')) {
                    $source_dir = $subfolders[0] . '/';
                }

                // Try to read new version BEFORE overwriting
                $new_module_file = $source_dir . 'argsellers.php';
                if (file_exists($new_module_file)) {
                    $new_content = file_get_contents($new_module_file);
                    if (preg_match("/\\\$this->version\s*=\s*'([^']+)'/", $new_content, $matches)) {
                        $version_after = $matches[1];
                    }
                }

                // Copy files recursively into module directory
                $this->rcopy($source_dir, _PS_MODULE_DIR_ . $this->module->name . '/');

                // Cleanup temp directories & files
                Tools::deleteDirectory($temp_extract, true);
                @unlink($zip_file);
            }
        }

        // Run PrestaShop Native Upgrade Script
        if (method_exists($this->module, 'runUpgradeModule')) {
            $this->module->runUpgradeModule();
        }

        // Full PrestaShop & Smarty Cache Purge
        try {
            Tools::clearSmartyCache();
            Tools::clearXMLCache();
            Media::clearCache();
        } catch (Exception $e) {
            // Ignore cache clear exceptions
        }

        // Keep confirmation message in session cookie for PrestaShop redirect
        if ($file_downloaded) {
            $msg = 'Modulo actualizado desde GitHub (' . $github_repo . '). Version: v' . $version_before . ' -> v' . $version_after . '. Cache purgada.';
            $this->context->cookie->argsellers_conf = $msg;
            $this->context->cookie->argsellers_conf_type = 'success';
        } else {
            $msg = 'ADVERTENCIA: No se pudo descargar desde GitHub. Error: [' . $download_error . ']. Version instalada: v' . $version_before . '. Cache actualizada.';
            $this->context->cookie->argsellers_conf = $msg;
            $this->context->cookie->argsellers_conf_type = 'warning';
        }

        Tools::redirectAdmin(self::$currentIndex . '&conf=4&token=' . $this->token);
    }

    public function initContent()
    {
        if (isset($this->context->cookie->argsellers_conf)) {
            $type = isset($this->context->cookie->argsellers_conf_type) ? $this->context->cookie->argsellers_conf_type : 'success';
            if ($type === 'warning') {
                $this->warnings[] = $this->context->cookie->argsellers_conf;
            } else {
                $this->confirmations[] = $this->context->cookie->argsellers_conf;
            }
            unset($this->context->cookie->argsellers_conf);
            unset($this->context->cookie->argsellers_conf_type);
        }
        parent::initContent();
    }

    private function rcopy($src, $dst)
    {
        if (!file_exists($src)) return;
        if (is_dir($src)) {
            if (!file_exists($dst)) mkdir($dst, 0755, true);
            $files = scandir($src);
            foreach ($files as $file) {
                if ($file != "." && $file != "..") {
                    $this->rcopy("$src/$file", "$dst/$file");
                }
            }
        } else if (file_exists($src)) {
            copy($src, $dst);
        }
    }

    public function processRollbackModule()
    {
        $backup_dir = _PS_MODULE_DIR_ . 'argsellers_backup/';
        $module_dir = _PS_MODULE_DIR_ . 'argsellers/';
        $backup_version = Configuration::get('ARGSELLERS_BACKUP_VERSION');
        if (!$backup_version) {
            $backup_version = 'anterior';
        }

        if (file_exists($backup_dir . 'argsellers.php')) {
            $this->rcopy($backup_dir, $module_dir);

            try {
                Tools::clearSmartyCache();
                Tools::clearXMLCache();
                Media::clearCache();
            } catch (Exception $e) {}

            $msg = 'Se restauró la versión anterior (v' . $backup_version . ') correctamente desde el backup de seguridad. Caché purgada.';
            $this->context->cookie->argsellers_conf = $msg;
            $this->context->cookie->argsellers_conf_type = 'success';
        } else {
            $msg = 'No se encontró ningún backup de la versión anterior para restaurar.';
            $this->context->cookie->argsellers_conf = $msg;
            $this->context->cookie->argsellers_conf_type = 'warning';
        }

        Tools::redirectAdmin(self::$currentIndex . '&token=' . $this->token);
    }

    private function recursiveRemoveDir($dir)
    {
        if (!file_exists($dir)) {
            return;
        }
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->recursiveRemoveDir("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    public function displayImage($value, $row)
    {
        if ($value) {
            $path = _MODULE_DIR_ . $this->module->name . '/views/img/' . $value;
            return '<img src="' . $path . '" class="img-thumbnail" style="max-width: 45px; height: 45px; border-radius: 50%; object-fit: cover;" />';
        }
        return '<div style="width: 45px; height: 45px; border-radius: 50%; background: #e9ecef; display: inline-block; vertical-align: middle;"><svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="width: 100%; height: 100%;"><path d="M12 12C14.21 12 16 10.21 16 8C16 5.79 14.21 4 12 4C9.79 4 8 5.79 8 8C8 10.21 9.79 12 12 12ZM12 14C9.33 14 4 15.34 4 18V20H20V18C20 15.34 14.67 14 12 14Z" fill="#adb5bd"/></svg></div>';
    }

    public function displayFormattedPhone($value, $row)
    {
        $prefix = Configuration::get('ARGSELLERS_PHONE_PREFIX');
        if (!$prefix) $prefix = '+54 9 11';
        
        $raw = preg_replace('/[^0-9]/', '', $value);
        if (strlen($raw) >= 8) {
            $formatted = substr($raw, 0, 4) . '-' . substr($raw, 4);
        } else {
            $formatted = $raw;
        }
        return htmlspecialchars($prefix . ' ' . $formatted);
    }

    public function displayFormattedEmail($value, $row)
    {
        $suffix = Configuration::get('ARGSELLERS_EMAIL_SUFFIX');
        if (!$suffix) $suffix = '@argseguridad.com';
        
        if (strpos($value, '@') === false) {
            return htmlspecialchars($value . $suffix);
        }
        return htmlspecialchars($value);
    }

    public function renderForm()
    {
        $obj = $this->loadObject(true);

        $phone_prefix = Configuration::get('ARGSELLERS_PHONE_PREFIX');
        if (!$phone_prefix) $phone_prefix = '+54 9 11';

        $email_suffix = Configuration::get('ARGSELLERS_EMAIL_SUFFIX');
        if (!$email_suffix) $email_suffix = '@argseguridad.com';

        $available_sectors = json_decode(Configuration::get('ARGSELLERS_SECTORS'), true);
        if (!is_array($available_sectors)) {
            $available_sectors = array('Ventas', 'Gremio', 'Proyectos', 'Revendedores', 'Asesoría Técnica', 'Mayorista');
        }

        $assigned_roles = array();
        if ($obj && $obj->role) {
            $assigned_roles = array_map('trim', explode('/', $obj->role));
        }

        $sectors_checkboxes_html = '<div class="row"><div class="col-lg-9" style="padding-top:7px;">';
        foreach ($available_sectors as $idx => $sec) {
            $checked = in_array($sec, $assigned_roles) ? 'checked="checked"' : '';
            $sectors_checkboxes_html .= '<label style="margin-right: 15px; font-weight: normal; cursor: pointer;">';
            $sectors_checkboxes_html .= '<input type="checkbox" name="sectors_selected[]" value="' . htmlspecialchars($sec) . '" ' . $checked . ' /> ' . htmlspecialchars($sec);
            $sectors_checkboxes_html .= '</label>';
        }
        $config_url = $this->context->link->getAdminLink('AdminModules', true) . '&configure=' . $this->module->name;
        $sectors_checkboxes_html .= '<div style="margin-top: 8px;"><a href="' . $config_url . '" target="_blank" class="btn btn-default btn-xs"><i class="icon-plus"></i> ' . $this->l('Añadir nuevo sector') . '</a></div>';
        $sectors_checkboxes_html .= '</div></div>';

        $clean_phone = '';
        if ($obj && $obj->phone) {
            $clean_phone = preg_replace('/[^0-9]/', '', $obj->phone);
            if (strlen($clean_phone) >= 8) {
                $clean_phone = substr($clean_phone, 0, 4) . '-' . substr($clean_phone, 4);
            }
        }

        $clean_email_user = '';
        if ($obj && $obj->email) {
            if (strpos($obj->email, '@') !== false) {
                $parts = explode('@', $obj->email);
                $clean_email_user = $parts[0];
            } else {
                $clean_email_user = $obj->email;
            }
        }

        $image_preview_src = '';
        if ($obj && $obj->image) {
            $image_preview_src = _MODULE_DIR_ . $this->module->name . '/views/img/' . $obj->image;
        }

        $drag_drop_html = '
        <div id="argseller-dropzone" style="border: 2px dashed #009de0; border-radius: 10px; padding: 25px; text-align: center; background: #f8fafc; cursor: pointer; transition: all 0.2s ease;">
            <input type="file" name="image" id="argseller-file-input" style="display: none;" accept="image/*" />
            <div id="dropzone-prompt">
                <i class="icon-cloud-upload" style="font-size: 36px; color: #009de0;"></i>
                <p style="margin-top: 10px; font-weight: 600; color: #334155;">Arrastra y suelta aquí la foto de perfil del vendedor</p>
                <span class="btn btn-default btn-sm">O haz clic para seleccionar archivo</span>
            </div>
            <div id="dropzone-preview" style="' . ($image_preview_src ? '' : 'display: none;') . ' margin-top: 10px;">
                <img id="preview-img-target" src="' . $image_preview_src . '" style="max-width: 130px; height: 130px; border-radius: 50%; object-fit: cover; border: 3px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1);" />
                <p style="margin-top: 8px; font-size: 12px; color: #64748b;" id="preview-filename"></p>
            </div>
        </div>
        <script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function() {
                var zone = document.getElementById("argseller-dropzone");
                var input = document.getElementById("argseller-file-input");
                var prompt = document.getElementById("dropzone-prompt");
                var preview = document.getElementById("dropzone-preview");
                var previewImg = document.getElementById("preview-img-target");
                var filenameText = document.getElementById("preview-filename");

                zone.addEventListener("click", function() { input.click(); });
                zone.addEventListener("dragover", function(e) { e.preventDefault(); zone.style.background = "#e0f2fe"; });
                zone.addEventListener("dragleave", function(e) { e.preventDefault(); zone.style.background = "#f8fafc"; });
                zone.addEventListener("drop", function(e) {
                    e.preventDefault();
                    zone.style.background = "#f8fafc";
                    if (e.dataTransfer.files.length) {
                        input.files = e.dataTransfer.files;
                        handleFile(input.files[0]);
                    }
                });
                input.addEventListener("change", function() {
                    if (input.files.length) handleFile(input.files[0]);
                });
                function handleFile(file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        previewImg.src = e.target.result;
                        preview.style.display = "block";
                        filenameText.textContent = file.name;
                    };
                    reader.readAsDataURL(file);
                }

                var phoneInput = document.getElementById("phone-input-field");
                if (phoneInput) {
                    phoneInput.addEventListener("input", function(e) {
                        var val = this.value.replace(/[^0-9]/g, "");
                        if (val.length > 8) val = val.substring(0, 8);
                        if (val.length > 4) {
                            this.value = val.substring(0, 4) + "-" + val.substring(4);
                        } else {
                            this.value = val;
                        }
                    });
                }
            });
        </script>';

        $this->fields_form = array(
            'legend' => array(
                'title' => $this->l('Detalles del Asesor Comercial'),
                'icon' => 'icon-user'
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Nombre'),
                    'name' => 'name',
                    'required' => true,
                    'desc' => $this->l('Nombre del vendedor (ej. Constanza, Cristian).')
                ),
                array(
                    'type' => 'free',
                    'label' => $this->l('Sector / Rol'),
                    'name' => 'sectors_checkboxes_html',
                    'required' => true,
                    'desc' => $this->l('Marca uno o varios sectores. Se formatearán automáticamente como "Ventas / Gremio".')
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Teléfono WhatsApp'),
                    'name' => 'phone_input',
                    'id' => 'phone-input-field',
                    'required' => true,
                    'prefix' => $phone_prefix,
                    'desc' => $this->l('Escribe los 8 dígitos locales. El guion se agregará automáticamente (ej. 3567-8196).')
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Correo Electrónico'),
                    'name' => 'email_user_input',
                    'required' => true,
                    'suffix' => $email_suffix,
                    'desc' => $this->l('Escribe solo el nombre de usuario de correo (ej. gabriel).')
                ),
                array(
                    'type' => 'free',
                    'label' => $this->l('Foto de Perfil (Drag & Drop)'),
                    'name' => 'drag_drop_html_field',
                ),
                array(
                    'type' => 'switch',
                    'label' => $this->l('Activo'),
                    'name' => 'active',
                    'required' => false,
                    'is_bool' => true,
                    'values' => array(
                        array('id' => 'active_on', 'value' => 1, 'label' => $this->l('Sí')),
                        array('id' => 'active_off', 'value' => 0, 'label' => $this->l('No'))
                    )
                ),
                array(
                    'type' => 'hidden',
                    'name' => 'position'
                )
            ),
            'submit' => array(
                'title' => $this->l('Guardar'),
                'class' => 'btn btn-default pull-right'
            )
        );

        $this->fields_value['sectors_checkboxes_html'] = $sectors_checkboxes_html;
        $this->fields_value['drag_drop_html_field'] = $drag_drop_html;
        $this->fields_value['phone_input'] = $clean_phone;
        $this->fields_value['email_user_input'] = $clean_email_user;

        return parent::renderForm();
    }

    public function ajaxProcessUpdatePositions()
    {
        $way = (int)Tools::getValue('way');
        $id_seller = (int)Tools::getValue('id');
        $positions = Tools::getValue('argsellers');

        if (is_array($positions)) {
            foreach ($positions as $position => $value) {
                $pos = explode('_', $value);
                if (isset($pos[2]) && (int)$pos[2] === $id_seller) {
                    if ($seller = new ArgsellerModel((int)$pos[2])) {
                        if (isset($position)) {
                            $seller->position = $position;
                            $seller->update();
                        }
                    }
                    break;
                }
            }

            $sql = 'SELECT id_seller FROM `' . _DB_PREFIX_ . 'argsellers` ORDER BY position ASC';
            $results = Db::getInstance()->executeS($sql);
            foreach ($results as $index => $row) {
                Db::getInstance()->execute('
                    UPDATE `' . _DB_PREFIX_ . 'argsellers`
                    SET `position` = ' . (int)$index . '
                    WHERE `id_seller` = ' . (int)$row['id_seller']
                );
            }
        }
    }
}
