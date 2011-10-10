<?php

namespace toolib\Stupid\Condition;
use toolib\Stupid\Knowledge;
use toolib\Stupid\Condition;
use toolib\Http;

/**
 * @brief Condition object for Http Request.
 * @method toolib\Stupid\Condition\Request create() 
 */
class Request extends Condition
{
	/**
	 * @brief List with all subconditions (AND)
	 * @var array
	 */
	private $subconditions = array();
	
	/**
	 * @brief Condition to check path_prefix only
	 */
	private function checkPrefix()
	{
		$this->subconditions['check-prefix'] = function(Http\Request $request, Knowledge $knowledge) {
			
			if (($prefix = $knowledge->getOptionalFact('request.path_prefix', '')) === '')
				return true;
			return substr($request->getPath(), 0, strlen($prefix)) === $prefix;
		};
	}
	/**
	 * @brief Check if path exactly equal to a value
	 * @param string $check_path
	 * @return Request
	 */
	public function pathIs($check_path)
	{
		$this->checkPrefix();
		$this->subconditions[] = function(Http\Request $request, Knowledge $knowledge) use($check_path) {
			$full_check_path = $knowledge->getOptionalFact('request.path_prefix', '') . $check_path;
			if ($request->getPath() == $full_check_path ) {
				$knowledge->results['request.path_matched'] = $check_path;
				return true;
			}
		};
		return $this;
	}
	
	/**
	* @brief Check if path matches exactly with a regular expressions
	* @param string $regex Regular expresion to check against
	* @return Request
	*/
	public function pathRegexIs($regex)
	{
		$this->checkPrefix();
		$this->subconditions[] = function(Http\Request $request, Knowledge $knowledge) use($regex) {
			$path_suffix = substr($request->getPath(), strlen($knowledge->getOptionalFact('request.path_prefix', '')));
			if (preg_match($regex, $path_suffix, $matches) > 0) {
				$knowledge->results['request.path_matched'] = $path_suffix;
				foreach($matches as $key => $match) {
					if (!is_numeric($key))
						$knowledge->results['request.params'][$key] = $match;
				}
				return true;
			}
		};
		return $this;
	}
	
	/**
	 * @brief Check if path matches with a url pattern.
	 * @param string $pattern The pattern of url of static text and placeholders.
	 * @param array $requirements Array of placeholders requirements. Special
	 *  placeholders _start and _end can be used for manipulating nose and tail regular expressions.
	 * @return Request
	 */
	public function pathPatternIs($pattern, $requirements = array())
	{
		$this->checkPrefix();
		$this->subconditions[] = function(Http\Request $request, $knowledge) use($pattern, $requirements) {	
			
			/*
			 * 1st stage, extract placeholders from pattern
			 */
			$path_suffix = substr($request->getPath(), strlen($knowledge->getOptionalFact('request.path_prefix', '')));
			$full_pattern = $knowledge->getOptionalFact('request.path_prefix', '') . $pattern;
			if (preg_match_all('/(?<key>{[^}]+})/', $pattern, $matches, PREG_OFFSET_CAPTURE) === false)
				return false;

			/*
			 * 2nd stage, convert pattern to regular experssion
			 */
			$requirements = array_merge(array('_start' => '^', '_end' => '$'), $requirements);
			$extract_regex = "#{$requirements['_start']}";
			$offset_start = 0;
			$params = array();
			foreach($matches['key'] as $key) {
				$name = trim($key[0], '{}');
				$params[$name] = null;
				$requirement = isset($requirements[$name]) ? $requirements[$name] : '[^/]+'; 
				$offset_end = $key[1];
				$extract_regex .= preg_quote(substr($pattern, $offset_start, $offset_end - $offset_start), '#')
					 . "(?P<{$name}>{$requirement})";
				$offset_start = $key[1] + strlen($key[0]);
			}
			$extract_regex .= preg_quote(substr($pattern, $offset_start), '#') . "{$requirements['_end']}#";
			
			/*
			 * 3rd stage, execute regular expresion (pattern) on actual data.
			 */
			if (preg_match($extract_regex, $path_suffix, $matches)  <= 0)
				return false;
			
			foreach($params as $key => $value) {
				$params[$key] = $matches[$key];
			}
			
			$knowledge->results['request.path_matched'] = 
				substr($request->getPath(), strlen($knowledge->getOptionalFact('request.path_prefix', '')));
			$knowledge->setResult('request.params', $params);
			return true;
		};
		return $this;
	}
	
