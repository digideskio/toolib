<?php

namespace toolib\Http;

/**
 * @brief Abstract definition of gateway interface with parent server.
 */
abstract class Gateway
{
	/**
	 * @brief Pointer to singleton instance
	 * @var Gateway
	 */
	static private $_instance;
	
	public function __construct()
	{
		self::$_instance = $this;
	}
	
	/**
	 * @brief Get the request sent by gateway
	 * @return Request
	 */
	abstract public function getRequest();
	
	/**
	* @brief Get the reponse object to be replied to gateway
	* @return Response
	*/
	abstract public function getResponse();	
	
	/**
	 * @brief Get the singleton Gateway instance
	 * @return Gateway
	 */
	static public function getInstance()
	{
		return self::$_instance;
	}
}