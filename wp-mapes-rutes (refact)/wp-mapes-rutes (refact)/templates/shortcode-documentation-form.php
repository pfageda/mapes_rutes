<?php if (!defined('ABSPATH'))
    exit; ?>

<div class="mapes-documentation-form-page">
    <div class="form-header">
        <h2>📎 Enviar Documentació</h2>
        <p>Pugeu la documentació de la vostra activitat radioaficionada.</p>
    </div>

    <form id="documentation-form" class="mapes-documentation-form" enctype="multipart/form-data">
        <input type="hidden" name="activitat_id" value="<?php echo esc_attr($atts['activitat_id']); ?>">

        <div class="form-section">
            <h3>📋 Fitxers d'Activitat</h3>

            <div class="form-group">
                <label>Fitxer ADI:</label>
                <input type="file" name="fitxer_adi" accept=".adi">
                <small>Format .adi amb els contactes realitzats</small>
            </div>

            <div class="form-group">
                <label>Imatges de l'activitat (màxim 5, 600KB cada una):</label>
                <input type="file" name="imatge_0" accept=".jpg,.jpeg,.png">
                <input type="file" name="imatge_1" accept=".jpg,.jpeg,.png">
                <input type="file" name="imatge_2" accept=".jpg,.jpeg,.png">
                <input type="file" name="imatge_3" accept=".jpg,.jpeg,.png">
                <input type="file" name="imatge_4" accept=".jpg,.jpeg,.png">
                <small>Formats: JPG, PNG. Mida màxima: 600KB per imatge</small>
            </div>

            <div class="form-group">
                <label>Documentació PDF:</label>
                <input type="file" name="fitxer_pdf" accept=".pdf">
                <small>Document complementari en format PDF</small>
            </div>
        </div>

        <div class="form-actions">
            <button type="submit" class="mapes-btn primary large">📤 Enviar Documentació</button>
            <a href="javascript:history.back()" class="mapes-btn secondary large">← Tornar enrere</a>
        </div>
    </form>
</div>

<script>
    document.getElementById('documentation-form').addEventListener('submit', function (e) {
        e.preventDefault();

        // Validar mides d'imatges
        const imageInputs = this.querySelectorAll('input[type="file"][name^="imatge_"]');
        for (let input of imageInputs) {
            if (input.files[0] && input.files[0].size > 600 * 1024) {
                alert('La imatge és massa gran. Màxim 600KB per imatge.');
                return;
            }
        }

        const formData = new FormData(this);
        formData.append('action', 'mapes_upload_documentation');
        formData.append('nonce', '<?php echo wp_create_nonce("mapes_nonce"); ?>');

        // Mostrar indicador de càrrega
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = '⏳ Enviant...';
        submitBtn.disabled = true;

        fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Documentació enviada correctament!');
                    window.location.href = '/';
                } else {
                    alert('Error: ' + data.data);
                }
            })
            .catch(error => {
                alert('Error de connexió: ' + error.message);
            })
            .finally(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
    });
</script>