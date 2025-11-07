<?php if (!defined('ABSPATH'))
    exit; ?>

<div id="<?php echo $app_id; ?>" class="mapes-activations-admin">
    <!-- Header -->
    <div class="mapes-admin-header">
        <h2>📋 Gestió d'Activacions</h2>
        <a href="/mapes-app/" class="mapes-btn secondary">← Tornar al Mapa</a>
    </div>

    <!-- Estadístiques d'Activacions -->
    <div class="mapes-stats activations-stats">
        <div class="mapes-stat-card created">
            <div class="mapes-stat-label">CREADES</div>
            <div class="mapes-stat-number"><?php echo $stats['creades'] ?? 0; ?></div>
        </div>
        <div class="mapes-stat-card pending">
            <div class="mapes-stat-label">PENDENTS</div>
            <div class="mapes-stat-number"><?php echo $stats['pendents'] ?? 0; ?></div>
        </div>
        <div class="mapes-stat-card confirmed">
            <div class="mapes-stat-label">CONFIRMADES</div>
            <div class="mapes-stat-number"><?php echo $stats['confirmades'] ?? 0; ?></div>
        </div>
    </div>

    <!-- Secció Pendents de Confirmació (Prioritària) -->
    <div class="activations-section priority">
        <div class="section-header">
            <h3>⚠️ Pendents de Confirmació</h3>
            <span class="section-count"><?php echo count($pending_activations ?? []); ?> activacions</span>
        </div>

        <div class="activations-grid">
            <?php if (empty($pending_activations)): ?>
                <div class="empty-state">
                    <div class="empty-icon">✅</div>
                    <p>No hi ha activacions pendents de confirmació</p>
                </div>
            <?php else: ?>
                <?php foreach ($pending_activations as $activation): ?>
                    <div class="activation-card pending" data-id="<?php echo $activation->id; ?>">
                        <div class="activation-header">
                            <div class="activation-title">
                                <strong><?php echo esc_html($activation->route_code ?? 'N/A'); ?></strong>
                                <small>per <?php echo esc_html($activation->user_name ?? 'Usuari desconegut'); ?></small>
                            </div>
                            <div class="activation-status pending">
                                ⏳ <?php echo $activation->status === 'creada' ? 'Creada' : 'Finalitzada'; ?>
                            </div>
                        </div>

                        <div class="activation-info">
                            <div class="info-item">
                                <strong>📅 Data:</strong> <?php echo date('d/m/Y H:i', strtotime($activation->created_at)); ?>
                            </div>
                            <div class="info-item">
                                <strong>Monuments activats:</strong> <?php echo $activation->points_count ?? 0; ?>
                            </div>
                        </div>

                        <!-- Documents pujats -->
                        <?php if (!empty($activation->documents)): ?>
                            <div class="activation-documents">
                                <strong>📄 Documents pujats:</strong>
                                <div class="documents-list">
                                    <?php foreach ($activation->documents as $doc): ?>
                                        <a href="<?php echo esc_url($doc->file_url); ?>" target="_blank" class="doc-link">
                                            📄 <?php echo esc_html($doc->file_name); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="activation-actions">
                            <button class="mapes-btn success small" onclick="confirmActivation(<?php echo $activation->id; ?>)">
                                Confirmar
                            </button>
                            <button class="mapes-btn danger small" onclick="rejectActivation(<?php echo $activation->id; ?>)">
                                Rebutjar
                            </button>
                            <button class="mapes-btn secondary small"
                                onclick="showActivationDetails(<?php echo $activation->id; ?>)">
                                Detalls
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- ✅ NOVA SECCIÓ: ACTIVACIONS CONFIRMADES -->
    <div class="activations-section">
        <div class="section-header">
            <h3>✅ Activacions Confirmades</h3>
            <span class="section-count"><?php echo count($confirmed_activations ?? []); ?> activacions</span>
        </div>

        <div class="activations-table">
            <?php if (empty($confirmed_activations)): ?>
                <div class="empty-state">
                    <div class="empty-icon">✅</div>
                    <p>No hi ha activacions confirmades</p>
                </div>
            <?php else: ?>
                <table class="mapes-table">
                    <thead>
                        <tr>
                            <th>Ruta</th>
                            <th>Usuari</th>
                            <th>Data creació</th>
                            <th>Estat</th>
                            <th>Monuments</th>
                            <th>Pes</th>
                            <th>Accions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($confirmed_activations as $activation): ?>
                            <tr data-id="<?php echo $activation->id; ?>">
                                <td><strong><?php echo esc_html($activation->route_code ?? 'N/A'); ?></strong></td>
                                <td><?php echo esc_html($activation->username ?? 'Usuari desconegut'); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($activation->created_at)); ?></td>
                                <td><span class="status-badge confirmed">Confirmada</span></td>
                                <td><?php echo intval($activation->points_count ?: 1); ?></td>
                                <td>
                                    <span class="weight-progress">
                                        <?php
                                        $weight_obtained = $activation->total_weight ?? 0;
                                        $total_weight = $activation->total_route_weight ?? $weight_obtained;
                                        echo number_format($weight_obtained, 1) . '/' . number_format($total_weight, 1);
                                        ?>
                                    </span>
                                    <?php if ($total_weight > 0): ?>
                                        <div class="weight-bar"
                                            style="width: 60px; height: 4px; background: #eee; border-radius: 2px; margin-top: 2px;">
                                            <div
                                                style="width: <?php echo min(100, ($weight_obtained / $total_weight) * 100); ?>%; height: 100%; background: #28a745; border-radius: 2px;">
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <button class="action-btn view"
                                        onclick="showActivationDetails(<?php echo $activation->id; ?>)"
                                        title="Veure detalls">👁️</button>
                                    <button class="action-btn edit" onclick="editActivation(<?php echo $activation->id; ?>)"
                                        title="Editar">✏️</button>
                                    <button class="action-btn delete" onclick="deleteActivation(<?php echo $activation->id; ?>)"
                                        title="Esborrar">🗑️</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Secció Activacions Creades -->
    <div class="activations-section">
        <div class="section-header">
            <h3>📝 Activacions Creades</h3>
            <span class="section-count"><?php echo count($created_activations ?? []); ?> activacions</span>
        </div>

        <div class="activations-table">
            <?php if (empty($created_activations)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📋</div>
                    <p>No hi ha activacions creades</p>
                </div>
            <?php else: ?>
                <table class="mapes-table">
                    <thead>
                        <tr>
                            <th>Ruta</th>
                            <th>Usuari</th>
                            <th>Data creació</th>
                            <th>Estat</th>
                            <th>Monuments</th>
                            <th>Pes</th>
                            <th>Accions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($created_activations as $activation): ?>
                            <tr data-id="<?php echo $activation->id; ?>">
                                <td><strong><?php echo esc_html($activation->route_code ?? 'N/A'); ?></strong></td>
                                <td><?php echo esc_html($activation->user_name ?? 'Usuari desconegut'); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($activation->created_at)); ?></td>
                                <td><span
                                        class="status-badge <?php echo $activation->status ?? 'unknown'; ?>"><?php echo ucfirst($activation->status ?? 'Desconegut'); ?></span>
                                </td>
                                <td><?php echo $activation->points_count ?? 0; ?></td>
                                <td>
                                    <span class="weight-progress">
                                        <?php
                                        $weight_obtained = $activation->total_weight ?? 0;
                                        $total_weight = $activation->total_route_weight ?? 0;
                                        echo number_format($weight_obtained, 1) . '/' . number_format($total_weight, 1);
                                        ?>
                                    </span>
                                    <?php if ($total_weight > 0): ?>
                                        <div class="weight-bar"
                                            style="width: 60px; height: 4px; background: #eee; border-radius: 2px; margin-top: 2px;">
                                            <div
                                                style="width: <?php echo min(100, ($weight_obtained / $total_weight) * 100); ?>%; height: 100%; background: #1e81b0; border-radius: 2px;">
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="actions">
                                    <button class="action-btn view"
                                        onclick="showActivationDetails(<?php echo $activation->id; ?>)"
                                        title="Veure detalls">👁️</button>
                                    <button class="action-btn edit" onclick="editActivation(<?php echo $activation->id; ?>)"
                                        title="Editar">✏️</button>
                                    <button class="action-btn delete" onclick="deleteActivation(<?php echo $activation->id; ?>)"
                                        title="Esborrar">🗑️</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>