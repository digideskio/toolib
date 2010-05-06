<?php
// Use default layout to render this page
Layout::open('default')->activate();

echo 'This is the home page!';
etag('br');
echo 'You can change it by editing ';
etag('strong',  __FILE__);
?>
