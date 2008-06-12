<?php
    
    // This url is triggered when a feebee is unplugged
    
    parse_str($_SERVER['QUERY_STRING']); 
    session_id($session_to_unset);
    session_start();
    session_unset();
?>
