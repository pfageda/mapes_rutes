<?php if (!defined('ABSPATH'))
    exit; ?>

<div class="mapes-point-item" onclick="selectPoint('<?php echo $point->id; ?>')">
    <div class="mapes-point-info">
        <div class="mapes-point-number"><?php echo $index + 1; ?></div>
        <div class="mapes-point-details">
            <div class="mapes-point-title"><?php echo esc_html($point->title); ?></div>
            <?php if ($point->description): ?>
                <div class="mapes-point-description" title="<?php echo esc_attr($point->description); ?>">
                    <?php
                    $short_desc = strlen($point->description) > 50
                        ? substr($point->description, 0, 47) . '...'
                        : $point->description;
                    echo esc_html($short_desc);
                    ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="mapes-point-actions">
        <button onclick="event.stopPropagation(); editPoint('<?php echo $point->id; ?>')" title="Editar">✏</button>
        <button onclick="event.stopPropagation(); deletePoint('<?php echo $point->id; ?>')" title="Eliminar">×</button>
    </div>
</div>