<?php
Layout::open('default')->activate();

header("HTTP/1.1 404 Not Found");

etag('div class="not-found"',
tag('h1 class="error"', "Not Found: \"{$_SERVER['REQUEST_URI']}\" "),
tag('p', 'Sorry we were unable to find any information about this url. ')
);
exit;
?>
