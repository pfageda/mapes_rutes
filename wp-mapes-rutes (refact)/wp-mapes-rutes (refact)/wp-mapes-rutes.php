<?php
/**
 * Plugin Name: Mapes i Rutes Catalunya - Refactoritzat
 * Description: Gestió completa de monuments i rutes amb Google Maps - Arquitectura modular
 * Version: 3.0.0
 * Author: Pere Fajeda - propietat d'URBBLL
 */

if (!defined('ABSPATH')) {
    exit;
}

// Definir constants
define('WP_MAPES_VERSION', '3.0.0');
define('WP_MAPES_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WP_MAPES_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WP_MAPES_ASSETS_URL', WP_MAPES_PLUGIN_URL . 'assets/');
define('WP_MAPES_TEMPLATES_PATH', WP_MAPES_PLUGIN_PATH . 'templates/');

/**
 * Classe principal del plugin
 */
class WP_Mapes_Rutes_Core
{
    private static $instance = null;

    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies()
    {
        require_once WP_MAPES_PLUGIN_PATH . 'includes/class-mapes-database.php';
        require_once WP_MAPES_PLUGIN_PATH . 'includes/class-mapes-ajax.php';
        require_once WP_MAPES_PLUGIN_PATH . 'includes/class-mapes-shortcodes.php';
        require_once WP_MAPES_PLUGIN_PATH . 'includes/class-mapes-admin.php';
    }

