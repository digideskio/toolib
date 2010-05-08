<?php
require_once dirname(__FILE__) . '/bootstrap.php';

// File names
$fn_config = dirname(__FILE__) . '/config.inc.php';
$fn_htaccess = dirname(__FILE__) . '/.htaccess';


$dl = Layout::create('debug')->activate();
$dl->get_document()->title = 'Installation';
$dl->get_document()->add_ref_css(surl('/static/css/default.css'));

etag('h2', 'PHPLibs Skeleton');
etag('h3', 'Installation process');

// Make checks for writable files
if (! is_writable($fn_config))
{
    etag('div class="error" nl_escape_on', 'Cannot continue installing skeleton...
        The configuration file "config.inc.php" must be writable, you can change
        permissions and retry installation.');

    exit;
}

$f = new UI_InstallationForm($fn_config);
etag('div', $f->render());
?>