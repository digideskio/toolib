<?php

namespace toolib\Twiggy;

use toolib\Twiggy;

require_once __DIR__ . '/Runtime.ns.php';

/**
 * @brief Twiggy execution frame
 */
class Frame
{
	/**
	 * @brief Output of all blocks
	 * @var array
	 */
	public $block_output = array(0 => '');
	
	/**
	 * @brief Pointer to current capturing block
	 * @var string
	 */
	public $current_block = 0;
	
	/**
	 * @brief Pointer to next anonymous block name
	 * @var string
	 */
	public $next_anonymous_block = 1;
	
	/**
	 * @brief Execution enviroment for templates
	 * @var array
	 */
	private $enviroment = array();
	
	public $auto_escape = true;
	
	public $escape_mode = 'html';
	
	/**
	 * @brief Current execution frame
	 * @var Frame
	 */
	static public $current;	
	
	
	/**
	 * @brief Initialize a new execution frame
	 * @param string $compiled_file Path to compiled template
	 * @param array $enviroment Variables to be passed in template
	 */
	public function __construct($compiled_file, $enviroment)
	{
		self::$current = $this;
		
		if (is_array($enviroment))
			$this->enviroment = $enviroment;
		
		$this->callTemplate($compiled_file);
	}
	
	/**
	 * @brief Call a template with a specific enviroment
	 * @param string $compiled_file Path to compiled template
	 * @param array $extra_env Extra variables to be passed in template
	 */
	private function callTemplate($compiled_file, $extra_env = array())
	{
		foreach(array_merge($this->enviroment, $extra_env) as $var => $value) {
			$$var = $value;
		}
		ob_start(array($this, 'write'));
		include $compiled_file;
		ob_end_clean();
	}
	/**
	 * @brief Create a new block and start capturing
	 * @param string $name If it is null, block will be anonymous.
	 */
	public function nextBlock($name = null)
	{
		ob_flush();
		
		if ($name === null) {
			$this->current_block = $this->next_anonymous_block;
			$this->next_anonymous_block ++;
		} else {
			$this->current_block = $name;
		}
		$this->block_output[$this->current_block] = '';
		
	}
	
	/**
	 * @brief Start a new named block area
	 * @param string $name Name of the block
	 * @param string $content If you pass contents you don't need to call end_block().
	 */
	public function startBlock($name, $content = null)
	{
		$this->nextBlock($name);
		
		if ($content !== null) {			
			$this->write($content);
			$this->endBlock();
		}
	}
	
	/**
	 * @brief End previous block
	 */
	public function endBlock()
	{
		$this->nextBlock();
	}

	/**
	 * @brief Append raw data to current block
	 * @param string $data Data to be appended
	 */
	public function write($data)
	{
		$this->block_output[$this->current_block] .= $data;
	}
	
	/**
	 * @brief Append user data to current block. Data will be escaped if it is enabled.
	 * @param string $what Data to be appended.
	 */
	public function safeWrite($what)
	{
		ob_flush();
		if ($this->auto_escape) {
			$this->write(Runtime\escape($what, $this->escape_mode));
		} else {
			$this->write($what);
		}
	}
	
	/**
	 * @brief Write an alias to an already defined block
	 * @param string $name Block name
	 */
	public function writeBlockAlias($name)
	{
		$this->nextBlock();
		$this->block_output[$this->current_block] = & $this->block_output[$name];
		$this->nextBlock();
	}
	
	/**
	 * @brief Write directly at a block
	 * @param string $name Block name
	 */
	public function writeToBlock($name)
	{
		if (!isset($this->block_output[$name]))
			throw new \InvalidArgumentException('There is no block with name "' . $name . '"');
		$this->block_output[$name] .= $data;
	}
	
	/**
	 * @brief Request to preload a parent template before executing this one.
	 * @param string $templates Name of the template or array of templates
	 */
	public function extendsTemplate($templates)
	{
		if (is_array($templates)) {
			foreach($templates as $template) {
				if ($tp = Twiggy::open($template)) {
					return $this->callTemplate($tp->getCompiledFile());
				}
			}
			throw new \RuntimeException('Cannot find any of the templates');
		}
		
		if (! $tp = Twiggy::open($templates)) {
			throw new \RuntimeException('Cannot find template "' . $templates . '"');
		}		
		
		// Run parent inside same frame
		$this->callTemplate($tp->getCompiledFile());
	}
	
	/**
	 * @brief Request to preload a parent template before executing this one.
	 * @param string $templates Name of the template or array of templates
	 * @param array $enviroment Variables to be passed in template
	 */
	public function includeTemplate($templates, $enviroment = array())
	{
		if (is_array($templates)) {
			foreach($templates as $template) {
				if ($tp = Twiggy::open($template)) {
					return $this->callTemplate($tp->getCompiledFile(), $enviroment);
				}
			}
			throw new \RuntimeException('Cannot find any of the templates');
		}
		
		if (! $tp = Twiggy::open($templates)) {
			throw new \RuntimeException('Cannot find template "' . $templates . '"');
		}		
		
		// Run parent inside same frame
		$this->callTemplate($tp->getCompiledFile(), $enviroment);
	}

	/**
	 * @brief Get final stringified output
	 */
	public function __toString()
	{
		$out = '';
		foreach($this->block_output as $blk)
			$out .= $blk;
		return $out;
	}
}