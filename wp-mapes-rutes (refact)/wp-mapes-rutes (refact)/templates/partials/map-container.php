<div class="mapes-map-container">
    <?php
    // Mostrar llegenda de colors
    $legend_path = plugin_dir_path(__FILE__) . '../legend-colors.php';
    if (file_exists($legend_path)) {
        include $legend_path;
    }
    ?>
    <div id="map-<?php echo $app_id; ?>" class="mapes-map" style="height: <?php echo esc_attr($atts['height']); ?>;">
        <div class="mapes-map-loading">⏳ Carregant mapa...</div>
    </div>

    <!-- AFEGIR AQUEST CONTENIDOR D'EDICIÓ -->
    <div id="edit-panel-<?php echo $app_id; ?>" class="mapes-edit-panel" style="display: none;">
        <div class="mapes-edit-header">
            <h3 id="edit-title-<?php echo $app_id; ?>">Editar Monument</h3>
            <button onclick="cancelEdit('<?php echo $app_id; ?>')">✕ Tancar</button>
        </div>
        <div class="mapes-edit-content" id="edit-content-<?php echo $app_id; ?>">
            <!-- El formulari s'injectarà aquí -->
        </div>
    </div>
</div>