<?php

namespace toolib\Stupid\Condition;
use toolib\Stupid\Condition;
use toolib\Http;

/**
 * @method toolib\Stupid\Condition\Request create() 
 */
class Request extends Condition
{
	private $subconditions = array();
	
	public function __construct()
	{

	}
	
	public function pathIs($path)
	{
		$this->subconditions[] = function(Http\Request $request) use($path) {
			return $request->getPath('') == $path; 
		};
		return $this;
	}
	
	public function pathRegexIs($regex)
	{
		$this->subconditions[] = function(Http\Request $request) use($regex) {
			return preg_match($regex, $request->getPath(), $matches) > 0;
		};
		return $this;
	}
	
	public function pathPatternIs($pattern, $requirements = array())
	{
		$this_condition = $this;
		$this->subconditions[] = function(Http\Request $request, $knowledge) use($pattern, $this_condition, $requirements) {			
			
			$results = preg_match_all('/(?<key>{[^}]+})/', $pattern, $matches, PREG_OFFSET_CAPTURE);
			if ($results <= 0) {
				// No keys, means just check equality
				$validator = $this_condition->pathIs($pattern);
				return $validator($request); 
			}
			
			$requirements = array_merge(array('_start' => '^', '_end' => '$'));
			$proper_regex = "#{$requirements['_start']}";
			$offset_start = 0;
			foreach($matches['key'] as $key) {
				$name = trim($key[0], '{}');
				$params[$name] = null;
				$requirement = isset($requirements[$name]) ? $requirements[$name] : '[^/]+'; 
				$offset_end = $key[1];
				$proper_regex .= preg_quote(substr($pattern, $offset_start, $offset_end - $offset_start), '#');
				$proper_regex .= "(?P<{$name}>{$requirement})";
				$offset_start = $key[1] + strlen($key[0]);
			}
			$proper_regex .= substr($pattern, $offset_start) . "{$requirements['_end']}#";
			if (preg_match($proper_regex, $request->getPath(), $matches)  <= 0)
				return false;
			
			foreach($params as $key => $value) {
				$params[$key] = $matches[$key];
			}
			
			$knowledge->setResult('request.params', $params);
			return true;
		};
		return $this;
	}
	
	public function methodIs($method)
	{
		$this->subconditions[] = function(Http\Request $request) use($method){			
			return $request->getMethod() == strtoupper($method);
		};
		return $this;
	}
	
	public function methodIsPost()
	{
		return $this->methodIs('POST');
	}
	
	public function methodIsGet()
	{
		return $this->methodIs('GET');
	}
	
	public function methodIsDelete()
	{
		return $this->methodIs('DELETE');
	}
	
	public function methodIsPut()
	{
		return $this->methodIs('PUT');
	}
	
	public function evaluate()
	{
		$request = $this->knowledge->getFact('request.gateway')->getRequest();		
		foreach ($this->subconditions as $subcond) {
			if (!($res = $subcond($request, $this->knowledge))){
				return false;
			}
		}
		return true;
	}
}