<?php
// data validation helper file 
function validateString($value, $min, $max) {
    return isset($value) 
        && is_string($value) 
        && strlen(trim($value)) >= $min 
        && strlen($value) <= $max;
}

function validateNumber($value, $min, $max) {
    return isset($value) 
        && is_numeric($value) 
        && $value >= $min 
        && $value <= $max;
}
?>
