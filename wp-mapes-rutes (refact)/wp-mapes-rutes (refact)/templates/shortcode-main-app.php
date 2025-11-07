<?php
/**
 * Template principal per l'aplicació de mapes
 */

if (!defined('ABSPATH'))
    exit;
?>

<div id="<?php echo $app_id; ?>" class="mapes-complete-app">
    <!-- Panell lateral -->
    <div class="mapes-sidebar">
        <!-- Estadístiques -->
        <div class="mapes-stats">
            <div class="mapes-stat-card">
                <div class="mapes-stat-label">MONUMENTS</div>
                <div class="mapes-stat-number"><?php echo count($points); ?></div>
            </div>
            <div class="mapes-stat-card">
                <div class="mapes-stat-label">RUTES</div>
                <div class="mapes-stat-number"><?php echo count($routes); ?></div>
            </div>
        </div>

        <!-- NOU BOTÓ ACTIVACIONS IDÈNTIC AL ROUTES-TOGGLE -->
        <div class="mapes-admin-actions" style="margin-top: 15px;">
            <button type="button" onclick="window.location.href='/gestio-activacions/'" class="mapes-routes-toggle">
                📋 Activacions
            </button>
        </div>

        <!-- Selector de rutes -->
        <div class="mapes-routes-selector">
            <button class="mapes-routes-toggle" onclick="toggleRoutes('<?php echo $app_id; ?>')">
                Rutes (<?php echo count($routes); ?>) <span id="routes-toggle-<?php echo $app_id; ?>">+</span>
            </button>
            <div id="routes-list-<?php echo $app_id; ?>" class="mapes-routes-list" style="display: none;">
                <?php foreach ($routes as $route): ?>
                    <?php include WP_MAPES_TEMPLATES_PATH . 'partials/route-list-item.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Selector de monuments -->
        <div class="mapes-points-selector">
            <button class="mapes-points-toggle" onclick="togglePoints('<?php echo $app_id; ?>')">
                Monuments (<?php echo count($points); ?>) <span id="points-toggle-<?php echo $app_id; ?>">+</span>
            </button>
            <div id="points-list-<?php echo $app_id; ?>" class="mapes-points-list" style="display: none;">
                <div class="mapes-list-content">
                    <?php if (empty($points)): ?>
                        <div class="mapes-empty">No hi ha monuments creats</div>
                    <?php else: ?>
                        <?php foreach ($points as $index => $point): ?>
                            <?php include WP_MAPES_TEMPLATES_PATH . 'partials/point-list-item.php'; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Botons d'acció -->
        <button class="mapes-btn primary" onclick="openModal('modal-create-route-<?php echo $app_id; ?>')">
            + Crear Nova Ruta
        </button>
        <button class="mapes-btn secondary" onclick="openModal('modal-add-point-<?php echo $app_id; ?>')">
            + Afegir Monument
        </button>
    </div>

    <!-- Mapa -->
    <?php include WP_MAPES_TEMPLATES_PATH . 'partials/map-container.php'; ?>
</div>

<!-- PANELL D'EDICIÓ GENÈRIC -->
<div id="edit-panel-<?php echo $app_id; ?>" class="mapes-edit-panel" style="display: none;">
    <div class="mapes-edit-header">
        <h3 id="edit-title-<?php echo $app_id; ?>">Editar Element</h3>
        <button type="button" onclick="cancelEdit('<?php echo $app_id; ?>')" class="mapes-close-btn">×</button>
    </div>
    <div id="edit-content-<?php echo $app_id; ?>" class="mapes-edit-content">
        <!-- Contingut dinàmic generat per JavaScript -->
    </div>
</div>

<!-- Modals -->
<?php
include WP_MAPES_TEMPLATES_PATH . 'modal-add-point.php';
include WP_MAPES_TEMPLATES_PATH . 'modal-create-route.php';
?>

<script>
    function cancelEdit(appId) {
        const editPanel = document.getElementById(`edit-panel-${appId}`);
        if (editPanel) {
            editPanel.style.display = "none";
        }
    }
</script>