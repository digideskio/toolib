<?php

//! Create an absolute url based on root file
function url($relative)
{
    if (! strstr($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']))
    return dirname($_SERVER['SCRIPT_NAME']) . $relative;
    return $_SERVER['SCRIPT_NAME'] . $relative;
}

//! Create an absolute url for static content
function surl($relative)
{
    return dirname($_SERVER['SCRIPT_NAME']) . $relative;
}
?>
