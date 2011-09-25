<?php

namespace toolib\Http;

/**
 * @brief Abstract definition of gateway interface with parent server.
 */
abstract class Gateway
{	
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
}