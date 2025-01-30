<?php
if (!function_exists('move_ve_function')) {
    function move_ve_function() {
        ob_start();
        ?>
        <h1 style="color: red; font-size: 30px;">TEST</h1>
        <?php
        return ob_get_clean();
    }
}


add_shortcode('move_ve', 'move_ve_function');