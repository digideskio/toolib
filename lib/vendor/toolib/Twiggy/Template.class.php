<?php

namespace toolib\Twiggy;
use \toolib\Options;

/**
 * @brief Exception thrown on compilation error
 */
class CompilationException extends \Exception
{
	/**
	 * @brief Line of error
	 * @var integer
	 */
	public $line;
	
	/**
	 * @brief Source file
	 * @var string
	 */
	public $file;
	
	/**
	 * @brief Create a new compilation exception
	 * @param string $message Error message of compiler
	 * @param string $file Source file
	 * @param integer $line Line with error
	 */
	public function __construct($message, $file, $line) {
		$this->file = $file;
		$this->line = $line;
		parent::__construct($message);
	}
}

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
		$source = file_get_contents($this->source_path);
		$source_len = mb_strlen($source);
		
		// PHASE 1: Extract tokens
		$lang_tokens = array('{{', '}}', '{%', '%}', '{#', '#}');
		$tokens = array();
		foreach($lang_tokens as $tok_id => $tok) {
			$pos = 0;
			while(($pos = mb_strpos($source, $tok, $pos)) !== false) {
				$tokens[$pos] = $tok_id;
				$pos+= mb_strlen($tok);
			}
		}
		ksort($tokens, SORT_NUMERIC);

		// PHASE 2: Extract chunks
		$chunks[0] = array(-1, 0, -1); /*  tok_id, start, end */
		$last_chunk = 0;
		$search_tokid = -1;
		foreach($tokens as $pos => $tok_id) {

			if ($search_tokid > -1) {
				// Waiting for close tag
				if ($tok_id == $search_tokid) {
					$search_tokid = -1;
					$chunks[$last_chunk][2] = $pos;
					$chunks[++$last_chunk] = array(-1, $pos + 2/* strlen($tok)*/);
				}
				continue;
			}
			
			// Waiting for open tag
			if ($tok_id % 2 == 0) {
				// Then we wait for close tag
	 			$search_tokid = $tok_id + 1;
				$chunks[$last_chunk][2] = $pos;
				$chunks[++$last_chunk] = array($tok_id, $pos);
				continue;
			}
		}
		if ($search_tokid != -1) {
			throw new CompilationException("Searching for token \"{$lang_tokens[$search_tokid]}\" exceeded end of file",
				$this->source_path, -1);
		}
		if (!isset($chunks[$last_chunk][2]))
			$chunks[$last_chunk][2] = $source_len ;
		
		// PHASE 3: Process & optimize chunks
		$total_chunks = count($chunks); /* don't put count in for because we unset */
		for($i = 0;$i < $total_chunks; $i++) {
						
			if (($chunks[$i][2] - $chunks[$i][1]) == 0) {
				// Remove empty ones
				unset($chunks[$i]);
				continue;
			} else if ($chunks[$i][2] < $chunks[$i][1]) {
				throw new CompilationException("Internal compiler error at " . __FILE__ . ":" . __LINE__,
					$this->source_path, -1);
			}
			
			// Extract text
			if ($chunks[$i][0] == -1) {
				$chunks[$i]['text'] = mb_substr($source, $chunks[$i][1], $chunks[$i][2]- $chunks[$i][1]);
			} else {
				$chunks[$i]['text'] = mb_substr($source, $chunks[$i][1]+2, $chunks[$i][2]- $chunks[$i][1]-2);
			}
			
			// Optimize per case
			if ($chunks[$i][0] == 0) {
				// {{ Token
				// @todo FIX to be unicode safe
				$chunks[$i]['text'] = trim($chunks[$i]['text'], ' ;');
			}
		}
		//var_dump($chunks);
		//exit;
		
		// PHASE 4: Generate new code
		$compiled_data = file_get_contents(__DIR__ . '/ObjectWrapper.php') ;
		foreach($chunks as $chunk) {
			if ($chunk[0] == 4) {
				// {# Token
				continue;
			}
			
			if ($chunk[0] == -1){
				// Literal chunk
				$compiled_data .= '$this->rawWrite(\'';
				// @todo FIX this to be unicode safe? is it?
				// @todo Probably we must not escape \ to \\ for '
				$compiled_data .= str_replace(array('\'', '\\'), array('\\\'', '\\\\'), $chunk['text']);
				$compiled_data .= "');\n";
			}else if ($chunk[0] == 0) {
				// {{ Token
				$compiled_data .= '$this->safeWrite(';
				$compiled_data .= $chunk['text'];
				$compiled_data .= ");\n";
			} else if ($chunk[0] == 2) {
				// {% Token
				//$compiled_data .= '<?php ';
				$compiled_data .= $chunk['text'];
				$compiled_data .= "\n";
			}
		}
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