<?php

// Use default layout to render this page
Layout::open('default')->activate();
Layout::open('default')->get_document()->title = Config::get('site.title') . ' Section 1';

echo 'You can edit section1 in file ';
etag('strong', __FILE__);
echo '<br><pre>';
?>
