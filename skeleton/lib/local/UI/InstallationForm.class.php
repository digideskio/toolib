<?php
/*
 *  This file is part of PHPLibs <http://phplibs.kmfa.net/>.
 *  
 *  Copyright (c) 2010 < squarious at gmail dot com > .
 *  
 *  PHPLibs is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *  
 *  PHPLibs is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  
 *  You should have received a copy of the GNU General Public License
 *  along with PHPLibs.  If not, see <http://www.gnu.org/licenses/>.
 *  
 */


class UI_InstallationForm extends Output_HTML_Form
{
    public $config_file;
    
    public function __construct($config_file, $relative_folder)
    {
        $this->config_file = $config_file;
        
        parent::__construct(array(
            'db' => array('type' => 'custom', 'value' => '<h4>Database Options</h4>'),
			'db-host' => array('display' => 'Host', 'regcheck' => '/^.+$/',
				'onerror' => 'This field is mandatory.'),
            'db-schema' => array('display' => 'Database', 'regcheck' => '/^.+$/',
				'onerror' => 'This field is mandatory.'),
			'db-user' => array('display' => 'Username', 'regcheck' => '/^.+$/',
				'onerror' => 'This field is mandatory.'),
			'db-pass' => array('display' => 'Password', 'type' => 'password', 'regcheck' => '/^.+$/',
				'onerror' => 'This field is mandatory.'),
			'db-pass2' => array('display' => '', 'type' => 'password', 'regcheck' => '/^.+$/',
				'onerror' => 'This field is mandatory.'),
        ),
        array('title' => '', 'css' => array('ui-form', 'ui-installation'),
		    'buttons' => array(
		        'Save' => array('display' =>'Install'),
                )
            )
        );
    }

    public function on_post()
    {   $values = $this->field_values();
    
        if ($this->get_field_value('db-pass') != $this->get_field_value('db-pass2'))
        {
            $this->invalidate_field('db-pass2', 'The two password are not the same');
        }
        
        if ($this->is_valid())
        {
            // Try to connect
            if (!DB_Conn::connect($values['db-host'], $values['db-user'], $values['db-pass'], $values['db-schema']))
                $this->invalidate_field('db-host', 'Error connecting on database.');
        }
    }
    
    public function on_valid($values)
    {
        Config::set('db.host', $values['db-host']);
        Config::set('db.user', $values['db-user']);
        Config::set('db.pass', $values['db-pass']);
        Config::set('db.schema', $values['db-schema']);
        
        $data = "<?php\n// File generated with install.php\n";
        	
        foreach(Config::get_all() as $name => $value)
            $data .= sprintf("\nConfig::add('%s', '%s');\n",
                addslashes($name),
                addslashes($value));
        $data .= "\n?>";
        file_put_contents($this->config_file, $data);
        
        // Show result
        $this->hide();
        $relative_folder = dirname($_SERVER['SCRIPT_NAME']);
        etag('p', 'Installation finished succesfully !');
        etag('p', 'You can add support for cool urls by adding the
        	following data in .htaccess file at skeleton\'s root directory.',
            tag('pre class="code"', 
<<< EOF
php_flag magic_quotes_gpc off

RewriteEngine On
RewriteBase $relative_folder

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ ./index.php/$1 [PT,L,QSA]"
EOF
            ));
    }
}
?>
