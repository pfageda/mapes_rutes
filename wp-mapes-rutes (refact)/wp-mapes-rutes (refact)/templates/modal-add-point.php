<?php if (!defined('ABSPATH'))
    exit; ?>

<div id="modal-add-point-<?php echo $app_id; ?>" class="mapes-modal">
    <div class="mapes-modal-content" style="width: 500px; max-width: 90vw;">
        <div class="mapes-modal-header">
            <h3>Afegir Monument</h3>
            <button onclick="closeModal('modal-add-point-<?php echo $app_id; ?>')">×</button>
        </div>
        <div style="padding: 20px;">
            <form onsubmit="submitAddPoint('<?php echo $app_id; ?>', event)">

                <!-- NOM DEL PUNT (OBLIGATORI) -->
                <div class="mapes-form-group">
                    <label>Nom *</label>
                    <input type="text" name="title" placeholder="Nom del monument">
                </div>

                <!-- DESCRIPCIÓ (OPCIONAL) -->
                <div class="mapes-form-group">
                    <label>Descripció</label>
                    <textarea name="description" placeholder="Descripció opcional..." rows="3"></textarea>
                </div>

                <!-- SELECTOR DE MODE UBICACIÓ -->
                <div class="mapes-form-group">
                    <label>Tipus ubicació</label>
                    <div class="mapes-input-toggle">
                        <button type="button" class="active"
                            onclick="toggleInputType('<?php echo $app_id; ?>', 'location')">Nom lloc</button>
                        <button type="button"
                            onclick="toggleInputType('<?php echo $app_id; ?>', 'coordinates')">Coordenades</button>
                    </div>
                </div>

                <!-- MODE UBICACIÓ PER NOM -->
                <div class="mapes-form-group" id="location-input-<?php echo $app_id; ?>">
                    <label>Ubicació *</label>
                    <input type="text" name="location_name" placeholder="ex: Sagrada Familia Barcelona">

                    <!-- ⭐ NOUS CAMPS - NOMÉS PER UBICACIÓ PER NOM -->
                    <div style="margin-top: 15px;">
                        <div class="mapes-form-group">
                            <label>Població *</label>
                            <input type="text" name="poblacio" placeholder="Ex: Barcelona" required>
                        </div>

                        <div class="mapes-form-group">
                            <label>Província *</label>
                            <select name="provincia" required>
                                <option value="">Selecciona província</option>
                                <option value="Barcelona">Barcelona</option>
                                <option value="Girona">Girona</option>
                                <option value="Lleida">Lleida</option>
                                <option value="Tarragona">Tarragona</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- MODE COORDENADES DIRECTES -->
                <div class="mapes-form-group" id="coordinates-input-<?php echo $app_id; ?>" style="display: none;">
                    <div class="mapes-coordinates-grid">
                        <div>
                            <label>Latitud *</label>
                            <input type="number" step="any" name="lat" placeholder="41.4036">
                        </div>
                        <div>
                            <label>Longitud *</label>
                            <input type="number" step="any" name="lng" placeholder="2.1744">
                        </div>
                    </div>
                    <!-- NO hi ha camps poblacio/provincia aquí -->
                </div>

                <!-- BOTONS D'ACCIÓ -->
                <div class="mapes-form-actions">
                    <button type="submit" class="mapes-btn primary">Afegir Monument</button>
                    <button type="button" class="mapes-btn secondary"
                        onclick="closeModal('modal-add-point-<?php echo $app_id; ?>')">Cancel·lar</button>
                </div>
            </form>
        </div>
    </div>
</div>