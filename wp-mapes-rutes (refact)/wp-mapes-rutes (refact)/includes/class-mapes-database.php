<?php
/**
 * Gestió de base de dades per Mapes i Rutes
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Mapes_Database
{
    private static $tables_created = false;

    public static function create_tables()
    {
        // Debug per saber que la funció s'executa
        error_log('Executant create_tables()');
        global $wpdb;
        $tables = $wpdb->get_results("SHOW TABLES LIKE '{$wpdb->prefix}mapes_%'");

        // Debug per veure les taules existents abans de crear de noves
        error_log("TAULES EXISTENTS: " . print_r($tables, true));

        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // CREAR TAULA DE RUTES MANUALMENT (per evitar error dbDelta)
        $routes_table = $wpdb->prefix . 'mapes_routes';
        $table_exists_routes = $wpdb->get_var("SHOW TABLES LIKE '$routes_table'");

        if ($table_exists_routes != $routes_table) {
            $routes_sql = "CREATE TABLE $routes_table (
                id int(11) NOT NULL AUTO_INCREMENT,
                code varchar(50) NOT NULL,
                name varchar(255) NOT NULL,
                color varchar(7) DEFAULT '#000000',
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY code_unique (code)
            ) $charset_collate";

            $result = $wpdb->query($routes_sql);
            if ($result === false) {
                error_log('ERROR CREANT TAULA ROUTES: ' . $wpdb->last_error);
            } else {
                error_log('TAULA ROUTES CREADA CORRECTAMENT');
            }
        } else {
            error_log('TAULA ROUTES JA EXISTEIX');
        }

        // Taula de monuments (funciona amb dbDelta)
        $points_table = $wpdb->prefix . 'mapes_points';
        $points_sql = "CREATE TABLE $points_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            lat decimal(10, 6) NOT NULL,
            lng decimal(10, 6) NOT NULL,
            DME int(11) NOT NULL,
            Poblacio varchar(280) NOT NULL,
            Provincia varchar(140) NOT NULL,
            Fitxa_Monument varchar(500) NOT NULL,
            Vegades_activat int(11) NOT NULL DEFAULT 0,
            Darrera_Activacio datetime NULL,
            Indicatiu_activacio varchar(300) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY lat_lng (lat, lng),
            KEY DME (DME)
        ) $charset_collate;";

        // Taula de relació ruta-monuments (funciona amb dbDelta)
        $route_points_table = $wpdb->prefix . 'mapes_route_points';
        $route_points_sql = "CREATE TABLE $route_points_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            route_id int(11) NOT NULL,
            point_id int(11) NOT NULL,
            order_num int(11) NOT NULL DEFAULT 1,
            weight decimal(5, 2) DEFAULT 1.00,
            PRIMARY KEY (id),
            KEY route_id (route_id),
            KEY point_id (point_id),
            KEY route_order (route_id, order_num)
        ) $charset_collate;";

        // Crear amb dbDelta (només monuments i relacions)
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($points_sql);
        dbDelta($route_points_sql);

        // CREAR TAULA D'ACTIVITATS MANUALMENT (dbDelta falla amb aquesta)
        $activitats_table = $wpdb->prefix . 'mapes_activitats';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$activitats_table'");

        if ($table_exists != $activitats_table) {
            $activitats_sql = "CREATE TABLE $activitats_table (
    id int(11) NOT NULL AUTO_INCREMENT,
    route_id int(11) NOT NULL,
    user_id int(11) DEFAULT NULL,
    activation_code varchar(10) NOT NULL,
    indicatiu varchar(300) NOT NULL,
    email varchar(255) NOT NULL,
    data_activitat date NOT NULL,
    referencia_dvge varchar(100) NOT NULL,
    modes_operacio text NOT NULL,
    horari varchar(20) NOT NULL DEFAULT 'mati',
    comentaris text DEFAULT NULL,
    status varchar(20) NOT NULL DEFAULT 'creada',
    fitxer_adi varchar(255) DEFAULT NULL,
    imatges text DEFAULT NULL,
    fitxer_pdf varchar(255) DEFAULT NULL,
    confirmed_at datetime DEFAULT NULL,
    confirmed_by int(11) DEFAULT NULL,
    rejected_at datetime DEFAULT NULL,
    rejected_by int(11) DEFAULT NULL,
    rejection_reason text DEFAULT NULL,
    deleted_at datetime DEFAULT NULL,
    deleted_by int(11) DEFAULT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY activation_code (activation_code),
    KEY route_id (route_id),
    KEY email_code (email, activation_code),
    KEY status_date (status, data_activitat),
    KEY user_id (user_id)
) $charset_collate";


            $result = $wpdb->query($activitats_sql);
            if ($result === false) {
                error_log('ERROR CREANT TAULA ACTIVITATS: ' . $wpdb->last_error);
                error_log('SQL EXECUTADA: ' . $activitats_sql);
            } else {
                error_log('TAULA ACTIVITATS CREADA CORRECTAMENT');
            }
        } else {
            // ⭐ AFEGIR AQUESTA PART TAMBÉ!
            error_log('TAULA ACTIVITATS JA EXISTEIX');
        }

        $activitat_points_table = $wpdb->prefix . 'mapes_activitat_points';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$activitat_points_table'");

        if ($table_exists != $activitat_points_table) {
            $activitat_points_sql = "CREATE TABLE $activitat_points_table (
        id int(11) NOT NULL AUTO_INCREMENT,
        activitat_id int(11) NOT NULL,
        point_id int(11) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY activitat_id (activitat_id),
        KEY point_id (point_id),
        UNIQUE KEY activitat_point (activitat_id, point_id)
    ) $charset_collate";

            $result = $wpdb->query($activitat_points_sql);

            if ($result === false) {
                error_log('ERROR CREANT TAULA ACTIVITAT_POINTS: ' . $wpdb->last_error);
                error_log('SQL EXECUTADA: ' . $activitat_points_sql);
            } else {
                error_log('TAULA ACTIVITAT_POINTS CREADA CORRECTAMENT');
            }
        } else {
            error_log('TAULA ACTIVITAT_POINTS JA EXISTEIX');
        }

        // AFEGIR AQUESTES NOVES SECCIONS AQUÍ:

        // CREAR TAULA D'ACTIVACIONS MANUALMENT (per gestió d'administrador)
        $activacions_table = $wpdb->prefix . 'mapes_activacions';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$activacions_table'");

        if ($table_exists != $activacions_table) {
            $activacions_sql = "CREATE TABLE $activacions_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            route_id int(11) NOT NULL,
            user_id int(11) NULL,
            activation_code varchar(10) NOT NULL,
            indicatiu varchar(300) NOT NULL,
            email varchar(255) NOT NULL,
            data_activitat date NOT NULL,
            referencia_dvge varchar(100) NOT NULL,
            modes_operacio text NOT NULL,
            horari varchar(20) NOT NULL DEFAULT 'mati',
            comentaris text,
            status varchar(20) NOT NULL DEFAULT 'creada',
            fitxer_adi varchar(255),
            imatges text,
            fitxer_pdf varchar(255),
            confirmed_at datetime NULL,
            confirmed_by int(11) NULL,
            rejected_at datetime NULL,
            rejected_by int(11) NULL,
            rejection_reason text,
            deleted_at datetime NULL,
            deleted_by int(11) NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY activation_code (activation_code),
            KEY route_id (route_id),
            KEY email_code (email, activation_code),
            KEY status_date (status, data_activitat),
            KEY user_id (user_id)
        ) $charset_collate";

            $result = $wpdb->query($activacions_sql);
            if ($result === false) {
                error_log('ERROR CREANT TAULA ACTIVACIONS: ' . $wpdb->last_error);
            } else {
                error_log('TAULA ACTIVACIONS CREADA CORRECTAMENT');
            }
        } else {
            error_log('TAULA ACTIVACIONS JA EXISTEIX');
        }

        // CREAR TAULA DE DOCUMENTS D'ACTIVACIONS
        $documents_table = $wpdb->prefix . 'mapes_activitat_documents';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$documents_table'");

        if ($table_exists != $documents_table) {
            $documents_sql = "CREATE TABLE $documents_table (
            id int(11) NOT NULL AUTO_INCREMENT,
            activitat_id int(11) NOT NULL,
            file_name varchar(255) NOT NULL,
            file_path varchar(500) NOT NULL,
            file_url varchar(500) NOT NULL,
            file_type varchar(50) NOT NULL,
            file_size int(11) NOT NULL,
            uploaded_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY activitat_id (activitat_id)
        ) $charset_collate";

            $result = $wpdb->query($documents_sql);
            if ($result === false) {
                error_log('ERROR CREANT TAULA DOCUMENTS: ' . $wpdb->last_error);
            } else {
                error_log('TAULA DOCUMENTS CREADA CORRECTAMENT');
            }
        } else {
            error_log('TAULA DOCUMENTS JA EXISTEIX');
        }

        // ⭐ MIGRACIÓ DE DADES (si cal)
        self::migrate_activitats_to_activacions();
        self::$tables_created = true;
    }

    // FUNCIÓ MODIFICADA: get_points sense dependencies de routes
    public static function get_points($limit = null)
    {
        global $wpdb;
        $points_table = $wpdb->prefix . 'mapes_points';
        $route_points_table = $wpdb->prefix . 'mapes_route_points';
        $routes_table = $wpdb->prefix . 'mapes_routes';

        $sql = "SELECT p.*, GROUP_CONCAT(r.code ORDER BY r.code SEPARATOR ', ') as route_codes
            FROM $points_table p
            LEFT JOIN $route_points_table rp ON p.id = rp.point_id
            LEFT JOIN $routes_table r ON rp.route_id = r.id
            GROUP BY p.id
            ORDER BY p.created_at DESC";

        if ($limit) {
            $sql .= $wpdb->prepare(" LIMIT %d", $limit);
        }

        $points = $wpdb->get_results($sql);

        // Afegir camp calculat
        foreach ($points as $point) {
            $point->pertany_ruta = !empty($point->route_codes) ? $point->route_codes : 'Sense ruta';
        }

        // ⚡ DEBUG MANUAL - AFEGIR ACTIVATION_STATUS DIRECTAMENT AQUÍ
        error_log("🔍 ADDING activation_status MANUAL...");
        foreach ($points as $point) {
            $vegades_activat = intval($point->Vegades_activat ?? 0);

            if ($vegades_activat === 0) {
                $point->activation_status = 'never_activated';
            } else {
                $point->activation_status = 'confirmed';
            }

            error_log("🎨 Monument '{$point->title}': Vegades={$vegades_activat}, Status={$point->activation_status}");
        }

        return $points;
    }


    // FUNCIÓ MODIFICADA: get_point sense dependencies de routes
    public static function get_point($id)
    {
        global $wpdb;
        $points_table = $wpdb->prefix . 'mapes_points';
        $routes_table = $wpdb->prefix . 'mapes_routes';

        // COMPROVAR SI LA TAULA ROUTES EXISTEIX
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$routes_table'");

        if ($table_exists == $routes_table) {
            // Si existeix routes, usar query completa
            $route_points_table = $wpdb->prefix . 'mapes_route_points';

            $point = $wpdb->get_row($wpdb->prepare(
                "SELECT p.*, 
                        GROUP_CONCAT(r.code ORDER BY r.code SEPARATOR ', ') as route_codes
                 FROM $points_table p
                 LEFT JOIN $route_points_table rp ON p.id = rp.point_id
                 LEFT JOIN $routes_table r ON rp.route_id = r.id
                 WHERE p.id = %d
                 GROUP BY p.id",
                $id
            ));
        } else {
            // Si no existeix routes, query simple
            $point = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $points_table WHERE id = %d",
                $id
            ));
        }

        if ($point) {
            $point->pertany_ruta = (!empty($point->route_codes)) ? $point->route_codes : 'Sense ruta';
        }

        return $point;
    }

    public static function insert_point($data)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'mapes_points';

        $result = $wpdb->insert(
            $table,
            array(
                'title' => sanitize_text_field($data['title']),
                'description' => sanitize_textarea_field($data['description'] ?? ''),
                'lat' => floatval($data['lat']),
                'lng' => floatval($data['lng']),
                // ⭐ CORREGIR AQUESTS CAMPS PER USAR LES DADES DEL FORMULARI
                'DME' => !empty($data['dme']) ? intval($data['dme']) : 0,
                'Poblacio' => sanitize_text_field($data['poblacio'] ?? 'No especificada'),
                'Provincia' => sanitize_text_field($data['provincia'] ?? 'Barcelona'),
                'Fitxa_Monument' => sanitize_url($data['fitxa_monument'] ?? ''),
                'Vegades_activat' => intval($data['vegades_activat'] ?? 0),
                'Darrera_Activacio' => $data['darrera_activacio'] ?? null,
                'Indicatiu_activacio' => sanitize_text_field($data['indicatiu_activacio'] ?? '')
            ),
            array('%s', '%s', '%f', '%f', '%d', '%s', '%s', '%s', '%d', '%s', '%s')
        );

        if ($result === false) {
            error_log('ERROR INSERT PUNT: ' . $wpdb->last_error);
            error_log('SQL QUERY: ' . $wpdb->last_query);
        }

        return $result ? $wpdb->insert_id : false;
    }

    // FUNCIÓ UPDATE_POINT ACTUALITZADA AMB NOUS CAMPS
    public static function update_point($id, $data)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'mapes_points';

        // ⭐ DEBUG TEMPORAL
        error_log('=== UPDATE_POINT DEBUG ===');
        error_log("ID a actualitzar: $id");
        error_log('Data rebuda: ' . print_r($data, true));

        return $wpdb->update(
            $table,
            array(
                'title' => sanitize_text_field($data['title']),
                'description' => sanitize_textarea_field($data['description'] ?? ''),
                'lat' => floatval($data['lat']),
                'lng' => floatval($data['lng']),
                'DME' => isset($data['dme']) ? intval($data['dme']) : 0,  // Usar isset() en lloc de !empty()
                'Poblacio' => sanitize_text_field($data['poblacio']),
                'Provincia' => sanitize_text_field($data['provincia']),
                'Fitxa_Monument' => sanitize_url($data['fitxa_monument']),
                'Vegades_activat' => intval($data['vegades_activat'] ?? 0),
                'Darrera_Activacio' => $data['darrera_activacio'] ?? null,
                'Indicatiu_activacio' => sanitize_text_field($data['indicatiu_activacio'])
            ),
            array('id' => $id),
            array('%s', '%s', '%f', '%f', '%d', '%s', '%s', '%s', '%d', '%s', '%s'),
            array('%d')
        );

        // ⭐ DEBUG RESULTAT SQL
        error_log('SQL executada: ' . $wpdb->last_query);
        error_log('Resultat SQL: ' . var_export($result, true));
        error_log('Error SQL: ' . $wpdb->last_error);

        return $result;
    }

    public static function delete_point($id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'mapes_points';

        return $wpdb->delete($table, array('id' => $id), array('%d'));
    }

    // OPERACIONS RUTES (SEGURES)
    public static function get_routes($with_points = true)
    {
        global $wpdb;
        $routes_table = $wpdb->prefix . 'mapes_routes';

        // COMPROVAR SI LA TAULA EXISTEIX
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$routes_table'");
        if ($table_exists != $routes_table) {
            error_log('TAULA ROUTES NO EXISTEIX - Retornant array buit');
            return array();
        }

        $routes = $wpdb->get_results("SELECT * FROM $routes_table ORDER BY created_at DESC");

        if ($with_points && $routes) {
            $route_points_table = $wpdb->prefix . 'mapes_route_points';
            $points_table = $wpdb->prefix . 'mapes_points';

            foreach ($routes as $route) {
                $route->points = $wpdb->get_results($wpdb->prepare("
                SELECT rp.*, p.title, p.lat, p.lng 
                FROM $route_points_table rp
                JOIN $points_table p ON rp.point_id = p.id
                WHERE rp.route_id = %d
                ORDER BY rp.order_num ASC
            ", $route->id));

                // ⭐ DEBUG TEMPORAL - Comprova els pesos que es carreguen
                if (!empty($route->points) && $route->code == '4r343432') {
                    error_log("RUTA {$route->code}: DEBUG PUNTS:");
                    foreach ($route->points as $point) {
                        error_log("  - {$point->title}: pes={$point->weight}");
                    }
                }
            }
        }

        return $routes;
    }


    // RESTA DE FUNCIONS IGUALS...
    public static function insert_route($data)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'mapes_routes';

        $result = $wpdb->insert(
            $table,
            array(
                'code' => sanitize_text_field($data['code']),
                'name' => sanitize_text_field($data['name']),
                'color' => sanitize_hex_color($data['color'] ?? '#000000')
            ),
            array('%s', '%s', '%s')
        );

        return $result ? $wpdb->insert_id : false;
    }

    public static function update_route($id, $data)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'mapes_routes';

        return $wpdb->update(
            $table,
            array(
                'code' => sanitize_text_field($data['code']),
                'name' => sanitize_text_field($data['name']),
                'color' => sanitize_hex_color($data['color'] ?? '#000000')
            ),
            array('id' => $id),
            array('%s', '%s', '%s'),
            array('%d')
        );
    }
    public static function get_route_points($route_id)
    {
        global $wpdb;
        $route_points_table = $wpdb->prefix . 'mapes_route_points';
        $points_table = $wpdb->prefix . 'mapes_points';

        return $wpdb->get_results($wpdb->prepare("
        SELECT p.*, rp.order_num, rp.weight
        FROM $route_points_table rp
        JOIN $points_table p ON rp.point_id = p.id
        WHERE rp.route_id = %d
        ORDER BY rp.order_num ASC
    ", $route_id));
    }
    public static function delete_route($id)
    {
        global $wpdb;
        $routes_table = $wpdb->prefix . 'mapes_routes';
        $route_points_table = $wpdb->prefix . 'mapes_route_points';

        // Eliminar monuments de ruta primer
        $wpdb->delete($route_points_table, array('route_id' => $id), array('%d'));

        // Eliminar ruta
        return $wpdb->delete($routes_table, array('id' => $id), array('%d'));
    }

    // OPERACIONS PUNTS DE RUTA
    public static function insert_route_points($route_id, $points)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'mapes_route_points';

        // Eliminar monuments existents
        $wpdb->delete($table, array('route_id' => $route_id), array('%d'));

        // Inserir nous monuments
        foreach ($points as $point) {
            $wpdb->insert(
                $table,
                array(
                    'route_id' => $route_id,
                    'point_id' => intval($point['point_id']),
                    'order_num' => intval($point['order']),
                    'weight' => floatval($point['weight'])
                ),
                array('%d', '%d', '%d', '%f')
            );
        }

        return true;
    }

    // RESTA DE FUNCIONS D'ACTIVITATS IGUALS... 
    public static function insert_activitat($data)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'mapes_activitats';


        // VERIFICAR I CREAR TAULA SI NO EXISTEIX
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        if ($table_exists != $table) {
            $charset_collate = $wpdb->get_charset_collate();
            $create_sql = "CREATE TABLE wp_mapes_activitats (
    id int(11) NOT NULL AUTO_INCREMENT,
    route_id int(11) NOT NULL,
    user_id int(11) DEFAULT NULL,
    activation_code varchar(10) NOT NULL,
    indicatiu varchar(300) NOT NULL,
    email varchar(255) NOT NULL,
    data_activitat date NOT NULL,
    referencia_dvge varchar(100) NOT NULL,
    modes_operacio text NOT NULL,
    horari varchar(20) NOT NULL DEFAULT 'mati',
    comentaris text DEFAULT NULL,
    status varchar(20) NOT NULL DEFAULT 'creada',
    fitxer_adi varchar(255) DEFAULT NULL,
    imatges text DEFAULT NULL,
    fitxer_pdf varchar(255) DEFAULT NULL,
    confirmed_at datetime DEFAULT NULL,
    confirmed_by int(11) DEFAULT NULL,
    rejected_at datetime DEFAULT NULL,
    rejected_by int(11) DEFAULT NULL,
    rejection_reason text DEFAULT NULL,
    deleted_at datetime DEFAULT NULL,
    deleted_by int(11) DEFAULT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY activation_code (activation_code),
    KEY route_id (route_id),
    KEY email_code (email, activation_code),
    KEY status_date (status, data_activitat),
    KEY user_id (user_id)
) $charset_collate";

            $wpdb->query($create_sql);
        }

        // Generar codi d'activació únic
        $activation_code = self::generate_activation_code();

        $result = $wpdb->insert(
            $table,
            array(
                'route_id' => intval($data['route_id']),
                'activation_code' => $activation_code,
                'indicatiu' => sanitize_text_field($data['indicatiu']),
                'email' => sanitize_email($data['email']),
                'data_activitat' => sanitize_text_field($data['data_activitat']),
                'referencia_dvge' => sanitize_text_field($data['referencia_dvge']),
                'modes_operacio' => sanitize_text_field(implode(', ', $data['modes'] ?? [])),
                'horari' => sanitize_text_field($data['horari']),
                'comentaris' => sanitize_textarea_field($data['comentaris'] ?? ''),
                'status' => 'creada'
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s')
        );

        // DEBUG millorat
        if ($result === false) {
            error_log('MAPES ERROR: ' . $wpdb->last_error);
            error_log('MAPES QUERY: ' . $wpdb->last_query);
            return false;
        }

        if ($result) {
            return array(
                'id' => $wpdb->insert_id,
                'activation_code' => $activation_code
            );
        }

        return false;
    }

    public static function get_activitat_by_email_code($email, $code)
    {
        global $wpdb;
        $activitats_table = $wpdb->prefix . 'mapes_activitats';
        $routes_table = $wpdb->prefix . 'mapes_routes';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT a.*, r.code as route_code, r.name as route_name, r.color as route_color
             FROM $activitats_table a
             JOIN $routes_table r ON a.route_id = r.id
             WHERE a.email = %s AND a.activation_code = %s",
            $email,
            $code
        ));
    }

    public static function update_activitat_documentation($id, $files_data)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'mapes_activitats';

        return $wpdb->update(
            $table,
            array(
                'fitxer_adi' => sanitize_text_field($files_data['adi'] ?? ''),
                'imatges' => sanitize_text_field(implode(',', $files_data['imatges'] ?? [])),
                'fitxer_pdf' => sanitize_text_field($files_data['pdf'] ?? ''),
                'status' => 'finalitzada'
            ),
            array('id' => $id),
            array('%s', '%s', '%s', '%s'),
            array('%d')
        );
    }

    public static function get_activitats_by_status($status = 'creada', $limit = null)
    {
        global $wpdb;
        $activitats_table = $wpdb->prefix . 'mapes_activitats';
        $routes_table = $wpdb->prefix . 'mapes_routes';

        $sql = "SELECT a.*, r.code as route_code, r.name as route_name
                FROM $activitats_table a
                JOIN $routes_table r ON a.route_id = r.id
                WHERE a.status = %s
                ORDER BY a.created_at DESC";

        if ($limit) {
            $sql .= $wpdb->prepare(" LIMIT %d", $limit);
        }

        return $wpdb->get_results($wpdb->prepare($sql, $status));
    }

    private static function generate_activation_code()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'mapes_activitats';

        do {
            // Generar codi aleatori de 10 caràcters (lletres i números)
            $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 10));

            // Verificar que no existeix
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE activation_code = %s",
                $code
            ));
        } while ($exists > 0);

        return $code;
    }

    // Funció per enviar email de confirmació
    public static function send_activation_email($email, $code, $route_name, $indicatiu, $selected_monument_id = null)
    {
        $subject = "Confirmació Activitat Radioaficionat - Codi: $code";

        // ⭐ OBTENIR INFORMACIÓ DEL MONUMENT SELECCIONAT
        global $wpdb;
        $routes_table = $wpdb->prefix . 'mapes_routes';
        $route_points_table = $wpdb->prefix . 'mapes_route_points';
        $points_table = $wpdb->prefix . 'mapes_points';

        // Trobar la ruta per nom
        $route = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $routes_table WHERE name = %s",
            $route_name
        ));

        // ⭐ OBTENIR MONUMENT SELECCIONAT
        $selected_monument_info = "";
        if ($selected_monument_id) {
            $selected_monument = $wpdb->get_row($wpdb->prepare(
                "SELECT title, Poblacio, lat, lng FROM $points_table WHERE id = %d",
                $selected_monument_id
            ));

            if ($selected_monument) {
                $monument_name = $selected_monument->title;
                $monument_location = !empty($selected_monument->Poblacio) ? " ({$selected_monument->Poblacio})" : "";

                $google_maps_url = "https://www.google.com/maps/search/?api=1&query=" .
                    urlencode($monument_name . $monument_location) .
                    "&query=" . $selected_monument->lat . "," . $selected_monument->lng;

                $selected_monument_info = "
            <div style='border: 1px solid #ddd; padding: 15px; margin: 20px 0; border-radius: 4px; background: #fafafa;'>
                <h3 style='margin: 0 0 10px 0; color: #333;'>Monument Seleccionat</h3>
                <p style='margin: 5px 0;'><strong>Monument:</strong> {$monument_name}{$monument_location}</p>
                <p style='margin: 10px 0 5px 0;'><strong>Enllaços:</strong></p>
                <ul style='margin: 5px 0; padding-left: 20px;'>
                    <li><a href='https://maps.google.com/?q={$selected_monument->lat},{$selected_monument->lng}' style='color: #0066cc;'>Veure coordenades</a></li>
                    <li><a href='{$google_maps_url}' style='color: #0066cc;'>Obrir Google Maps</a></li>
                </ul>
            </div>";
            }
        }

        // ⭐ OBTENIR PUNTS DE LA RUTA
        $points_info = "";
        if ($route) {
            $points = $wpdb->get_results($wpdb->prepare("
            SELECT p.title, p.Poblacio, rp.weight, rp.order_num
            FROM $route_points_table rp 
            JOIN $points_table p ON rp.point_id = p.id 
            WHERE rp.route_id = %d 
            ORDER BY rp.order_num ASC
        ", $route->id));

            if ($points) {
                $total_points = count($points);
                $points_info = "
            <div style='border: 1px solid #ddd; padding: 15px; margin: 20px 0; border-radius: 4px; background: #f9f9f9;'>
                <h3 style='margin: 0 0 10px 0; color: #333;'>Monuments de la Ruta ({$total_points} total)</h3>
                <ol style='margin: 0; padding-left: 20px; line-height: 1.5;'>";

                foreach ($points as $point) {
                    $poblacio = !empty($point->Poblacio) ? " ({$point->Poblacio})" : "";
                    $weight = $point->weight ? " - Pes: {$point->weight}%" : "";
                    $points_info .= "<li style='margin: 5px 0;'>{$point->title}{$poblacio}{$weight}</li>";
                }

                $points_info .= "</ol></div>";
            }
        }

        // ⭐ EMAIL HTML SIMPLE
        $message = "
    <!DOCTYPE html>
    <html lang='ca'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Confirmació Activitat</title>
    </head>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background-color: #f5f5f5;'>
        <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
            
            <!-- Header Simple -->
            <div style='background: #333; color: white; padding: 20px; text-align: center; border-radius: 4px 4px 0 0;'>
                <h1 style='margin: 0; font-size: 24px;'>Confirmació d'Activitat</h1>
                <p style='margin: 5px 0 0 0; font-size: 16px;'>Radioaficionat</p>
            </div>

            <!-- Contingut -->
            <div style='padding: 20px;'>
                <p>Benvolgut/da radioaficionat,</p>
                <p>La vostra sol·licitud d'activitat ha estat <strong>registrada correctament</strong>.</p>

                <!-- Detalls -->
                <div style='border: 1px solid #ddd; padding: 15px; margin: 20px 0; border-radius: 4px; background: #f9f9f9;'>
                    <h3 style='margin: 0 0 10px 0; color: #333;'>Detalls de l'Activitat</h3>
                    <table style='width: 100%;'>
                        <tr><td style='padding: 3px 0; width: 30%;'><strong>Ruta:</strong></td><td>{$route_name}</td></tr>
                        <tr><td style='padding: 3px 0;'><strong>Indicatiu:</strong></td><td>{$indicatiu}</td></tr>
                        <tr><td style='padding: 3px 0;'><strong>Codi:</strong></td><td style='font-size: 16px; font-weight: bold;'>{$code}</td></tr>
                    </table>
                </div>

                {$selected_monument_info}

                {$points_info}

                <!-- Avís -->
                <div style='border: 1px solid #ccc; padding: 15px; margin: 20px 0; border-radius: 4px; background: #f0f0f0;'>
                    <h3 style='margin: 0 0 10px 0; color: #333;'>Important</h3>
                    <p style='margin: 0;'>Guardeu aquest codi d'activació: <strong>{$code}</strong><br>
                    El necessitareu per finalitzar l'activitat.</p>
                </div>

                <!-- Enllaços -->
                <div style='border: 1px solid #ddd; padding: 15px; margin: 20px 0; border-radius: 4px; background: #fafafa;'>
                    <h3 style='margin: 0 0 10px 0; color: #333;'>Enllaços Útils</h3>
                    <ul style='margin: 0; padding-left: 20px;'>
                        <li><a href='" . home_url('/finalitzar-activitat/') . "' style='color: #0066cc;'>Finalitzar Activitat</a></li>
                        <li><a href='" . home_url('/usuari-mapes/') . "' style='color: #0066cc;'>Consultar Activitats</a></li>
                    </ul>
                </div>
            </div>

            <!-- Footer Simple -->
            <div style='background: #f0f0f0; padding: 15px; text-align: center; border-radius: 0 0 4px 4px; border-top: 1px solid #ddd;'>
                <p style='margin: 0; font-size: 14px; color: #666;'>Sistema de Gestió d'Activitats</p>
            </div>
        </div>
    </body>
    </html>";

        // ⭐ CAPÇALERES HTML
        $headers = array(
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: Sistema Activitats <noreply@' . parse_url(home_url(), PHP_URL_HOST) . '>'
        );

        $result = wp_mail($email, $subject, $message, $headers);

        // DEBUG
        if (!$result) {
            global $phpmailer;
            error_log('ERROR ENVIANT EMAIL: ' . $phpmailer->ErrorInfo);
        } else {
            error_log('EMAIL SIMPLE ENVIAT CORRECTAMENT A: ' . $email);
        }

        return $result;
    }

    // NOVA FUNCIÓ: Inserir monuments d'una activitat
    public static function insert_activitat_points($activitat_id, $point_ids)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'mapes_activitat_points';

        // Eliminar monuments existents de l'activitat
        $wpdb->delete($table, array('activitat_id' => $activitat_id), array('%d'));

        // Inserir nous monuments
        foreach ($point_ids as $point_id) {
            $wpdb->insert(
                $table,
                array(
                    'activitat_id' => $activitat_id,
                    'point_id' => intval($point_id)
                ),
                array('%d', '%d')
            );
        }

        return true;
    }

    // NOVA FUNCIÓ: Obtenir monuments d'una activitat
    public static function get_activitat_points($activitat_id)
    {
        global $wpdb;
        $activitat_points_table = $wpdb->prefix . 'mapes_activitat_points';
        $points_table = $wpdb->prefix . 'mapes_points';

        return $wpdb->get_results($wpdb->prepare("
        SELECT p.*, ap.created_at as selected_at
        FROM $activitat_points_table ap
        JOIN $points_table p ON ap.point_id = p.id
        WHERE ap.activitat_id = %d
        ORDER BY p.title ASC
    ", $activitat_id));
    }

    // FUNCIONS NOVES PER ACTIVACIONS

    /**
     * Obtenir estadístiques d'activacions
     */
    public static function get_activation_stats()
    {
        global $wpdb;
        $table = $wpdb->prefix . 'mapes_activitats';

        // ⭐ DEBUG TEMPORAL
        $debug_query = "SELECT status, COUNT(*) as count FROM $table GROUP BY status";
        $debug_results = $wpdb->get_results($debug_query);
        error_log('DEBUG STATS: ' . print_r($debug_results, true));

        $stats = $wpdb->get_row("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'creada' THEN 1 ELSE 0 END) as creades,
            SUM(CASE WHEN status = 'finalitzada' THEN 1 ELSE 0 END) as pendents,
            SUM(CASE WHEN status = 'confirmada' THEN 1 ELSE 0 END) as confirmades
        FROM $table
        WHERE status != 'esborrada'
    ", ARRAY_A);

        // ⭐ DEBUG TEMPORAL
        error_log('STATS RESULT: ' . print_r($stats, true));

        return $stats ?: ['total' => 0, 'creades' => 0, 'pendents' => 0, 'confirmades' => 0];
    }

    /**
     * Obtenir activacions pendents de confirmació - VERSIÓ ESTABLE
     */
    public static function get_pending_activations()
    {
        global $wpdb;
        $activations_table = $wpdb->prefix . 'mapes_activitats';
        $routes_table = $wpdb->prefix . 'mapes_routes';
        $activation_points_table = $wpdb->prefix . 'mapes_activitat_points';
        $route_points_table = $wpdb->prefix . 'mapes_route_points';

        $results = $wpdb->get_results("
        SELECT 
            a.*,
            COALESCE(NULLIF(a.indicatiu,''), a.email, 'Usuari desconegut') as user_name,
            COALESCE(CONCAT(r.code, ' - ', r.name), CONCAT('Ruta-', a.route_id)) as route_code,
            r.name as route_name,
            r.color as route_color,
            
            COUNT(DISTINCT ap.point_id) as points_count,
            COALESCE(SUM(DISTINCT rp.weight), 0) as total_weight,
            
            (SELECT COUNT(DISTINCT rp2.point_id) 
             FROM {$route_points_table} rp2 
             WHERE rp2.route_id = a.route_id) as total_route_points,
             
            (SELECT COALESCE(SUM(rp3.weight), 0) 
             FROM {$route_points_table} rp3 
             WHERE rp3.route_id = a.route_id) as total_route_weight
             
        FROM {$activations_table} a
        LEFT JOIN {$routes_table} r ON a.route_id = r.id
        LEFT JOIN {$activation_points_table} ap ON a.id = ap.activitat_id
        LEFT JOIN {$route_points_table} rp ON ap.point_id = rp.point_id AND rp.route_id = a.route_id
        WHERE a.status = 'finalitzada'
        GROUP BY a.id
        ORDER BY a.created_at DESC
    ");

        // PROCESSAR DOCUMENTS DELS CAMPS EXISTENTS
        if ($results) {
            foreach ($results as &$activation) {
                $activation->documents = [];

                // Document addicional d'imatges
                if (!empty($activation->fitxer_adii)) {
                    $activation->documents[] = (object) [
                        'file_name' => $activation->fitxer_adii,
                        'file_url' => '/wp-content/uploads/mapes-activitats/' . $activation->fitxer_adii
                    ];
                }

                // Document PDF
                if (!empty($activation->fitxer_pdf)) {
                    $activation->documents[] = (object) [
                        'file_name' => $activation->fitxer_pdf,
                        'file_url' => '/wp-content/uploads/mapes-activitats/' . $activation->fitxer_pdf
                    ];
                }

                // Si hi ha imatges al camp "imatges"
                if (!empty($activation->imatges)) {
                    $imatges = explode(',', $activation->imatges);
                    foreach ($imatges as $imatge) {
                        if (trim($imatge)) {
                            $activation->documents[] = (object) [
                                'file_name' => trim($imatge),
                                'file_url' => '/wp-content/uploads/mapes-activitats/' . trim($imatge)
                            ];
                        }
                    }
                }
            }
        }

        return $results ?: [];
    }



    /**
     * Obtenir activacions creades - VERSIÓ CORREGIDA
     */
    public static function get_created_activations($limit = 50)
    {
        global $wpdb;
        $activations_table = $wpdb->prefix . 'mapes_activitats';
        $routes_table = $wpdb->prefix . 'mapes_routes';
        $activation_points_table = $wpdb->prefix . 'mapes_activitat_points';
        $route_points_table = $wpdb->prefix . 'mapes_route_points';

        $results = $wpdb->get_results($wpdb->prepare("
        SELECT 
            a.*,
            COALESCE(NULLIF(a.indicatiu,''), a.email, 'Usuari desconegut') as user_name,
            COALESCE(CONCAT(r.code, ' - ', r.name), CONCAT('Ruta-', a.route_id)) as route_code,
            r.name as route_name,
            r.color as route_color,
            
            COUNT(DISTINCT ap.point_id) as points_count,
            COALESCE(SUM(DISTINCT rp.weight), 0) as total_weight,
            
            (SELECT COUNT(DISTINCT rp2.point_id) 
             FROM {$route_points_table} rp2 
             WHERE rp2.route_id = a.route_id) as total_route_points,
             
            (SELECT COALESCE(SUM(rp3.weight), 0) 
             FROM {$route_points_table} rp3 
             WHERE rp3.route_id = a.route_id) as total_route_weight
             
        FROM {$activations_table} a
        LEFT JOIN {$routes_table} r ON a.route_id = r.id
        LEFT JOIN {$activation_points_table} ap ON a.id = ap.activitat_id
        LEFT JOIN {$route_points_table} rp ON ap.point_id = rp.point_id AND rp.route_id = a.route_id
        WHERE a.status = 'creada'
        GROUP BY a.id
        ORDER BY a.created_at DESC
        LIMIT %d
    ", $limit));

        if ($results) {
            foreach ($results as &$activation) {
                $activation->documents = [];
            }
        }

        return $results ?: [];
    }
    /**
     * Obtenir activacions confirmades
     */
    public static function getConfirmedActivations($limit = 50)
    {
        global $wpdb;
        $activations_table = $wpdb->prefix . 'mapes_activitats';
        $routes_table = $wpdb->prefix . 'mapes_routes';
        $activation_points_table = $wpdb->prefix . 'mapes_activitat_points';
        $routepoints_table = $wpdb->prefix . 'mapes_route_points';

        $results = $wpdb->get_results($wpdb->prepare("
        SELECT a.*,
               COALESCE(NULLIF(a.indicatiu,''), a.email, 'Usuari desconegut') as username,
               COALESCE(CONCAT(r.code, ' - ', r.name), CONCAT('Ruta-', a.route_id)) as route_code,
               r.name as route_name,
               r.color as route_color,
               COUNT(DISTINCT ap.point_id) as points_count,
               COALESCE(SUM(DISTINCT rp.weight), 0) as total_weight,
               (SELECT COALESCE(SUM(rp2.weight), 0) 
                FROM {$routepoints_table} rp2 
                WHERE rp2.route_id = a.route_id) as total_route_weight
        FROM {$activations_table} a
        LEFT JOIN {$routes_table} r ON a.route_id = r.id
        LEFT JOIN {$activation_points_table} ap ON a.id = ap.activitat_id
        LEFT JOIN {$routepoints_table} rp ON ap.point_id = rp.point_id AND rp.route_id = a.route_id
        WHERE a.status = 'confirmada'
        GROUP BY a.id
        ORDER BY a.created_at DESC  
        LIMIT %d
    ", $limit));

        return $results ?: array();
    }




    /**
     * Migració de dades (si cal)
     */
    private static function migrate_activitats_to_activacions()
    {
        // Funció buida per evitar errors - no cal migrar res ara mateix
        error_log('MIGRACIÓ: Funció migrate_activitats_to_activacions() cridada correctament');
    }


    /**
     * Confirmar activació
     */
    public static function confirm_activation($activation_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'mapes_activitats';

        $result = $wpdb->update(
            $table,
            [
                'status' => 'confirmada',
                'confirmed_at' => current_time('mysql'),
                'confirmed_by' => get_current_user_id()
            ],
            ['id' => $activation_id],
            ['%s', '%s', '%d'],
            ['%d']
        );

        if ($result !== false) {
            // Actualitzar última activació dels monuments
            self::update_points_last_activation($activation_id);
        }

        return $result;
    }

    /**
     * Rebutjar activació
     */
    public static function reject_activation($activation_id, $reason = '')
    {
        global $wpdb;
        $table = $wpdb->prefix . 'mapes_activitats';

        return $wpdb->update(
            $table,
            [
                'status' => 'rebutjada',
                'rejected_at' => current_time('mysql'),
                'rejected_by' => get_current_user_id(),
                'rejection_reason' => $reason
            ],
            ['id' => $activation_id],
            ['%s', '%s', '%d', '%s'],
            ['%d']
        );
    }

    /**
     * Esborrar activació
     */
    public static function delete_activation($activation_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . 'mapes_activitats';

        return $wpdb->update(
            $table,
            [
                'status' => 'esborrada',
                'deleted_at' => current_time('mysql'),
                'deleted_by' => get_current_user_id()
            ],
            ['id' => $activation_id],
            ['%s', '%s', '%d'],
            ['%d']
        );
    }

    /**
     * Actualitzar última activació dels monuments
     */
    private static function update_points_last_activation($activation_id)
    {
        global $wpdb;
        $points_table = $wpdb->prefix . 'mapes_points';
        $activation_points_table = $wpdb->prefix . 'mapes_activitat_points';
        $activations_table = $wpdb->prefix . 'mapes_activitats';

        // Obtenir data de l'activació
        $activation_date = $wpdb->get_var($wpdb->prepare("
        SELECT data_activitat FROM $activations_table WHERE id = %d
    ", $activation_id));

        // Actualitzar tots els monuments de l'activació
        $wpdb->query($wpdb->prepare("
        UPDATE $points_table p
        INNER JOIN $activation_points_table ap ON p.id = ap.point_id
        SET p.Darrera_Activacio = %s,
            p.Vegades_activat = COALESCE(p.Vegades_activat, 0) + 1
        WHERE ap.activitat_id = %d
    ", $activation_date, $activation_id));
    }

    /**
     * Obtenir monuments amb informació d'estat d'activació
     */
    public static function get_points_with_status()
    {
        global $wpdb;
        $points_table = $wpdb->prefix . 'mapes_points';
        $activities_table = $wpdb->prefix . 'mapes_activitats';
        $activity_points_table = $wpdb->prefix . 'mapes_activitat_points';

        $points = $wpdb->get_results("
        SELECT 
            p.*,
            -- Estat d'activació
            CASE 
                WHEN MAX(a.status = 'confirmada') = 1 THEN 'confirmed'
                WHEN MAX(a.status IN ('creada', 'finalitzada')) = 1 THEN 'pending'
                ELSE 'never_activated'
            END as activation_status,
            
            -- Última data de confirmació
            MAX(CASE WHEN a.status = 'confirmada' THEN a.updated_at END) as last_confirmed_date,
            
            -- Comprovar si fa més de 3 anys
            CASE 
                WHEN MAX(CASE WHEN a.status = 'confirmada' THEN a.updated_at END) IS NOT NULL 
                     AND MAX(CASE WHEN a.status = 'confirmada' THEN a.updated_at END) < DATE_SUB(NOW(), INTERVAL 3 YEAR)
                THEN 1
                ELSE 0
            END as is_old_confirmation
            
        FROM $points_table p
        LEFT JOIN $activity_points_table ap ON p.id = ap.point_id
        LEFT JOIN $activities_table a ON ap.activitat_id = a.id
        GROUP BY p.id
        ORDER BY p.created_at DESC
    ");

        return $points;
    }
    /**
     * Obtenir monuments amb el seu estat d'activació per determinar colors
     */
    public static function get_points_with_activation_status()
    {
        global $wpdb;
        $points_table = $wpdb->prefix . 'mapes_points';
        $activations_table = $wpdb->prefix . 'mapes_activitats';
        $activation_points_table = $wpdb->prefix . 'mapes_activitat_points';

        $three_years_ago = date('Y-m-d H:i:s', strtotime('-3 years'));

        $points = $wpdb->get_results("
        SELECT 
            p.*,
            -- Última activació creada (qualsevol estat)
            MAX(a.created_at) as last_activity_date,
            
            -- Última activació confirmada
            MAX(CASE WHEN a.status = 'confirmada' THEN a.created_at END) as last_confirmed_date,
            
            -- Hi ha activacions pendents?
            COUNT(CASE WHEN a.status IN ('creada', 'finalitzada') THEN 1 END) as pending_count,
            
            -- Total d'activacions confirmades
            COUNT(CASE WHEN a.status = 'confirmada' THEN 1 END) as confirmed_count,
            
            -- Estat calculat
            CASE 
                -- Mai activat
                WHEN COUNT(ap.point_id) = 0 THEN 'never_activated'
                
                -- Té activacions pendents
                WHEN COUNT(CASE WHEN a.status IN ('creada', 'finalitzada') THEN 1 END) > 0 THEN 'pending_confirmation'
                
                -- Confirmat fa menys de 3 anys
                WHEN MAX(CASE WHEN a.status = 'confirmada' THEN a.created_at END) > '$three_years_ago' THEN 'confirmed_recent'
                
                -- Confirmat fa més de 3 anys
                WHEN MAX(CASE WHEN a.status = 'confirmada' THEN a.created_at END) IS NOT NULL THEN 'confirmed_old'
                
                -- Fallback
                ELSE 'never_activated'
            END as activation_status
            
        FROM $points_table p
        LEFT JOIN $activation_points_table ap ON p.id = ap.point_id
        LEFT JOIN $activations_table a ON ap.activitat_id = a.id AND a.status != 'esborrada'
        GROUP BY p.id
        ORDER BY p.id
    ");

        // Debug temporal per veure els estats calculats
        foreach ($points as $point) {
            error_log("POINT COLOR DEBUG: {$point->title} -> {$point->activation_status} (pending:{$point->pending_count}, confirmed:{$point->confirmed_count})");
        }

        return $points ?: [];
    }
    /**
     * 🎨 AFEGIR ESTAT D'ACTIVACIÓ ALS PUNTS
     */
    public static function add_activation_status_to_points($points)
    {
        // ⚡ DEBUG TEMPORAL - AFEGIR AQUESTA LÍNIA
        error_log("🔍 add_activation_status_to_points() EXECUTADA amb " . count($points) . " monuments");

        if (empty($points))
            return $points;

        global $wpdb;
        $activitats_table = $wpdb->prefix . 'mapes_activitats';
        $activitat_points_table = $wpdb->prefix . 'mapes_activitat_points';

        foreach ($points as &$point) {
            $vegades_activat = intval($point->Vegades_activat ?? 0);
            $darrera_activacio = $point->Darrera_Activacio ?? null;

            // ⚡ DEBUG TEMPORAL - AFEGIR AQUESTES LÍNIES
            error_log("🔍 Monument '{$point->title}': Vegades_activat={$vegades_activat}, Darrera_Activacio={$darrera_activacio}");

            if ($vegades_activat === 0) {
                // Mai activat
                $point->activation_status = 'never_activated';
            } else {
                // Comprovar si hi ha activacions pendents per aquest monument
                $pending = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*)
                FROM {$activitat_points_table} ap
                INNER JOIN {$activitats_table} a ON ap.activitat_id = a.id
                WHERE ap.point_id = %d AND a.status IN ('creada', 'finalitzada')
            ", $point->id));

                // ⚡ DEBUG TEMPORAL
                error_log("🔍 Monument '{$point->title}': Pendents={$pending}");

                if ($pending > 0) {
                    $point->activation_status = 'pending_confirmation';
                } else {
                    // Activació confirmada - comprovar antiguitat
                    if ($darrera_activacio) {
                        $data_activacio = new DateTime($darrera_activacio);
                        $avui = new DateTime();
                        $dies = $avui->diff($data_activacio)->days;

                        $point->activation_status = ($dies <= 30) ? 'confirmed_recent' : 'confirmed_old';
                    } else {
                        $point->activation_status = 'confirmed_old';
                    }
                }
            }
            // ⚡ DEBUG FINAL
            error_log("🎨 Monument '{$point->title}': RESULTAT activation_status={$point->activation_status}");
        }

        return $points;
    }



}
