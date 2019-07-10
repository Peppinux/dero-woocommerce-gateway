<?php
function format_time($seconds) {
    $time_format = $seconds . ' seconds';
    if($seconds >= 86400) {
        $time_format = 'about ' . floor($seconds / 86400) . ' days';
    }
    else if($seconds >= 3600) {
        $time_format = 'about ' . floor($seconds / 3600) . ' hours';
    }
    else if($seconds >= 60) {
        $time_format = 'about '. floor($seconds / 60) . ' minutes'; 
    }
    return $time_format;
}
?>
