<?php

namespace toolib\Twiggy;
use \toolib\Options;

/**
 * @brief Interface to a twiggy template
 */
class Template
{
	/**
	 * @brief The full path of template's source
	 * @var string
	 */
	private $source_path;
	
	/**
	 * @brief Full path of template's compiled file.
	 * @var string
	 */
	private $compiled_path = null;
	
	/**
	 * @var \toolib\Options
	 */
	private $options;
	
	/**
	 * @brief Twiggy template instance
	 * @param string $path The file path of template source.
	 * @param Options $options Options to be passed.
	 */
	public function __construct($source_path, Options $options)
	{
		$this->source_path = $source_path;
		$this->options = $options;
	}
	
	/**
	 * @brief Generate compiled code and return it
	 * @return string
	 */
	private function generateCompiled()
	{
		// Actual compilation
		$object_wrapper = file_get_contents(__DIR__ . '/ObjectWrapper.php');
		$compiled_data = $object_wrapper . str_replace(
			array('{%', '%}', '{{', '}}'),
			array('<?php', '?>', '<?php echo $this->safeWrite(', ');?>'),
			file_get_contents($this->source_path)
		);

		return $compiled_data;
	}
	
	/**
	 * @brief Request template compilation
	 */
	public function compile()
	{
		// Generate storage path
		if ($this->options->get('compiled_directory') !== false) {
			$this->compiled_path = $this->options->get('compiled_directory') . '/';
		} else {
			$this->compiled_path = sys_get_temp_dir() . '/';
		}
		$this->compiled_path .= 'twiggy_' . basename($this->source_path) . '_' . sha1(realpath($this->source_path)) . '.php';
		
		if (!file_exists($this->compiled_path) || $this->options->get('auto_reload')) {
			return (bool)file_put_contents($this->compiled_path, $this->generateCompiled());
		}
	}
	
	/**
	 * @brief Request compilation and get output file.
	 */
	public function getCompiledFile()
	{
		if ($this->compiled_path === null)
			$this->compile();
		return $this->compiled_path;
	}
	
	/**
	 * @brief Execute template and get execution frame
	 * @param array $enviroment Variables to be passed inside template
	 * @return Frame
	 */
	public function execute($enviroment = array())
	{
		return new Frame($this->getCompiledFile(), $enviroment);
	}
	
	/**
	 * @brief Execute template and print output
	 * @param array $enviroment Variables to be passed inside template
	 */
	public function display($enviroment = array())
	{
		echo (string)$this->execute($enviroment);
	}
	
	/**
	* @brief Execute template and get output
	* @param array $enviroment Variables to be passed inside template
	*/
	public function render($enviroment = array())
	{
		return (string)$this->execute($enviroment);
	}
}