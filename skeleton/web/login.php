<?php

Layout::open('default')->activate();

// Get the reference url to redirect back
function reference_url()
{
	$path_chunks = explode('/', $_SERVER['PATH_INFO']);
	$path_chunks =  array_filter($path_chunks,
	    create_function('$c', 'return (($c != "+login") && ($c != "+logout"));')
    );
	return url(implode('/', $path_chunks));
}
	        	
// Logout user if there is someone logged on
Stupid::add_rule(create_function('', 'Auth_Realm::clear_identity(); Net_HTTP_Response::redirect(reference_url());'),
	array('type' => 'url_path', 'chunk[-1]' => '/\+logout/'));
Stupid::chain_reaction();

// Login form
if (! Auth_Realm::has_identity())
{   
	$form = new UI_LoginForm(reference_url());
	etag('div', $form->render());
}
else
	Net_HTTP_Response::redirect(reference_url());
    
?>
