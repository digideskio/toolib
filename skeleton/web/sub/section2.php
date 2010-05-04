<?php

// Use default layout to render this page
Layout::open('default')->activate();
Layout::open('default')->menu->add_link('Section 3', '/section3');

echo 'Section 2 deliberally adds a dynamic menu button that points nowhere!';
etag('br');
echo 'You can edit section2 in file ';
etag('strong', __FILE__);
?>
