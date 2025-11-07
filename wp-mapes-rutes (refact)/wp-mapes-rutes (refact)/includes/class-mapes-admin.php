<?php
/**
 * Gestió de l'administració per Mapes i Rutes
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Mapes_Admin
{
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'handle_admin_actions'));
    }

    public function add_admin_menu()
    {
        add_menu_page(
            'Mapes Catalunya',
            'Mapes',
            'manage_options',
            'mapes-complet',
            array($this, 'admin_page'),
            'dashicons-location-alt',
            30
        );
        add_submenu_page(
            'mapes-complet',                    // Parent slug
            'Gestió d\'Activacions',            // Page title
            '📋 Activacions',                   // Menu title
            'manage_options',                   // Capability
            'mapes-activacions',                // Menu slug
            array($this, 'activations_page')   // Function
        );
    }

    public function handle_admin_actions()
    {
        if (isset($_POST['save_config']) && current_user_can('manage_options')) {
            check_admin_referer('mapes_admin_config');

            update_option('mapes_google_api_key', sanitize_text_field($_POST['google_api_key']));
            add_settings_error('mapes_settings', 'settings_updated', 'Configuració guardada!', 'success');
        }
    }

    public function admin_page()
    {
        $api_key = get_option('mapes_google_api_key', '');
        $points_count = count(WP_Mapes_Database::get_points());
        $routes_count = count(WP_Mapes_Database::get_routes(false));

        ?>
        <div class="wrap">
            <h1>Mapes Catalunya - Administració</h1>

            <?php settings_errors('mapes_settings'); ?>

            <div class="mapes-admin-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">

                <!-- Estadístiques -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">Estadístiques</h2>
                    </div>
                    <div class="inside">
                        <table class="widefat">
                            <tr>
                                <td><strong>Monuments totals:</strong></td>
                                <td><?php echo $points_count; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Rutes totals:</strong></td>
                                <td><?php echo $routes_count; ?></td>
                            </tr>
                            <tr>
                                <td><strong>Estat API:</strong></td>
                                <td><?php echo $api_key ? '✅ Configurat' : '❌ No configurat'; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Configuració -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">Configuració API</h2>
                    </div>
                    <div class="inside">
                        <form method="post">
                            <?php wp_nonce_field('mapes_admin_config'); ?>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="google_api_key">Google Maps API Key</label>
                                    </th>
                                    <td>
                                        <input type="text" id="google_api_key" name="google_api_key"
                                            value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
                                        <p class="description">
                                            Introdueix la teva clau API de Google Maps.
                                            <a href="https://developers.google.com/maps/documentation/javascript/get-api-key"
                                                target="_blank">Com obtenir-la?</a>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            <?php submit_button('Guardar Configuració', 'primary', 'save_config'); ?>
                        </form>
                    </div>
                </div>

                <!-- Shortcodes -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">Shortcodes Disponibles</h2>
                    </div>
                    <div class="inside">
                        <p><code>[mapes-app]</code> - Aplicació completa</p>
                        <p><code>[mapes-app height="500px"]</code> - Amb altura personalitzada</p>

                        <h4>Exemple d'ús:</h4>
                        <textarea readonly class="widefat" rows="3">
                                                                        Afegeix aquest shortcode a qualsevol pàgina o entrada:

                                                                        [mapes-app height="600px"]
                                                                                                </textarea>
                    </div>
                </div>

                <!-- Accions -->
                <div class="postbox">
                    <div class="postbox-header">
                        <h2 class="hndle">Accions d'Administració</h2>
                    </div>
                    <div class="inside">
                        <?php if ($points_count > 0): ?>
                            <p>
                                <a href="<?php echo admin_url('admin.php?page=mapes-export'); ?>" class="button">
                                    Exportar Dades
                                </a>
                            </p>
                        <?php endif; ?>

                        <p>
                            <a href="<?php echo admin_url('admin.php?page=mapes-import'); ?>" class="button">
                                Importar Dades
                            </a>
                        </p>

                        <h4>Informació del Plugin:</h4>
                        <ul>
                            <li><strong>Versió:</strong> <?php echo WP_MAPES_VERSION; ?></li>
                            <li><strong>Base de dades:</strong> Operativa</li>
                            <li><strong>JavaScript:</strong> Modular</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Pàgina de gestió d'activacions
     */
    public function activations_page()
    {
        // Obtenir activacions pendents
        $pending_activations = WP_Mapes_Database::get_pending_activations();

        ?>
        <div class="wrap">
            <h1>📋 Gestió d'Activacions</h1>

            <?php if (empty($pending_activations)): ?>
                <div class="notice notice-info">
                    <p>No hi ha activacions pendents de confirmació.</p>
                </div>
            <?php else: ?>
                <div class="activations-list">
                    <?php foreach ($pending_activations as $activation): ?>
                        <div class="activation-card"
                            style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin-bottom: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); font-size: 14px;">

                            <!-- Header -->
                            <div class="activation-header"
                                style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <div class="activation-title" style="font-size: 16px; font-weight: bold; color: #333;">
                                    <?php echo esc_html($activation->route_code ?? "Ruta-{$activation->route_id}"); ?>
                                    <small style="color: #666;">[<?php echo esc_html($activation->email); ?>]</small>
                                </div>
                                <div class="activation-status"
                                    style="background: #ffa500; color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px;">
                                    ⏳ <?php echo ucfirst($activation->status); ?>
                                </div>
                            </div>

                            <!-- Details - FONT MÉS GRAN -->
                            <div class="activation-details"
                                style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 15px; font-size: 15px;">
                                <div><strong>📅 Data:</strong> <?php echo date('d/m/Y H:i', strtotime($activation->created_at)); ?>
                                </div>
                                <div><strong>📍 Monuments:</strong> <?php echo $activation->points_count ?? 0; ?> monuments</div>
                                <div><strong>🏆 Puntuació:</strong> <?php echo $activation->total_weight ?? 'N/A'; ?></div>
                            </div>

                            <!-- Documents -->
                            <?php if (!empty($activation->documents) && is_array($activation->documents)): ?>
                                <div class="activation-documents" style="margin-bottom: 15px; font-size: 14px;">
                                    <strong>📎 Documents:</strong>
                                    <?php foreach ($activation->documents as $doc): ?>
                                        <a href="<?php echo esc_url($doc['url']); ?>" target="_blank"
                                            style="display: inline-block; margin-left: 10px; color: #0073aa; text-decoration: none;">
                                            📄 <?php echo esc_html($doc['name']); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Actions - BOTONS MÉS GRANS -->
                            <div class="activation-actions" style="display: flex; gap: 10px; justify-content: flex-end;">
                                <button onclick="viewDetails(<?php echo $activation->id; ?>)" class="button" style="font-size: 14px;">
                                    Detalls
                                </button>
                                <button onclick="confirmarActivacio(<?php echo $activation->id; ?>)" class="button button-primary"
                                    style="font-size: 14px; background: #28a745;">
                                    Confirmar
                                </button>
                                <button onclick="rebutjarActivacio(<?php echo $activation->id; ?>)" class="button"
                                    style="font-size: 14px; background: #dc3545; color: white;">
                                    Rebutjar
                                </button>
                            </div>
                        </div>

                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- JavaScript per les funcions -->
        <script>
            // Variables globals necessàries
            var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
            var mapesAdminNonce = '<?php echo wp_create_nonce('mapes_admin_nonce'); ?>';

            // Funcions pels botons
            function confirmarActivacio(activationId) {
                if (!confirm('Confirmar aquesta activació? Aquesta acció no es pot desfer.')) {
                    return;
                }

                console.log('🔄 Confirmant activació:', activationId);

                fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'mapes_confirm_activation',
                        activation_id: activationId,
                        nonce: mapesAdminNonce
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        console.log('✅ Resposta confirmació:', data);
                        if (data.success) {
                            alert('✅ Activació confirmada correctament!');
                            location.reload();
                        } else {
                            alert('❌ Error: ' + (data.data || 'Error desconegut'));
                        }
                    })
                    .catch(error => {
                        console.error('❌ Error confirmació:', error);
                        alert('❌ Error de connexió');
                    });
            }

            function rebutjarActivacio(activationId) {
                const motiu = prompt('Motiu del rebuig (opcional):');
                if (motiu === null) return; // Cancel·lat

                console.log('🔄 Rebutjant activació:', activationId, 'Motiu:', motiu);

                fetch(ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'mapes_reject_activation',
                        activation_id: activationId,
                        rejection_reason: motiu || '',
                        nonce: mapesAdminNonce
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        console.log('✅ Resposta rebuig:', data);
                        if (data.success) {
                            alert('✅ Activació rebutjada correctament.');
                            location.reload();
                        } else {
                            alert('❌ Error: ' + (data.data || 'Error desconegut'));
                        }
                    })
                    .catch(error => {
                        console.error('❌ Error rebuig:', error);
                        alert('❌ Error de connexió');
                    });
            }

            function viewDetails(activationId) {
                console.log('👁️ Veient detalls:', activationId);
                alert('Detalls per activació #' + activationId + ' (per implementar)');
            }
        </script>
        <?php
    }


}
