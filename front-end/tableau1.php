<?php
if (!function_exists('zoomVeFunction')) {
    function zoomVeFunction() {
        ob_start();
        // change couleur rouge en aqua
        ?>
        <h1 style="color: aqu; font-size: 30px;">TEST</h1>
        <?php
        return ob_get_clean();
    }
}

add_shortcode('zoom_ve', 'zoomVeFunction');