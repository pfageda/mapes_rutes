<?php if (!defined('ABSPATH'))
    exit; ?>

<div id="modal-create-route-<?php echo $app_id; ?>" class="mapes-modal">
    <div class="mapes-modal-content" style="width: 600px; max-width: 90vw;">
        <div class="mapes-modal-header">
            <h3>Crear Nova Ruta</h3>
            <button onclick="closeModal('modal-create-route-<?php echo $app_id; ?>')">×</button>
        </div>
        <div style="padding: 20px;">
            <form onsubmit="submitCreateRoute('<?php echo $app_id; ?>', event)">
                <div class="mapes-form-group">
                    <label>Codi Ruta *</label>
                    <input type="text" name="code" placeholder="ex: R001" required>
                </div>

                <div class="mapes-form-group">
                    <label>Nom Ruta *</label>
                    <input type="text" name="name" placeholder="ex: Ruta Turística Barcelona" required>
                </div>

                <div class="mapes-form-group">
                    <label>Color</label>
                    <div class="mapes-color-picker">
                        <button type="button" class="mapes-color-btn active" style="background: #000000;"
                            onclick="selectColor(this, '#000000')"></button>
                        <button type="button" class="mapes-color-btn" style="background: #404040;"
                            onclick="selectColor(this, '#404040')"></button>
                        <button type="button" class="mapes-color-btn" style="background: #CC0000;"
                            onclick="selectColor(this, '#CC0000')"></button>
                        <button type="button" class="mapes-color-btn" style="background: #003366;"
                            onclick="selectColor(this, '#003366')"></button>
                        <button type="button" class="mapes-color-btn" style="background: #006600;"
                            onclick="selectColor(this, '#006600')"></button>
                    </div>
                    <input type="hidden" name="color" value="#000000">
                </div>

                <div class="mapes-form-group">
                    <label>Monuments de la Ruta</label>
                    <div class="mapes-route-points-editor">
                        <?php if (empty($points)): ?>
                            <div class="mapes-empty">No hi ha monuments disponibles</div>
                        <?php else: ?>
                            <?php foreach ($points as $index => $point): ?>
                                <div class="mapes-route-point-item">
                                    <div class="mapes-route-point-check">
                                        <input type="checkbox" name="points[]" value="<?php echo $point->id; ?>"
                                            onchange="togglePointControls(this)">
                                    </div>
                                    <div class="mapes-route-point-name">
                                        <?php echo esc_html($point->title); ?>
                                    </div>
                                    <div class="mapes-route-point-controls" style="display: none;">
                                        <div class="mapes-control-group">
                                            <label>Ordre:</label>
                                            <input type="number" min="1" max="100" value="<?php echo $index + 1; ?>">
                                        </div>
                                        <div class="mapes-control-group">
                                            <label>Pes:</label>
                                            <input type="number" min="5" max="100" step="5" value="5">
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mapes-form-actions">
                    <button type="submit" class="mapes-btn primary">Crear Ruta</button>
                    <button type="button" class="mapes-btn secondary"
                        onclick="closeModal('modal-create-route-<?php echo $app_id; ?>')">Cancel·lar</button>
                </div>
            </form>
        </div>
    </div>
</div>