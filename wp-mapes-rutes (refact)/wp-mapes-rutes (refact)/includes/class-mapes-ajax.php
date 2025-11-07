<?php
/**
 * Gestió de peticions AJAX per Mapes i Rutes
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Mapes_Ajax
{
    public function __construct()
    {
        $this->init_hooks();
    }

    private function init_hooks()
    {
        // Actions existents per usuaris logats
        add_action('wp_ajax_mapes_add_point', array($this, 'add_point'));
        add_action('wp_ajax_mapes_edit_point', array($this, 'edit_point'));
        add_action('wp_ajax_mapes_delete_point', array($this, 'delete_point'));
        add_action('wp_ajax_mapes_create_route', array($this, 'create_route'));
        add_action('wp_ajax_mapes_edit_route', array($this, 'edit_route'));
        add_action('wp_ajax_mapes_delete_route', array($this, 'delete_route'));

        // NOVES ACTIONS PER ACTIVITATS
        add_action('wp_ajax_mapes_create_activitat', array($this, 'create_activitat'));
        add_action('wp_ajax_mapes_validate_activitat', array($this, 'validate_activitat'));
        add_action('wp_ajax_mapes_upload_documentation', array($this, 'upload_documentation'));

        // NOVES ACCIONS PER GESTIÓ D'ACTIVACIONS
        add_action('wp_ajax_mapes_confirm_activation', array($this, 'confirm_activation'));
        add_action('wp_ajax_mapes_reject_activation', array($this, 'reject_activation'));
        add_action('wp_ajax_mapes_delete_activation', array($this, 'delete_activation'));
        add_action('wp_ajax_mapes_get_activation_details', array($this, 'get_activation_details'));

        // Actions per usuaris no logats
        add_action('wp_ajax_nopriv_mapes_add_point', array($this, 'add_point'));
        add_action('wp_ajax_nopriv_mapes_edit_point', array($this, 'edit_point'));
        add_action('wp_ajax_nopriv_mapes_delete_point', array($this, 'delete_point'));
        add_action('wp_ajax_nopriv_mapes_create_activitat', array($this, 'create_activitat'));
        add_action('wp_ajax_nopriv_mapes_validate_activitat', array($this, 'validate_activitat'));
        add_action('wp_ajax_nopriv_mapes_upload_documentation', array($this, 'upload_documentation'));

        // ⭐ NOVA ACCIÓ PER VERIFICAR DISPONIBILITAT
        add_action('wp_ajax_mapes_check_availability', array($this, 'check_availability'));
        add_action('wp_ajax_nopriv_mapes_check_availability', array($this, 'check_availability'));
    }

    private function verify_nonce()
    {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'mapes_nonce')) {
            wp_send_json_error('Verificació de seguretat fallida');
            exit;
        }
    }

    // GESTIÓ PUNTS (funcions existents)
    public function add_point()
    {
        global $wpdb;
        $tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}mapes_%'");
        error_log("TAULES TROBADES: " . print_r($tables, true));

        $this->verify_nonce();

        // ⭐ RECOLLIR TOTS ELS CAMPS NECESSARIS (NO NOMÉS 4)
        $title = sanitize_text_field($_POST['title'] ?? '');
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        $lat = floatval($_POST['lat'] ?? 0);
        $lng = floatval($_POST['lng'] ?? 0);

        // ⭐ AFEGIR AQUESTS CAMPS QUE FALTAVEN:
        $poblacio = sanitize_text_field($_POST['poblacio'] ?? '');
        $provincia = sanitize_text_field($_POST['provincia'] ?? '');

        error_log("POST REBUT: " . print_r($_POST, true));

        // VALIDACIÓ OBLIGATÒRIA
        if (empty($title)) {
            wp_send_json_error('El nom és obligatori');
            return;
        }

        // Validacions coordenades
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            wp_send_json_error('Coordenades no vàlides');
            return;
        }

        // ⭐ CRIDAR insert_point AMB TOTS ELS CAMPS
        $point_id = WP_Mapes_Database::insert_point(array(
            'title' => $title,
            'description' => $description,
            'lat' => $lat,
            'lng' => $lng,
            'poblacio' => $poblacio,
            'provincia' => $provincia
        ));

        if ($point_id) {
            wp_send_json_success(array(
                'message' => 'Monument afegit correctament',
                'point_id' => $point_id
            ));
        } else {
            wp_send_json_error('Error afegint monument a la base de dades');
        }
    }



    public function edit_point()
    {
        $this->verify_nonce();

        // ⭐ DEBUG TEMPORAL
        error_log('=== EDIT POINT DEBUG ===');
        error_log('POST data: ' . print_r($_POST, true));

        $id = intval($_POST['id'] ?? 0);
        $title = sanitize_text_field(stripslashes($_POST['title'] ?? ''));
        $description = sanitize_textarea_field(stripslashes($_POST['description'] ?? ''));
        $lat = floatval($_POST['lat'] ?? 0);
        $lng = floatval($_POST['lng'] ?? 0);

        // ⭐ DEBUG COORDENADES
        error_log("ID: $id, Title: $title");
        error_log("Coordenades rebudes: LAT=$lat, LNG=$lng");

        $dme_raw = $_POST['dme'] ?? '';
        $dme = 0;
        if (trim($dme_raw) !== '') {
            $dme = intval($dme_raw);
            if ($dme < 0) {
                wp_send_json_error('El DME ha de ser positiu si s\'indica');
                return;
            }
        }

        $poblacio = sanitize_text_field($_POST['poblacio'] ?? '');
        $provincia = sanitize_text_field($_POST['provincia'] ?? '');
        $fitxa_monument = sanitize_url($_POST['fitxa_monument'] ?? '');
        $vegades_activat = intval($_POST['vegades_activat'] ?? 0);
        $darrera_activacio = sanitize_text_field($_POST['darrera_activacio'] ?? '');
        $indicatiu_activacio = sanitize_text_field($_POST['indicatiu_activacio'] ?? '');

        if (!$id || empty($title)) {
            wp_send_json_error('ID i nom són obligatoris');
            return;
        }

        if (empty($poblacio)) {
            wp_send_json_error('La població és obligatòria');
            return;
        }

        if (!in_array($provincia, ['Barcelona', 'Girona', 'Lleida', 'Tarragona'])) {
            wp_send_json_error('Província no vàlida');
            return;
        }

        if (!empty($darrera_activacio)) {
            $datetime = DateTime::createFromFormat('Y-m-d\TH:i', $darrera_activacio);
            if (!$datetime) {
                wp_send_json_error('Format de data/hora no vàlid');
                return;
            }
            $darrera_activacio = $datetime->format('Y-m-d H:i:s');
        } else {
            $darrera_activacio = null;
        }

        $result = WP_Mapes_Database::update_point($id, array(
            'title' => $title,
            'description' => $description,
            'lat' => $lat,
            'lng' => $lng,
            'dme' => $dme,
            'poblacio' => $poblacio,
            'provincia' => $provincia,
            'fitxa_monument' => $fitxa_monument,
            'vegades_activat' => $vegades_activat,
            'darrera_activacio' => $darrera_activacio,
            'indicatiu_activacio' => $indicatiu_activacio
        ));

        // ⭐ DEBUG RESULTAT UPDATE
        error_log('Resultat update_point: ' . var_export($result, true));


        if ($result !== false) {
            wp_send_json_success('Monument actualitzat correctament');
        } else {
            wp_send_json_error('Error actualitzant monument');
        }
    }


    public function delete_point()
    {
        $this->verify_nonce();

        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            wp_send_json_error('ID de monument no vàlid');
            return;
        }

        $result = WP_Mapes_Database::delete_point($id);

        if ($result) {
            wp_send_json_success('Monument eliminat correctament');
        } else {
            wp_send_json_error('Error eliminant monument');
        }
    }

    // GESTIÓ RUTES (funcions existents)
    public function create_route()
    {
        $this->verify_nonce();

        $code = sanitize_text_field($_POST['code'] ?? '');
        $name = sanitize_text_field($_POST['name'] ?? '');
        $color = sanitize_hex_color($_POST['color'] ?? '#000000');
        $points_json = stripslashes($_POST['points'] ?? '[]');

        if (empty($code) || empty($name)) {
            wp_send_json_error('Codi i nom són obligatoris');
            return;
        }

        $points = json_decode($points_json, true);
        if (!$points || count($points) < 2) {
            wp_send_json_error('Cal mínim 2 monuments per la ruta');
            return;
        }

        $route_id = WP_Mapes_Database::insert_route(array(
            'code' => $code,
            'name' => $name,
            'color' => $color
        ));

        if ($route_id) {
            WP_Mapes_Database::insert_route_points($route_id, $points);
            wp_send_json_success(array(
                'message' => 'Ruta creada correctament',
                'route_id' => $route_id
            ));
        } else {
            wp_send_json_error('Error creant ruta');
        }
    }

    public function edit_route()
    {
        $this->verify_nonce();

        $id = intval($_POST['id'] ?? 0);
        $code = sanitize_text_field($_POST['code'] ?? '');
        $name = sanitize_text_field($_POST['name'] ?? '');
        $color = sanitize_hex_color($_POST['color'] ?? '#000000');
        $points_json = stripslashes($_POST['points'] ?? '[]');

        if (!$id || empty($code) || empty($name)) {
            wp_send_json_error('Dades obligatòries mancants');
            return;
        }

        $points = json_decode($points_json, true);
        if (!$points || count($points) < 2) {
            wp_send_json_error('Cal mínim 2 monuments per la ruta');
            return;
        }

        $result = WP_Mapes_Database::update_route($id, array(
            'code' => $code,
            'name' => $name,
            'color' => $color
        ));

        if ($result !== false) {
            WP_Mapes_Database::insert_route_points($id, $points);
            wp_send_json_success('Ruta actualitzada correctament');
        } else {
            wp_send_json_error('Error actualitzant ruta');
        }
    }

    public function delete_route()
    {
        $this->verify_nonce();

        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            wp_send_json_error('ID de ruta no vàlid');
            return;
        }

        $result = WP_Mapes_Database::delete_route($id);

        if ($result) {
            wp_send_json_success('Ruta eliminada correctament');
        } else {
            wp_send_json_error('Error eliminant ruta');
        }
    }

    // NOVES FUNCIONS PER ACTIVITATS
    public function create_activitat()
    {
        $this->verify_nonce();

        // Validar i recollir dades del formulari
        $route_id = intval($_POST['route_id'] ?? 0);
        $indicatiu = sanitize_text_field($_POST['indicatiu'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $data_activitat = sanitize_text_field($_POST['data_activitat'] ?? '');
        $referencia_dvge = sanitize_text_field($_POST['referencia_dvge'] ?? '');
        $modes = $_POST['modes'] ?? [];
        $horari = sanitize_text_field($_POST['horari'] ?? 'mati');
        $comentaris = sanitize_textarea_field($_POST['comentaris'] ?? '');
        $selected_monument = intval($_POST['selected_monument'] ?? 0);

        // Validacions
        if (!$route_id || empty($indicatiu) || empty($email) || empty($data_activitat) || empty($referencia_dvge)) {
            wp_send_json_error('Tots els camps obligatoris són necessaris');
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            wp_send_json_error('Format d\'email no vàlid');
            return;
        }

        if (!DateTime::createFromFormat('Y-m-d', $data_activitat)) {
            wp_send_json_error('Format de data no vàlid');
            return;
        }

        if (empty($modes) || !is_array($modes)) {
            wp_send_json_error('Cal seleccionar almenys un mode d\'operació');
            return;
        }

        if (!$selected_monument) {
            wp_send_json_error('Cal seleccionar un monument per activar');
            return;
        }

        // ⭐ NOVA VALIDACIÓ: Verificar disponibilitat del monument
        if ($selected_monument && $data_activitat && $horari) {
            global $wpdb;

            // Comprovar si hi ha conflicte d'horari
            $conflicte = $wpdb->get_row($wpdb->prepare("
            SELECT a.id, a.indicatiu
            FROM {$wpdb->prefix}mapes_activitats a
            INNER JOIN {$wpdb->prefix}mapes_activitat_points ap ON a.id = ap.activitat_id
            WHERE ap.point_id = %d 
            AND a.data_activitat = %s 
            AND a.horari = %s 
            AND a.status IN ('creada', 'finalitzada', 'confirmada')
        ", $selected_monument, $data_activitat, $horari));

            if ($conflicte) {
                // Obtenir nom del monument per al missatge
                $monument = $wpdb->get_row($wpdb->prepare("
                SELECT title, Poblacio 
                FROM {$wpdb->prefix}mapes_points 
                WHERE id = %d
            ", $selected_monument));

                $monument_nom = $monument ?
                    $monument->title . ' (' . $monument->Poblacio . ')' :
                    'Monument seleccionat';

                wp_send_json_error("❌ El monument '{$monument_nom}' ja està ocupat per l'activació '{$conflicte->indicatiu}' el dia {$data_activitat} a l'horari '{$horari}'. Si us plau, seleccioneu una altra data o horari.");
                return;
            }
        }

        // Data no pot ser anterior a avui
        $data_activitat_obj = DateTime::createFromFormat('Y-m-d', $data_activitat);
        $avui = new DateTime();
        $avui->setTime(0, 0, 0); // Eliminar hores per comparar només dates

        if (!$data_activitat_obj) {
            wp_send_json_error('Format de data no vàlid');
            return;
        }

        if ($data_activitat_obj < $avui) {
            wp_send_json_error('La data d\'activitat no pot ser anterior a avui');
            return;
        }

        // Crear activitat
        $activitat_data = array(
            'route_id' => $route_id,
            'indicatiu' => $indicatiu,
            'email' => $email,
            'data_activitat' => $data_activitat,
            'referencia_dvge' => $referencia_dvge,
            'modes' => $modes,
            'horari' => $horari,
            'comentaris' => $comentaris
        );

        $result = WP_Mapes_Database::insert_activitat($activitat_data);

        // ✅ GUARDAR EL MONUMENT SELECCIONAT (CORREGIT)
        if ($result && $selected_monument) {
            $point_ids = array($selected_monument);
            WP_Mapes_Database::insert_activitat_points($result['id'], $point_ids);
            error_log("MONUMENT GUARDAT PER ACTIVITAT {$result['id']}: {$selected_monument}");
        }

        if ($result && isset($result['activation_code'])) {
            // Obtenir informació de la ruta per l'email
            $routes = WP_Mapes_Database::get_routes();
            $route_name = '';
            foreach ($routes as $route) {
                if ($route->id == $route_id) {
                    $route_name = $route->name;
                    break;
                }
            }

            // Enviar email de confirmació
            $email_sent = WP_Mapes_Database::send_activation_email(
                $email,
                $result['activation_code'],
                $route_name,
                $indicatiu,
                $selected_monument
            );

            wp_send_json_success(array(
                'message' => 'Activitat creada correctament',
                'activation_code' => $result['activation_code'],
                'email_sent' => $email_sent,
                'activitat_id' => $result['id']
            ));
        } else {
            wp_send_json_error('Error creant l\'activitat a la base de dades');
        }
    }

    /**
     * Verificar disponibilitat d'un monument en una data i horari específics
     */
    public function check_availability()
    {
        $this->verify_nonce();

        $point_id = intval($_POST['point_id'] ?? 0);
        $data = sanitize_text_field($_POST['data'] ?? '');
        $horari = sanitize_text_field($_POST['horari'] ?? '');

        if (!$point_id || !$data || !$horari) {
            wp_send_json_error('Dades incompletes');
            return;
        }

        global $wpdb;

        $conflicte = $wpdb->get_row($wpdb->prepare("
        SELECT a.id, a.indicatiu, p.title, p.Poblacio
        FROM {$wpdb->prefix}mapes_activitats a
        INNER JOIN {$wpdb->prefix}mapes_activitat_points ap ON a.id = ap.activitat_id
        INNER JOIN {$wpdb->prefix}mapes_points p ON ap.point_id = p.id
        WHERE ap.point_id = %d 
        AND a.data_activitat = %s 
        AND a.horari = %s 
        AND a.status IN ('creada', 'finalitzada', 'confirmada')
        LIMIT 1
    ", $point_id, $data, $horari));

        if ($conflicte) {
            $monument_nom = $conflicte->title;
            if ($conflicte->Poblacio) {
                $monument_nom .= ' (' . $conflicte->Poblacio . ')';
            }

            wp_send_json_error("Monument '{$monument_nom}' ja ocupat el {$data} ({$horari}) per l'activació '{$conflicte->indicatiu}'");
        } else {
            wp_send_json_success('Monument disponible');
        }
    }




    public function validate_activitat()
    {
        $this->verify_nonce();

        $email = sanitize_email($_POST['email'] ?? '');
        $code = sanitize_text_field($_POST['activation_code'] ?? '');

        if (empty($email) || empty($code)) {
            wp_send_json_error('Email i codi d\'activació són obligatoris');
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            wp_send_json_error('Format d\'email no vàlid');
            return;
        }

        // Verificar email i codi
        $activitat = WP_Mapes_Database::get_activitat_by_email_code($email, $code);

        if (!$activitat) {
            wp_send_json_error('Combinació d\'email i codi d\'activació no trobada');
            return;
        }
        //Comprovar que ja ha passat la data d'activitat
        $data_activitat = DateTime::createFromFormat('Y-m-d', $activitat->data_activitat);
        $avui = new DateTime();
        $avui->setTime(0, 0, 0);

        if ($data_activitat > $avui) {
            $dies_restants = $avui->diff($data_activitat)->days;
            wp_send_json_error("No podeu finalitzar l'activitat fins que passi la data programada ({$activitat->data_activitat}). Resten $dies_restants dies.");
            return;
        }

        if ($activitat->status === 'finalitzada') {
            wp_send_json_error('Aquesta activitat ja ha estat finalitzada');
            return;
        }

        wp_send_json_success(array(
            'message' => 'Activitat validada correctament',
            'activitat' => $activitat,
            'can_upload' => true,
            'redirect_to_docs' => true
        ));
    }

    public function upload_documentation()
    {
        $this->verify_nonce();

        $activitat_id = intval($_POST['activitat_id'] ?? 0);

        if (!$activitat_id) {
            wp_send_json_error('ID d\'activitat no vàlid');
            return;
        }

        // Crear directori si no existeix
        $upload_dir = wp_upload_dir();
        $mapes_dir = $upload_dir['basedir'] . '/mapes-activitats/';

        if (!file_exists($mapes_dir)) {
            wp_mkdir_p($mapes_dir);
        }

        $uploaded_files = array();

        // Processar fitxer ADI
        if (isset($_FILES['fitxer_adi']) && $_FILES['fitxer_adi']['error'] === UPLOAD_ERR_OK) {
            $adi_info = pathinfo($_FILES['fitxer_adi']['name']);
            if (strtolower($adi_info['extension']) === 'adi') {
                $adi_filename = 'adi_' . $activitat_id . '_' . time() . '.adi';
                if (move_uploaded_file($_FILES['fitxer_adi']['tmp_name'], $mapes_dir . $adi_filename)) {
                    $uploaded_files['adi'] = $adi_filename;
                }
            }
        }

        // Processar imatges (fins a 5, < 600KB cada una)
        $uploaded_images = array();
        for ($i = 0; $i < 5; $i++) {
            if (isset($_FILES["imatge_$i"]) && $_FILES["imatge_$i"]['error'] === UPLOAD_ERR_OK) {
                $image_size = $_FILES["imatge_$i"]['size'];
                if ($image_size > 600 * 1024) { // 600KB
                    wp_send_json_error("La imatge " . ($i + 1) . " supera els 600KB");
                    return;
                }

                $image_info = pathinfo($_FILES["imatge_$i"]['name']);
                $allowed_extensions = array('jpg', 'jpeg', 'png');

                if (in_array(strtolower($image_info['extension']), $allowed_extensions)) {
                    $image_filename = 'img_' . $activitat_id . '_' . $i . '_' . time() . '.' . $image_info['extension'];
                    if (move_uploaded_file($_FILES["imatge_$i"]['tmp_name'], $mapes_dir . $image_filename)) {
                        $uploaded_images[] = $image_filename;
                    }
                }
            }
        }
        $uploaded_files['imatges'] = $uploaded_images;

        // Processar PDF
        if (isset($_FILES['fitxer_pdf']) && $_FILES['fitxer_pdf']['error'] === UPLOAD_ERR_OK) {
            $pdf_info = pathinfo($_FILES['fitxer_pdf']['name']);
            if (strtolower($pdf_info['extension']) === 'pdf') {
                $pdf_filename = 'pdf_' . $activitat_id . '_' . time() . '.pdf';
                if (move_uploaded_file($_FILES['fitxer_pdf']['tmp_name'], $mapes_dir . $pdf_filename)) {
                    $uploaded_files['pdf'] = $pdf_filename;
                }
            }
        }

        // Actualitzar activitat amb fitxers i canviar estat a finalitzada
        $result = WP_Mapes_Database::update_activitat_documentation($activitat_id, $uploaded_files);

        if ($result !== false) {
            wp_send_json_success(array(
                'message' => 'Documentació enviada correctament',
                'uploaded_files' => $uploaded_files,
                'status' => 'finalitzada'
            ));
        } else {
            wp_send_json_error('Error actualitzant la base de dades');
        }
    }
    /**
     * Confirmar activació via AJAX
     */
    public function confirm_activation()
    {
        $this->verify_nonce();

        if (!current_user_can('manage_options')) {
            wp_send_json_error('No tens permisos per confirmar activacions');
            return;
        }

        $activation_id = intval($_POST['id'] ?? 0);
        if (!$activation_id) {
            wp_send_json_error('ID d\'activació no vàlid');
            return;
        }

        // 🔍 DEBUG: VEURE QUÈ HI HA A LA TAULA
        global $wpdb;
        $table = $wpdb->prefix . 'mapes_activitats';

        // Buscar l'activació sense condició d'estat
        $activation = $wpdb->get_row($wpdb->prepare("
        SELECT * FROM {$table}
        WHERE id = %d
    ", $activation_id));

        // Log detallat per debugging
        error_log("🔍 DEBUG confirm_activation:");
        error_log("- ID buscat: " . $activation_id);
        error_log("- Taula: " . $table);
        error_log("- Activació trobada: " . ($activation ? 'SÍ' : 'NO'));

        if ($activation) {
            error_log("- Estat actual: " . $activation->status);
            error_log("- Email: " . $activation->email);
            error_log("- Data: " . $activation->created_at);
        } else {
            // Llistar totes les activacions per veure què hi ha
            $all_activations = $wpdb->get_results("SELECT id, status, email FROM {$table} ORDER BY id DESC LIMIT 10");
            error_log("- Activacions existents:");
            foreach ($all_activations as $act) {
                error_log("  ID: {$act->id}, Status: {$act->status}, Email: {$act->email}");
            }
        }

        if (!$activation) {
            wp_send_json_error('Activació no trobada (ID: ' . $activation_id . ')');
            return;
        }

        // Verificar estat
        if (!in_array($activation->status, ['creada', 'finalitzada'])) {
            wp_send_json_error('Activació no es pot confirmar. Estat actual: ' . $activation->status);
            return;
        }

        // Continuar amb el codi original...
        $result = WP_Mapes_Database::confirm_activation($activation_id);

        if ($result === false) {
            wp_send_json_error('Error confirmant activació a la base de dades');
            return;
        }

        error_log("Activació #{$activation_id} confirmada per l'usuari #" . get_current_user_id());

        wp_send_json_success(array(
            'message' => 'Activació confirmada correctament',
            'activation_id' => $activation_id
        ));
    }


    /**
     * Rebutjar activació via AJAX
     */
    public function reject_activation()
    {
        try {
            $this->verify_nonce();

            if (!current_user_can('manage_options')) {
                wp_send_json_error('No tens permisos per rebutjar activacions');
                return;
            }

            $activation_id = intval($_POST['id'] ?? 0);
            $reason = sanitize_textarea_field($_POST['reason'] ?? '');

            if (!$activation_id) {
                wp_send_json_error('ID d\'activació no vàlid');
                return;
            }

            // Verificar que l'activació existeix i està pendent
            global $wpdb;
            $activation = $wpdb->get_row($wpdb->prepare("
    SELECT * FROM {$wpdb->prefix}mapes_activitats  // ✅ NOVA
    WHERE id = %d AND status IN ('creada', 'finalitzada')
", $activation_id));

            if (!$activation) {
                wp_send_json_error('Activació no trobada o ja processada');
                return;
            }

            // Rebutjar l'activació
            $result = WP_Mapes_Database::reject_activation($activation_id, $reason);

            if ($result === false) {
                wp_send_json_error('Error rebutjant activació a la base de dades');
                return;
            }

            // Log de l'acció
            $user_id = get_current_user_id();
            error_log("Activació #{$activation_id} rebutjada per l'usuari #{$user_id}. Motiu: {$reason}");

            wp_send_json_success(array(
                'message' => 'Activació rebutjada correctament',
                'activation_id' => $activation_id,
                'reason' => $reason
            ));

        } catch (Exception $e) {
            error_log('Error rebutjant activació: ' . $e->getMessage());
            wp_send_json_error('Error intern del servidor');
        }
    }

    /**
     * Esborrar activació via AJAX
     */
    public function delete_activation()
    {
        try {
            $this->verify_nonce();

            if (!current_user_can('manage_options')) {
                wp_send_json_error('No tens permisos per esborrar activacions');
                return;
            }

            $activation_id = intval($_POST['id'] ?? 0);
            if (!$activation_id) {
                wp_send_json_error('ID d\'activació no vàlid');
                return;
            }

            // Verificar que l'activació existeix
            global $wpdb;
            $activation = $wpdb->get_row($wpdb->prepare("
    SELECT * FROM {$wpdb->prefix}mapes_activitats  // ✅ NOVA
    WHERE id = %d AND status IN ('creada', 'finalitzada')
", $activation_id));

            if (!$activation) {
                wp_send_json_error('Activació no trobada o ja esborrada');
                return;
            }

            // Esborrar l'activació (soft delete)
            $result = WP_Mapes_Database::delete_activation($activation_id);

            if ($result === false) {
                wp_send_json_error('Error esborrant activació a la base de dades');
                return;
            }

            // Log de l'acció
            error_log("Activació #{$activation_id} esborrada per l'usuari #" . get_current_user_id());

            wp_send_json_success(array(
                'message' => 'Activació esborrada correctament',
                'activation_id' => $activation_id
            ));

        } catch (Exception $e) {
            error_log('Error esborrant activació: ' . $e->getMessage());
            wp_send_json_error('Error intern del servidor');
        }
    }

    /**
     * Obtenir detalls d'una activació via AJAX
     */
    /**
     * Obtenir detalls d'una activació via AJAX - VERSIÓ AMPLIADA
     */
    public function get_activation_details()
    {
        try {
            $this->verify_nonce();

            if (!current_user_can('manage_options')) {
                wp_send_json_error('No tens permisos per veure detalls d\'activacions');
                return;
            }

            $activation_id = intval($_POST['id'] ?? 0);
            if (!$activation_id) {
                wp_send_json_error('ID d\'activació no vàlid');
                return;
            }

            global $wpdb;
            $activations_table = $wpdb->prefix . 'mapes_activitats';
            $routes_table = $wpdb->prefix . 'mapes_routes';
            $activation_points_table = $wpdb->prefix . 'mapes_activitat_points';
            $route_points_table = $wpdb->prefix . 'mapes_route_points';
            $points_table = $wpdb->prefix . 'mapes_points';

            // ⭐ OBTENIR DADES BÀSIQUES AMB USER_NAME CORRECTE
            $activation = $wpdb->get_row($wpdb->prepare("
            SELECT 
                a.*,
                COALESCE(NULLIF(a.indicatiu,''), a.email, 'Usuari desconegut') as user_name,
                COALESCE(CONCAT(r.code, ' - ', r.name), CONCAT('Ruta-', a.route_id)) as route_code,
                r.name as route_name,
                r.color as route_color
            FROM {$activations_table} a
            LEFT JOIN {$routes_table} r ON a.route_id = r.id
            WHERE a.id = %d
        ", $activation_id));

            if (!$activation) {
                wp_send_json_error('Activació no trobada');
                return;
            }

            // ⭐ OBTENIR PUNTS ACTIVATS AMB DETALLS COMPLETS
            $activated_points = $wpdb->get_results($wpdb->prepare("
            SELECT 
                p.*,
                rp.weight,
                ap.created_at as activation_date
            FROM {$activation_points_table} ap
            JOIN {$points_table} p ON ap.point_id = p.id
            LEFT JOIN {$route_points_table} rp ON p.id = rp.point_id AND rp.route_id = %d
            WHERE ap.activitat_id = %d
            ORDER BY p.title ASC
        ", $activation->route_id, $activation_id));

            // ⭐ CALCULAR ESTADÍSTIQUES
            $total_points_activated = count($activated_points);
            $total_weight_activated = array_sum(array_column($activated_points, 'weight'));

            // ⭐ OBTENIR ESTADÍSTIQUES DE LA RUTA
            $route_stats = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(DISTINCT rp.point_id) as total_route_points,
                COALESCE(SUM(rp.weight), 0) as total_route_weight
            FROM {$route_points_table} rp
            WHERE rp.route_id = %d
        ", $activation->route_id));

            // ⭐ PROCESSAR DOCUMENTS
            $documents = [];
            if (!empty($activation->fitxer_adii)) {
                $documents[] = (object) [
                    'file_name' => $activation->fitxer_adii,
                    'file_url' => '/wp-content/uploads/mapes-activitats/' . $activation->fitxer_adii,
                    'type' => 'image'
                ];
            }

            if (!empty($activation->fitxer_pdf)) {
                $documents[] = (object) [
                    'file_name' => $activation->fitxer_pdf,
                    'file_url' => '/wp-content/uploads/mapes-activitats/' . $activation->fitxer_pdf,
                    'type' => 'pdf'
                ];
            }

            if (!empty($activation->imatges)) {
                $imatges = explode(',', $activation->imatges);
                foreach ($imatges as $imatge) {
                    if (trim($imatge)) {
                        $documents[] = (object) [
                            'file_name' => trim($imatge),
                            'file_url' => '/wp-content/uploads/mapes-activitats/' . trim($imatge),
                            'type' => 'image'
                        ];
                    }
                }
            }

            // ⭐ AFEGIR TOTES LES DADES CALCULADES
            $activation->points_count = $total_points_activated;
            $activation->total_weight = $total_weight_activated;
            $activation->total_route_points = $route_stats->total_route_points ?? 0;
            $activation->total_route_weight = $route_stats->total_route_weight ?? 0;

            wp_send_json_success(array(
                'activation' => $activation,
                'activated_points' => $activated_points,
                'documents' => $documents,
                'stats' => array(
                    'points_activated' => $total_points_activated,
                    'total_route_points' => $route_stats->total_route_points ?? 0,
                    'weight_obtained' => $total_weight_activated,
                    'total_route_weight' => $route_stats->total_route_weight ?? 0,
                    'completion_percentage' => $route_stats->total_route_points > 0 ?
                        round(($total_points_activated / $route_stats->total_route_points) * 100, 1) : 0
                )
            ));

        } catch (Exception $e) {
            error_log('Error obtenint detalls d\'activació: ' . $e->getMessage());
            wp_send_json_error('Error intern del servidor');
        }
    }



}