<?php if (!defined('ABSPATH'))
    exit; ?>

<!-- Modal Finalitzar Activitat -->
<div id="modal-finalize-activity" class="mapes-modal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; justify-content: center; align-items: center;">
    <div class="mapes-modal-content"
        style="background: white; border-radius: 8px; padding: 0; max-width: 500px; width: 90%; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
        <div class="mapes-modal-header"
            style="padding: 20px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; color: #333;">🎯 Finalitzar Activitat</h3>
            <button onclick="closeModal('modal-finalize-activity')"
                style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">&times;</button>
        </div>

        <div style="padding: 20px;">
            <p style="margin-bottom: 20px; color: #666;">
                Introduïu el vostre email i el codi d'activació rebut per correu per finalitzar l'activitat.
            </p>

            <form id="finalize-activity-form" onsubmit="submitFinalizeActivity(event)">
                <div class="mapes-form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">📧 Correu electrònic</label>
                    <input type="email" id="finalize-email" name="email" required placeholder="mail@exemple.com"
                        style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;">
                </div>
                <div class="mapes-form-group" style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">🔑 Codi d'activació</label>
                    <input type="text" id="finalize-activation-code" name="activationcode" required
                        placeholder="ABCD123456" maxlength="10"
                        style="width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; text-transform: uppercase; box-sizing: border-box;">
                    <small style="color: #666;">Codi de 10 caràcters rebut per correu electrònic</small>
                </div>
                <div class="mapes-form-actions" style="display: flex; gap: 10px; margin-top: 25px;">
                    <button type="submit" class="mapes-btn primary"
                        style="flex: 1; padding: 12px; background: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer;">
                        ✅ Validar i Continuar
                    </button>
                    <button type="button" onclick="closeModal('modal-finalize-activity')" class="mapes-btn secondary"
                        style="padding: 12px 20px; background: #f1f1f1; color: #333; border: none; border-radius: 4px; cursor: pointer;">
                        ❌ Cancel·lar
                    </button>
                </div>
            </form>

            <!-- Missatge de resultat -->
            <div id="finalize-result" style="display: none; margin-top: 20px; padding: 15px; border-radius: 6px;">
                <p id="finalize-message" style="margin: 0;"></p>
            </div>
        </div>
    </div>
</div>