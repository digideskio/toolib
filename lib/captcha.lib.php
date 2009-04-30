<?php

// Create a new math captcha
function captcha_math_create()
{   $sign = rand(1 ,2);

    switch($sign)
    {
    case 1: // +
        $sign = '+';
        $digit_1 = rand(1, 20);
        $digit_2 = rand(1, 30);
        break;
    case 2: // -
        $sign = '-';
        $digit_1 = rand(10, 30);
        $digit_2 = rand(1, $digit_1 - 1);
        break;
    }

    $_SESSION['captcha']['math'] = eval("return $digit_1 $sign $digit_2;");

    return "$digit_1 $sign $digit_2 =";
}

// Validate captch
function captcha_math_is_valid($reply)
{   $best = $_SESSION['captcha']['math'];
    $valid = false;

    // Check if it is valid
    if (($reply != "") && ($reply == $best))
        $valid = true;
        
    // Remove it from memory
    unset($_SESSION['captcha']);
    
    return $valid;
}

?>
