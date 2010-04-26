<?php

//! Simple class loader compatible to PSR-0
/**
 * This loader supports directory structure as defined
 * in PSR-0 except that it does not support namespaces as it
 * targets pre 5.3 enviroment. It also support variable file extension.
 */
class ClassLoader
{
    //! Directories that will be used to look for classes
    public $directories;

    //! The file extension that files must have
    public $file_extension;
    
    //! Construct a class loader class
    /**
     * @param $directories Array with all directories that will be searched
     * @param $file_extension The file extension of files to look for.
     */
    public function __construct($directories = array(), $file_extension = '.php')
    {
        // Check arguments
        $this->directories = $directories;
        $this->file_extension = $file_extension;
    }

    //! Register a directory as a 
    public function register_directory($directory)
    {
        $this->directories[] = $directory;
    }

    //! Register this object as an autoloader
    public function register()
    {
        spl_autoload_register(array($this, 'load_class'));
    }

    //! Set the file extension of files
    /**
     * @param The extension of file with the leading dot.\n
     *  e.g. '.php' , '.class.php'
     */
    public function set_file_extension($extension)
    {   
        $this->file_extension = $extension;
    }

    //! The actual class loader that is registered for auto load
    /**
     * @param $class The class name that we are looking for its file.
     */
    public function load_class($class)
    {
        foreach($this->directories as $directory)
        {
            $file = $directory . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, $class) . $this->file_extension;
            if (file_exists($file))
                require $file;
        }
    }    
}
