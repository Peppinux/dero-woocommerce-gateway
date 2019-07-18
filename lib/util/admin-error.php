<?php
function admin_error($message) {
    if(is_admin()) {
        echo 'ERROR: ' . $message;
    }
}
?>