    private function init_hooks()
    {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('init', array($this, 'maybe_create_tables'));

        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init()
    {
        // Inicialitzar components
        new WP_Mapes_Ajax();
        new WP_Mapes_Shortcodes();
        new WP_Mapes_Admin();
    }


    public function enqueue_frontend_scripts()
    {
        global $post;
        if (is_admin() || !$post) {
            return;
        }

        // CSS comú per tots els shortcodes
        wp_enqueue_style(
            'mapes-frontend',
            WP_MAPES_ASSETS_URL . 'css/mapes-frontend.css',
            array(),
            WP_MAPES_VERSION
        );

        // Scripts específics per mapes-app (versió admin)
        if (has_shortcode($post->post_content, 'mapes-app')) {
            wp_enqueue_script(
                'mapes-core',
                WP_MAPES_ASSETS_URL . 'js/mapes-core.js',
                array('jquery'),
                WP_MAPES_VERSION,
                true
            );

            wp_enqueue_script(
                'mapes-ui',
                WP_MAPES_ASSETS_URL . 'js/mapes-ui.js',
                array('mapes-core'),
                WP_MAPES_VERSION,
                true
            );

            wp_enqueue_script(
                'mapes-points',
                WP_MAPES_ASSETS_URL . 'js/mapes-points.js',
                array('mapes-core'),
                WP_MAPES_VERSION,
                true
            );

            wp_enqueue_script(
                'mapes-routes',
                WP_MAPES_ASSETS_URL . 'js/mapes-routes.js',
                array('mapes-core'),
                WP_MAPES_VERSION,
                true
            );

            // Configuració per mapes-app
            wp_localize_script('mapes-core', 'mapesConfig', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mapes_nonce'),
                'apiKey' => get_option('mapes_google_api_key', ''),
                'assetsUrl' => WP_MAPES_ASSETS_URL
            ));
        }

        // Scripts específics per usuari-mapes (versió usuari)
        if (has_shortcode($post->post_content, 'usuari-mapes')) {
            wp_enqueue_script(
                'mapes-user',
                WP_MAPES_ASSETS_URL . 'js/mapes-user.js',
                array('jquery'),
                WP_MAPES_VERSION,
                true
            );

            // Configuració per usuari-mapes
            wp_localize_script('mapes-user', 'mapesUserConfig', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mapes_nonce'),
                'apiKey' => get_option('mapes_google_api_key', ''),
            ));
        }
        // NOVA SECCIÓ: Scripts específics per gestio-activacions
        if (has_shortcode($post->post_content, 'gestio-activacions')) {
            wp_enqueue_script(
                'mapes-activations',
                WP_MAPES_ASSETS_URL . 'js/mapes-activations.js',
                array('jquery'),
                WP_MAPES_VERSION,
                true
            );

            wp_enqueue_style(
                'mapes-activations',
                WP_MAPES_ASSETS_URL . 'css/mapes-activations.css',
                array(),
                WP_MAPES_VERSION
            );

            // Configuració per gestio-activacions
            wp_localize_script('mapes-activations', 'mapesConfig', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('mapes_nonce'),
                'confirmMessage' => 'Estàs segur que vols confirmar aquesta activació?',
                'rejectMessage' => 'Estàs segur que vols rebutjar aquesta activació?',
                'deleteMessage' => 'Estàs segur que vols esborrar aquesta activació? Aquesta acció no es pot desfer.'
            ));
        }


    }


    public function enqueue_admin_scripts($hook)
    {
        if ($hook !== 'toplevel_page_mapes-complet') {
            return;
        }

        wp_enqueue_style(
            'mapes-admin',
            WP_MAPES_ASSETS_URL . 'css/mapes-admin.css',
            array(),
            WP_MAPES_VERSION
        );

        wp_enqueue_script(
            'mapes-admin',
            WP_MAPES_ASSETS_URL . 'js/mapes-admin.js',
            array('jquery'),
            WP_MAPES_VERSION,
            true
        );
    }

    public function activate()
    {
        if (!class_exists('WP_Mapes_Database')) {
            require_once WP_MAPES_PLUGIN_PATH . 'includes/class-mapes-database.php';
        }

        WP_Mapes_Database::create_tables();
        add_option('mapes_google_api_key', '');
        flush_rewrite_rules();
    }

    public function deactivate()
    {
        flush_rewrite_rules();
    }
    //crearem taules si no existeixen
    public function maybe_create_tables()
    {
        global $wpdb;
        $tables_to_check = [
            $wpdb->prefix . 'mapes_points',
            $wpdb->prefix . 'mapes_routes',
            $wpdb->prefix . 'mapes_route_points',
            $wpdb->prefix . 'mapes_activitats',
            $wpdb->prefix . 'mapes_activitat_points',
            $wpdb->prefix . 'mapes_activacions',
            $wpdb->prefix . 'mapes_activitat_documents'
        ];

        $all_exist = true;
        foreach ($tables_to_check as $table) {
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
            if (!$table_exists) {
                $all_exist = false;
                break;
            }
        }

        if (!$all_exist) {
            WP_Mapes_Database::create_tables();
            error_log('Mapes: Taules creades automàticament des de maybe_create_tables().');
        }
    }


    public function mapes_test_create_tables()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Taula de monuments
        $points_table = $wpdb->prefix . 'mapes_points';
        $points_sql = "CREATE TABLE $points_table (
        id int(11) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        description text,
        lat decimal(10,6) NOT NULL,
        lng decimal(10,6) NOT NULL,
        DME int(11) DEFAULT NULL,
        Poblacio varchar(280) NOT NULL,
        Provincia varchar(140) NOT NULL,
        Fitxa_Monument varchar(500) NOT NULL,
        Vegades_activat int(11) NOT NULL DEFAULT 0,
        Darrera_Activacio datetime NULL,
        Indicatiu_activacio varchar(300) NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY lat_lng (lat, lng)
    ) $charset_collate;";

        // Taula de rutes
        $routes_table = $wpdb->prefix . 'mapes_routes';
        $routes_sql = "CREATE TABLE $routes_table (
        id int(11) NOT NULL AUTO_INCREMENT,
        code varchar(50) NOT NULL UNIQUE,
        name varchar(255) NOT NULL,
        color varchar(7) DEFAULT '#000000',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";

        // Taula relació ruta-monuments
        $route_points_table = $wpdb->prefix . 'mapes_route_points';
        $route_points_sql = "CREATE TABLE $route_points_table (
        id int(11) NOT NULL AUTO_INCREMENT,
        route_id int(11) NOT NULL,
        point_id int(11) NOT NULL,
        order_num int(11) NOT NULL DEFAULT 1,
        weight decimal(5,2) DEFAULT 1.00,
        PRIMARY KEY (id),
        KEY route_id (route_id),
        KEY point_id (point_id),
        KEY route_order (route_id, order_num)
    ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        dbDelta($points_sql);
        dbDelta($routes_sql);
        dbDelta($route_points_sql);

        // Escriure missatge debug
        error_log("Mapes: creació de taules executada.");

        echo "Creació de taules executada. Revisa debug.log per errors.";
    }

}

// Inicialitzar plugin
function wp_mapes_rutes_init()
{
    return WP_Mapes_Rutes_Core::get_instance();
}
add_action('plugins_loaded', 'wp_mapes_rutes_init');