	/**
	 * @brief Check that value of a query parameter is equal to an expected value.
	 * @param string $name Name of parameter
	 * @param string $expected Expected value for this condition to be true
	 * @return \toolib\Stupid\Condition\Request
	 */
	public function queryParamIs($name, $expected)
	{
		$this->subconditions[] = function(\toolib\Http\Request $request, $knowledge) use($name, $expected) {
			if ($request->getQuery()->isArray($name))
				return false;
			return $request->getQuery()->is($name, $expected);
		};
		return $this;
	}
	
	/**
	 * @brief Check that value of a query parameter matches a regular expression
	 * @param string $name Name of parameter
	 * @param string $pattern Pattern to be matched for the condition to be true.
	 * @return \toolib\Stupid\Condition\Request
	 */
	public function queryParamRegexIs($name, $pattern)
	{
		$this->subconditions[] = function(\toolib\Http\Request $request, $knowledge) use($name, $pattern) {
			return (preg_match($pattern, $request->getQuery()->get($name, '')) > 0);
		};
		return $this;
	}
	
	/**
	 * @brief Check that value of a content parameter is equal to an expected value.
	 * @param string $name Name of parameter
	 * @param string $expected Expected value for this condition to be true
	 * @return \toolib\Stupid\Condition\Request
	 */
	public function contentParamIs($name, $expected)
	{
		$this->subconditions[] = function(\toolib\Http\Request $request, $knowledge) use($name, $expected) {
			if ($request->getContent()) {
				if ($request->getContent()->isArray($name))
					return false;
				return $request->getContent()->is($name, $expected);
			}
		};
		return $this;
	}
	
	/**
	 * @brief Check that value of a content parameter matches a regular expression
	 * @param string $name Name of parameter
	 * @param string $pattern Pattern to be matched for the condition to be true.
	 * @return \toolib\Stupid\Condition\Request
	 */
	public function contentParamRegexIs($name, $pattern)
	{
		$this->subconditions[] = function(\toolib\Http\Request $request, $knowledge) use($name, $pattern) {
			if ($request->getContent()) {
				return (preg_match($pattern, $request->getContent()->get($name, '')) > 0);
			}
		};
		return $this;
	}
	
	/**
	 * @brief Check if request method is specific type.
	 * @param string $method Any valid HTTP Request methods
	 * @return Request
	 */
	public function methodIs($method)
	{
		$this->subconditions[] = function(Http\Request $request) use($method){			
			return $request->getMethod() == strtoupper($method);
		};
		return $this;
	}
	
	/**
	 * @brief Check if request method is POST.
	 * @return Request
	 */
	public function methodIsPost()
	{
		return $this->methodIs('POST');
	}

	/**
	 * @brief Check if request method is GET.
	 * @return Request
	 */	
	public function methodIsGet()
	{
		return $this->methodIs('GET');
	}
	
	/**
	 * @brief Check if request method is DELETE.
	 * @return Request
	 */	
	public function methodIsDelete()
	{
		return $this->methodIs('DELETE');
	}
	
	/**
	 * @brief Check if request method is PUT.
	 * @return Request
	 */
	public function methodIsPut()
	{
		return $this->methodIs('PUT');
	}
	
	/**
	 * @brief Check if request method is HEAD.
	 * @return Request
	 */
	public function methodIsHead()
	{
		return $this->methodIs('HEAD');
	}
	
	public function evaluate()
	{
		// Set the request's extractor
		$k = $this->knowledge;
		$this->knowledge->setExtractor('request', function($k) {
			if (isset($k->results['request.path_matched']))
				return array('request.path_prefix' => 
					$k->getOptionalFact('request.path_prefix', '') . $k->results['request.path_matched']);
			else
				return array();
		});
		
		$request = $this->knowledge->getFact('request.gateway')->getRequest();		
		foreach ($this->subconditions as $subcond) {
			if (!($res = $subcond($request, $this->knowledge))){
				return false;
			}
		}
		return true;
	}
}