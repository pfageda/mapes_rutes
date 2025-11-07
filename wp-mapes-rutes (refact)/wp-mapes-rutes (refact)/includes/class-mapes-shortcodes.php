<?php
/**
 * Gestió de shortcodes per Mapes i Rutes
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Mapes_Shortcodes
{
    public function __construct()
    {
        add_shortcode('mapes-app', array($this, 'render_main_app'));
        add_shortcode('usuari-mapes', array($this, 'render_user_app'));
        add_shortcode('formulari-activitat', array($this, 'render_activity_form'));
        add_shortcode('finalitzar-activitat', array($this, 'render_finalize_form'));
        add_shortcode('enviar-documentacio', array($this, 'render_documentation_form'));
        add_shortcode('gestio-activacions', array($this, 'render_activations_admin'));
        add_shortcode('mapes_test_create', function () {
            WP_Mapes_Rutes_Core::get_instance()->mapes_test_create_tables();
            return 'Creació de taules executada. Revisa debug.log per errors.';
        });



    }
    public function render_main_app($atts)
    {
        $atts = shortcode_atts(array(
            'height' => '600px',
        ), $atts);

        $api_key = get_option('mapes_google_api_key', '');
        if (empty($api_key)) {
            return '<div class="mapes-error">❌ API key no configurada. Ves a Mapes > Configuració</div>';
        }

        $app_id = 'mapes-app-' . uniqid();
        $points = WP_Mapes_Database::get_points_with_status();
        $routes = WP_Mapes_Database::get_routes();

        // Cargar template
        ob_start();
        include WP_MAPES_TEMPLATES_PATH . 'shortcode-main-app.php';
        $html = ob_get_clean();

        // Inicialitzar JavaScript amb dades
        $script = "
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (window.mapesCore) {  // <-- CORRECCIÓ: mapesCore no mapesUser
            window.mapesCore.setAppData('{$app_id}', " . wp_json_encode($points) . ", " . wp_json_encode($routes) . ");
        }
    });
</script>";

        return $html . $script;
    }

    public function render_user_app($atts)
    {
        $atts = shortcode_atts(array(
            'height' => '600px',
        ), $atts);

        $api_key = get_option('mapes_google_api_key', '');
        if (empty($api_key)) {
            return '<div class="mapes-error">❌ API key no configurada.</div>';
        }

        $app_id = 'user-mapes-app-' . uniqid();
        $points = WP_Mapes_Database::get_points_with_status();
        $routes = WP_Mapes_Database::get_routes();

        // Cargar template d'usuari
        ob_start();
        include WP_MAPES_TEMPLATES_PATH . 'shortcode-user-app.php';
        $html = ob_get_clean();

        $script = "
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.mapesUserCore) {
                window.mapesUserCore.setAppData('{$app_id}', " . wp_json_encode($points) . ", " . wp_json_encode($routes) . ");
            }
        });
    </script>";

        return $html . $script;
    }

    public function render_activity_form($atts)
    {
        $atts = shortcode_atts(array(
            'route_id' => '',
        ), $atts);

        // Obtenir ruta des de paràmetre URL o shortcode
        $route_id = $atts['route_id'] ?: ($_GET['route'] ?? '');

        if (empty($route_id)) {
            return '<div class="mapes-error">No s\'ha especificat cap ruta</div>';
        }

        $routes = WP_Mapes_Database::get_routes(true);
        $selected_route = null;

        foreach ($routes as $r) {
            if ($r->id == $route_id) {
                $selected_route = $r;
                break;
            }
        }

        if (!$selected_route) {
            return '<div class="mapes-error">Ruta no trobada</div>';
        }

        // AFEGIR AQUESTA PART: Obtenir punts de la ruta seleccionada
        $route_points = WP_Mapes_Database::get_route_points($route_id);

        // DEBUG temporal
        error_log("🔍 ROUTE POINTS TROBATS: " . print_r($route_points, true));

        ob_start();
        include WP_MAPES_TEMPLATES_PATH . 'shortcode-activity-form.php';
        return ob_get_clean();
    }


    public function render_finalize_form($atts)
    {
        $atts = shortcode_atts(array(
            'redirect_success' => '/enviar-documentacio/',
        ), $atts);

        ob_start();
        include WP_MAPES_TEMPLATES_PATH . 'shortcode-finalize-activity.php';
        return ob_get_clean();
    }

    public function render_documentation_form($atts)
    {
        $atts = shortcode_atts(array(
            'activitat_id' => $_GET['activitat'] ?? '',
        ), $atts);

        // Verificar que tenim activitat vàlida
        if (empty($atts['activitat_id'])) {
            return '<div class="mapes-error">❌ No s\'ha especificat activitat vàlida</div>';
        }

        ob_start();
        include WP_MAPES_TEMPLATES_PATH . 'shortcode-documentation-form.php';
        return ob_get_clean();
    }
    public function render_activations_admin($atts)
    {
        $atts = shortcode_atts(array(
            'height' => '600px'
        ), $atts);

        // Verificar permisos d'administrador
        if (!current_user_can('manage_options')) {
            return '<div class="mapes-error">❌ No tens permisos per accedir a aquesta pàgina</div>';
        }

        $app_id = 'activations-admin-' . uniqid();

        // Obtenir estadístiques d'activacions
        $stats = WP_Mapes_Database::get_activation_stats();

        // Obtenir activacions pendents
        $pending_activations = WP_Mapes_Database::get_pending_activations();

        // Obtenir activacions creades
        $created_activations = WP_Mapes_Database::get_created_activations();

        // Obtenir activacions confirmades
        $confirmed_activations = WP_Mapes_Database::getConfirmedActivations();
        // 🔍 DEBUG - Elimina després de comprovar:
        error_log('Stats confirmades: ' . ($stats['confirmades'] ?? 'NULL'));
        error_log('Confirmed activations count: ' . count($confirmed_activations));
        error_log('Confirmed activations data: ' . print_r($confirmed_activations, true));
        // Carregar template
        ob_start();
        include WP_MAPES_TEMPLATES_PATH . 'shortcode-activations-admin.php';
        $html = ob_get_clean();

        // Inicialitzar JavaScript
        $script = "
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (window.mapesActivations) {
            window.mapesActivations.setAppData('{$app_id}');
        }
    });
</script>";

        return $html . $script;
    }

}
