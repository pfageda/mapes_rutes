<?php if (!defined('ABSPATH'))
    exit; ?>

<div class="mapes-route-item" onclick="selectRoute('<?php echo $app_id; ?>', '<?php echo $route->id; ?>')">
    <div class="mapes-route-info">
        <div class="mapes-route-color" style="background: <?php echo esc_attr($route->color); ?>"></div>
        <span class="mapes-route-code"><?php echo esc_html($route->code); ?></span>
        <span class="mapes-route-name">- <?php echo esc_html($route->name); ?></span>
    </div>
    <div class="mapes-route-actions">
        <button onclick="event.stopPropagation(); editRoute('<?php echo $route->id; ?>')" title="Editar">✏</button>
        <button onclick="event.stopPropagation(); deleteRoute('<?php echo $route->id; ?>')" title="Eliminar">×</button>
    </div>
</div>