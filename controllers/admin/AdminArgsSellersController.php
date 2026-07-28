<?php
/**
 * 2026 ARGSEGURIDAD
 * Admin controller for managing sellers in PrestaShop backoffice v2.7.0
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

    public function initPageHeaderToolbar()
    {
        parent::initPageHeaderToolbar();

        // 1-Click Update Module button with AJAX Console Modal trigger
        $this->page_header_toolbar_btn['update_module'] = array(
            'href' => 'javascript:void(0);',
            'desc' => $this->l('Actualizar Módulo'),
            'icon' => 'process-icon-refresh icon-cloud-upload',
            'js' => 'openUpdateConsoleModal();'
        );

        // Global Settings button
        $this->page_header_toolbar_btn['configure_module'] = array(
            'href' => $this->context->link->getAdminLink('AdminModules', true) . '&configure=' . $this->module->name,
            'desc' => $this->l('Ajustes Globales'),
            'icon' => 'process-icon-cogs icon-cogs',
        );
    }

    public function postProcess()
    {
        // Intercept AJAX action=ajaxUpdateModule for terminal console stream
        if (Tools::getValue('action') === 'ajaxUpdateModule') {
            $this->processAjaxUpdateModuleLog();
            exit;
        }

        // Standard HTTP fallback update action
        if (Tools::getValue('action') === 'updateModule') {
            $this->processUpdateModule();
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

    public function processAjaxUpdateModuleLog()
    {
        header('Content-Type: application/json');
        $logs = array();

        $logs[] = array('type' => 'info', 'text' => 'Iniciando proceso de actualización v2.7.0...');
        
        $github_repo = Configuration::get('ARGSELLERS_GITHUB_REPO');
        if (!$github_repo) {
            $github_repo = 'magnus2201/argsellers';
        }
        $logs[] = array('type' => 'info', 'text' => 'Repositorio GitHub: ' . $github_repo);

        $github_token = Configuration::get('ARGSELLERS_GITHUB_TOKEN');
        if (!empty($github_token)) {
            $logs[] = array('type' => 'info', 'text' => 'Token de autenticación GitHub detectado.');
        } else {
            $logs[] = array('type' => 'info', 'text' => 'Modo descarga pública (sin token token GitHub).');
        }

        $urls_to_try = array(
            'https://github.com/' . $github_repo . '/archive/refs/heads/main.zip',
            'https://raw.githubusercontent.com/' . $github_repo . '/main/argsellers.zip'
        );

        $zip_file = _PS_MODULE_DIR_ . $this->module->name . '_temp_update.zip';
        $file_downloaded = false;

        foreach ($urls_to_try as $idx => $download_url) {
            $logs[] = array('type' => 'info', 'text' => 'Probando URL (' . ($idx + 1) . '/' . count($urls_to_try) . '): ' . $download_url);

            if (function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $download_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) PrestaShop-Updater');

                if (!empty($github_token)) {
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Authorization: token ' . $github_token,
                        'Accept: application/vnd.github.v3.raw'
                    ));
                }

                $file_data = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curl_err = curl_error($ch);
                curl_close($ch);

                $logs[] = array('type' => 'info', 'text' => 'Respuesta HTTP cURL: ' . $http_code . ' (Tamaño: ' . strlen($file_data) . ' bytes)');

                if ($http_code == 200 && !empty($file_data) && strlen($file_data) > 500) {
                    file_put_contents($zip_file, $file_data);
                    $file_downloaded = true;
                    $logs[] = array('type' => 'success', 'text' => '¡ZIP descargado correctamente desde cURL!');
                    break;
                } else if ($curl_err) {
                    $logs[] = array('type' => 'error', 'text' => 'Error de cURL: ' . $curl_err);
                }
            }

            if (!$file_downloaded) {
                $logs[] = array('type' => 'warning', 'text' => 'Intentando descarga fallback via file_get_contents...');
                $opts = array(
                    'http' => array(
                        'method' => 'GET',
                        'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) PrestaShop-Updater\r\n" .
                                    (!empty($github_token) ? "Authorization: token " . $github_token . "\r\n" : "")
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
                    $logs[] = array('type' => 'success', 'text' => '¡ZIP descargado correctamente vía stream!');
                    break;
                } else {
                    $logs[] = array('type' => 'error', 'text' => 'Fallo al obtener archivo desde ' . $download_url);
                }
            }
        }

        if (file_exists($zip_file) && class_exists('ZipArchive')) {
            $logs[] = array('type' => 'info', 'text' => 'Abriendo archivo ZIP guardado...');
            $zip = new ZipArchive();
            if ($zip->open($zip_file) === true) {
                $temp_extract = _PS_MODULE_DIR_ . $this->module->name . '_extracted_temp/';
                if (!file_exists($temp_extract)) {
                    mkdir($temp_extract, 0755, true);
                }
                $zip->extractTo($temp_extract);
                $zip->close();
                $logs[] = array('type' => 'info', 'text' => 'Descompresión ZIP completada en temporal.');

                $subfolders = glob($temp_extract . '*', GLOB_ONLYDIR);
                $source_dir = $temp_extract;
                if (!empty($subfolders) && file_exists($subfolders[0] . '/argsellers.php')) {
                    $source_dir = $subfolders[0] . '/';
                    $logs[] = array('type' => 'info', 'text' => 'Directorio anidado detectado: ' . basename($subfolders[0]));
                }

                $logs[] = array('type' => 'info', 'text' => 'Copiando archivos al directorio del módulo...');
                $this->rcopy($source_dir, _PS_MODULE_DIR_ . $this->module->name . '/');

                Tools::deleteDirectory($temp_extract, true);
                @unlink($zip_file);
                $logs[] = array('type' => 'success', 'text' => '¡Archivos reemplazados exitosamente!');
            } else {
                $logs[] = array('type' => 'error', 'text' => 'No se pudo abrir el archivo ZIP descargado.');
            }
        }

        // Run PrestaShop Native Upgrade Script
        $logs[] = array('type' => 'info', 'text' => 'Ejecutando script de actualización de módulo PrestaShop (runUpgradeModule)...');
        if (method_exists($this->module, 'runUpgradeModule')) {
            $upgraded = $this->module->runUpgradeModule();
            if ($upgraded) {
                $logs[] = array('type' => 'success', 'text' => '¡Scripts upgrade/ ejecutados correctamente!');
            }
        }

        // Cache purge
        $logs[] = array('type' => 'info', 'text' => 'Limpiando cachés de Smarty, XML y Symfony...');
        Tools::clearSmartyCache();
        Tools::clearXMLCache();
        Media::clearCache();

        $cache_dir = _PS_ROOT_DIR_ . '/var/cache/';
        if (file_exists($cache_dir)) {
            Tools::deleteDirectory($cache_dir . 'dev/', false);
            Tools::deleteDirectory($cache_dir . 'prod/', false);
        }
        $logs[] = array('type' => 'success', 'text' => '¡Caché purgada con éxito!');

        if ($file_downloaded) {
            $logs[] = array('type' => 'done', 'text' => '¡ACTUALIZACIÓN COMPLETADA CON ÉXITO A LA ÚLTIMA VERSIÓN!');
        } else {
            $logs[] = array('type' => 'warning', 'text' => 'No se pudo descargar de GitHub, pero se reinstalaron los archivos locales y se limpió la caché.');
        }

        echo json_encode(array('success' => true, 'logs' => $logs));
    }

    public function processUpdateModule()
    {
        $github_repo = Configuration::get('ARGSELLERS_GITHUB_REPO');
        if (!$github_repo) {
            $github_repo = 'magnus2201/argsellers';
        }
        
        $github_token = Configuration::get('ARGSELLERS_GITHUB_TOKEN');
        
        $urls_to_try = array(
            'https://github.com/' . $github_repo . '/archive/refs/heads/main.zip',
            'https://raw.githubusercontent.com/' . $github_repo . '/main/argsellers.zip'
        );

        $zip_file = _PS_MODULE_DIR_ . $this->module->name . '_temp_update.zip';
        $file_downloaded = false;

        foreach ($urls_to_try as $download_url) {
            if (function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $download_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) PrestaShop-Updater');

                if (!empty($github_token)) {
                    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                        'Authorization: token ' . $github_token,
                        'Accept: application/vnd.github.v3.raw'
                    ));
                }

                $file_data = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($http_code == 200 && !empty($file_data) && strlen($file_data) > 500) {
                    file_put_contents($zip_file, $file_data);
                    $file_downloaded = true;
                    break;
                }
            }

            if (!$file_downloaded) {
                $opts = array(
                    'http' => array(
                        'method' => 'GET',
                        'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) PrestaShop-Updater\r\n" .
                                    (!empty($github_token) ? "Authorization: token " . $github_token . "\r\n" : "")
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
                }
            }
        }

        if (file_exists($zip_file) && class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($zip_file) === true) {
                $temp_extract = _PS_MODULE_DIR_ . $this->module->name . '_extracted_temp/';
                if (!file_exists($temp_extract)) {
                    mkdir($temp_extract, 0755, true);
                }
                $zip->extractTo($temp_extract);
                $zip->close();

                $subfolders = glob($temp_extract . '*', GLOB_ONLYDIR);
                $source_dir = $temp_extract;
                if (!empty($subfolders) && file_exists($subfolders[0] . '/argsellers.php')) {
                    $source_dir = $subfolders[0] . '/';
                }

                $this->rcopy($source_dir, _PS_MODULE_DIR_ . $this->module->name . '/');

                Tools::deleteDirectory($temp_extract, true);
                @unlink($zip_file);
            }
        }

        if (method_exists($this->module, 'runUpgradeModule')) {
            $this->module->runUpgradeModule();
        }

        Tools::clearSmartyCache();
        Tools::clearXMLCache();
        Media::clearCache();

        $cache_dir = _PS_ROOT_DIR_ . '/var/cache/';
        if (file_exists($cache_dir)) {
            Tools::deleteDirectory($cache_dir . 'dev/', false);
            Tools::deleteDirectory($cache_dir . 'prod/', false);
        }

        if ($file_downloaded) {
            $msg = $this->l('¡Módulo actualizado con éxito desde GitHub (' . $github_repo . ') y toda la caché purgada!');
            $this->context->cookie->argsellers_conf = $msg;
            $this->confirmations[] = $msg;
        } else {
            $msg = $this->l('Archivos locales del módulo aplicados y caché limpiada.');
            $this->context->cookie->argsellers_conf = $msg;
            $this->confirmations[] = $msg;
        }

        Tools::redirectAdmin(self::$currentIndex . '&conf=4&token=' . $this->token);
    }

    public function initContent()
    {
        if (isset($this->context->cookie->argsellers_conf)) {
            $this->confirmations[] = $this->context->cookie->argsellers_conf;
            unset($this->context->cookie->argsellers_conf);
        }
        parent::initContent();

        // Inject Console Log Modal in Backoffice UI
        $ajax_url = self::$currentIndex . '&action=ajaxUpdateModule&ajax=1&token=' . $this->token;
        $modal_html = '
        <div id="argsellers-console-modal" class="modal fade" tabindex="-1" role="dialog" style="display:none;">
            <div class="modal-dialog modal-lg" role="document" style="max-width: 800px; margin: 60px auto;">
                <div class="modal-content" style="background: #0f172a; color: #f8fafc; border-radius: 12px; box-shadow: 0 20px 40px rgba(0,0,0,0.5); font-family: monospace;">
                    <div class="modal-header" style="border-bottom: 1px solid #334155; padding: 15px 20px; display: flex; align-items: center; justify-content: space-between;">
                        <h4 class="modal-title" style="color: #38bdf8; font-weight: bold; margin: 0; font-size: 16px;">
                            <i class="icon-terminal"></i> Consola de Actualización en Vivo - Gestor de Vendedores
                        </h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: #94a3b8; opacity: 1; font-size: 24px; background: none; border: none; cursor: pointer;">&times;</button>
                    </div>
                    <div class="modal-body" style="padding: 20px;">
                        <div id="argsellers-console-output" style="height: 340px; overflow-y: auto; background: #020617; padding: 15px; border-radius: 8px; border: 1px solid #1e293b; font-size: 13px; line-height: 1.6;">
                            <div style="color: #64748b;">Presiona "Iniciar Actualización" para comenzar el seguimiento en tiempo real...</div>
                        </div>
                    </div>
                    <div class="modal-footer" style="border-top: 1px solid #334155; padding: 12px 20px; display: flex; justify-content: space-between; align-items: center;">
                        <span id="argsellers-console-status" style="font-size: 12px; color: #94a3b8;">Estado: Listo</span>
                        <div>
                            <button type="button" id="btn-start-console-update" class="btn btn-info" onclick="runConsoleUpdate();" style="background: #0284c7; border: none; border-radius: 6px; padding: 8px 16px; font-weight: bold;">
                                <i class="icon-refresh"></i> Iniciar Actualización
                            </button>
                            <button type="button" class="btn btn-default" data-dismiss="modal" style="background: #334155; color: #fff; border: none; border-radius: 6px; padding: 8px 16px; margin-left: 8px;" onclick="location.reload();">
                                Cerrar y Recargar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script type="text/javascript">
            function openUpdateConsoleModal() {
                $("#argsellers-console-modal").modal("show");
            }

            function runConsoleUpdate() {
                var output = document.getElementById("argsellers-console-output");
                var status = document.getElementById("argsellers-console-status");
                var btn = document.getElementById("btn-start-console-update");
                
                btn.disabled = true;
                btn.innerHTML = "<i class=\"icon-spinner icon-spin\"></i> Actualizando...";
                output.innerHTML = "<div style=\"color: #38bdf8;\">[' + new Date().toLocaleTimeString() + '] Conectando con servidor PHP PrestaShop...</div>";
                status.textContent = "Estado: Procesando descarga de GitHub...";

                $.ajax({
                    url: "' . $ajax_url . '",
                    type: "POST",
                    dataType: "json",
                    success: function(res) {
                        btn.disabled = false;
                        btn.innerHTML = "<i class=\"icon-refresh\"></i> Reintentar";
                        status.textContent = "Estado: Proceso finalizado";

                        if (res.success && res.logs) {
                            res.logs.forEach(function(log) {
                                var color = "#f8fafc";
                                if (log.type === "success") color = "#4ade80";
                                else if (log.type === "error") color = "#f87171";
                                else if (log.type === "warning") color = "#fbbf24";
                                else if (log.type === "done") color = "#38bdf8";

                                var time = new Date().toLocaleTimeString();
                                output.innerHTML += "<div style=\"color: " + color + "; margin-bottom: 4px;\">[" + time + "] " + log.text + "</div>";
                            });
                            output.scrollTop = output.scrollHeight;
                        }
                    },
                    error: function(xhr, textStatus, errorThrown) {
                        btn.disabled = false;
                        btn.innerHTML = "<i class=\"icon-refresh\"></i> Reintentar";
                        status.textContent = "Estado: Error de comunicación";
                        output.innerHTML += "<div style=\"color: #f87171;\">[' + new Date().toLocaleTimeString() + '] Error HTTP AJAX: " + textStatus + " - " + errorThrown + "</div>";
                    }
                });
            }
        </script>';

        $this->content .= $modal_html;
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
