<?php if (!defined('ABSPATH'))
    exit; ?>

<div class="mapes-color-legend">
    <h4>📡 Llegenda d'Activacions</h4>
    <div class="legend-items">
        <div class="legend-item">
            <span class="legend-color" style="background: #32CD32;"></span>
            <span class="legend-text">Mai activat</span>
        </div>
        <div class="legend-item">
            <span class="legend-color" style="background: #FF4444;"></span>
            <span class="legend-text">Confirmat (< 3 anys)</span>
        </div>
        <div class="legend-item">
            <span class="legend-color" style="background: #808000;"></span>
            <span class="legend-text">Pendent confirmació</span>
        </div>
        <div class="legend-item">
            <span class="legend-color" style="background: #FFD700;"></span>
            <span class="legend-text">Confirmat (> 3 anys)</span>
        </div>
    </div>
</div>

<style>
    .mapes-color-legend {
        background: white;
        padding: 15px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        margin-bottom: 15px;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    .mapes-color-legend h4 {
        margin: 0 0 12px 0;
        color: #333;
        font-size: 16px;
    }

    .legend-items {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 140px;
    }

    .legend-color {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        border: 2px solid #333;
        flex-shrink: 0;
    }

    .legend-text {
        font-size: 13px;
        color: #555;
    }

    /* Responsiu */
    @media (max-width: 600px) {
        .legend-items {
            flex-direction: column;
            gap: 10px;
        }

        .legend-item {
            min-width: auto;
        }
    }
</style>