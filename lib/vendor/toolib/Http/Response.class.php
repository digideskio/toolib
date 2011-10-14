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

namespace toolib\Http;
require_once __DIR__ . '/Cookie.class.php';

/**
 * @brief Base class for interfacing HTTP Responses.
 */
abstract class Response
{

	/**
	 * Repository for meta information, used internally
	 * @var array
	 */
	private $meta = array('cache' => array());

	/**
	 * @brief Add a new header on response
	 * @param string $name Name of header
	 * @param string $value Value of header
	 * @param boolean $replace If true, it will replace any existing header with same name.
	 */
	abstract public function addHeader($name, $value, $replace = true);

	/**
	 * @brief Remove a header from response
	 * @param string $name Name of header
	 */
	abstract public function removeHeader($name);

	/**
	 * @brief Ask user-agent to redirect in a new url
	 * @param $url The absolute or relative url to redirect.
	 * @param $auto_exit If @b true the program will terminate immediately.
	 */
	abstract public function redirect($url, $auto_exit = true);

	/**
	 * @brief Define the content type of this response
	 * @param $mime The mime of the content.
	 */
	abstract public function setContentType($mime);

	/**
	 * @brief Set the status code and message of response
	 * @param $code 3-digits error code.
	 * @param $message A small description of this error code.
	 * @throws InvalidArgumentException
	 */
	abstract public function setStatusCode($code, $message);

	/**
	* @brief Get the status code and message of response
	* @return integer The 3-digit code.
	*/
	abstract public function getStatusCode(& $message = null);
	
	/**
	 * @brief Append content data on the response.
	 * @param string $data
	 */
	abstract public function appendContent($data);

	/**
	 * @brief Set a cookie to be send with response
	 * @param \toolib\Http\Cookie $cookie
	 */
	abstract public function setCookie(Cookie $cookie);

	/**
	 * @brief Define last modification timestamp of the responded resource.
	 * @param \DateTime $last_modified The timestamp that resource was
	 * last modified.
	 */
	public function setLastModified(\DateTime $last_modified)
	{
		$this->meta['last-modified'] = $last_modified;
		$this->addHeader('Last-Modified',
			gmdate('D, d M Y H:i:s', $last_modified->format('U')) . ' GMT' );
	}

	/**
	 * @brief Define responses ETag id.
	 * @param string $etag Unique id for this snapshot of response.
	 */
	public function setEtag($etag)
	{
		$this->meta['etag'] = $etag;
		$this->addHeader('ETag', $etag);
	}

	/**
	 * @brief Check if agent has latest resource modification based on
	 * the request headers.
	 * @param Request $request HTTP request to check against.
	 */
	public function isNotModified(Request $request)
	{
		// Check etag
		if (isset($this->meta['etag'])) {
			if (($matches = $request->getHeaders()->getValue('if-none-match')) !== null) {
				$matches = array_map('trim', explode(',', $matches));
				return in_array($this->meta['etag'] , $matches)?true:false;
			}
		}
		 
		// Check last modified
		if (isset($this->meta['last-modified'])) {
			if (($if_modified_since = $request->getHeaders()->getValue('if-modified-since')) !== null) {
				$if_modified_since = date_create($if_modified_since);
				return ($this->meta['last-modified']->format('U') > $if_modified_since->format('U'))?false:true;
			}
		}
		return false;
	}

	private function updateCacheControl()
	{
		$this->addHeader('Cache-Control', implode(', ', $this->meta['cache']));
	}

	/**
	 * @brief Declare that the response is private to only one user.
	 */
	public function setCachePrivate()
	{
		unset($this->meta['cache']['public']);
		$this->updateCacheControl();
	}

	/**
	 * @brief Declare that the response is public for any user
	 */
	public function setCachePublic()
	{
		$this->meta['cache']['public'] = 'public';
		$this->updateCacheControl();
	}
	
	/**
	 * @brief Set arbitrary Cache-control directive to be appended
	 */
	public function setCacheDirective($directive)
	{
		$this->meta['cache'][$directive] = $directive;
		$this->updateCacheControl();
	}
	
	/**
	 * @brief Set cache-control to no-cache directive
	 */
	public function setCacheDirectiveNoCache()
	{
		$this->meta['cache'] = array('no-cache' => 'no-cache');
		$this->updateCacheControl();
	}

	/**
	 * @brief Define tha maximum time in seconds that this response can be cached.
	 * @param integer $delta_seconds How many seconds the response can be cached.
	 */
	public function setCacheMaxAge($delta_seconds)
	{
		$this->meta['cache']['max-age'] = 'max-age=' . $delta_seconds;
		$this->updateCacheControl();
	}

	/**
	 * @brief Define tha maximum time in seconds that this response can be cached.
	 * @param integer $delta_seconds How many seconds the response can be cached.
	 */
	public function setCacheSharedMaxAge($delta_seconds)
	{
		$this->meta['cache']['s-max-age'] = 's-max-age=' . $delta_seconds;
		$this->updateCacheControl();
	}
	
	/**
	 * @brief (DEFAULT) This class of status code indicates that
	 * the client's request was successfully received, understood, and accepted.
	 */
	public function reply200Ok()
	{
		$this->setStatusCode(200, 'OK');
	}
	
	/**
	 * @brief The request has been fulfilled and resulted in a new resource
	 * being created.
	 * @param string $uri Uri of the newly created resource
	 */
	public function reply201Created($uri = null)
	{
		$this->setStatusCode(201, 'Created');
		if ($uri !== null)
			$this->addHeader('Location', $uri);
	}
	
	/**
	 * @brief The request has been accepted for processing,
	 * but the processing has not been completed.
	 */
	public function reply202Accepted()
	{
		$this->setStatusCode(202, 'Accepted');
	}
	
	/**
	 * @brief The server has fulfilled the request but does not need to
	 * return an entity-body.
	 * 
	 * It might want to return updated metainformation.
	 */
	public function reply204NoContent()
	{
		$this->setStatusCode(204, 'No Content');
	}
	
	/**
	 * @brief The request has been fulfilled and resulted in a new resource
	 * being created.
	 * @param string $uri Uri of the newly created resource
	 */
	public function reply301MovedPermanently($uri = null)
	{
		$this->setStatusCode(301, 'Moved Permanently');
		if ($uri !== null)
			$this->addHeader('Location', $uri);
	}
	
	/**
	 * @brief The requested resource resides temporarily under a different URI.
	 * @param string $uri Uri to find the resource
	 */
	public function reply302Found($uri = null)
	{
		$this->setStatusCode(302, 'Found');
		if ($uri !== null)
			$this->addHeader('Location', $uri);
	}
	
	/**
	 * @brief The response to the request can be found under a different
	 * URI and SHOULD be retrieved using a GET method on that resource.
	 * @param string $uri Uri to find the resource
	 */
	public function reply303SeeOther($uri = null)
	{
		$this->setStatusCode(303, 'See Other');
		if ($uri !== null)
			$this->addHeader('Location', $uri);
	}
	
	/**
	 * @brief If the client has performed a conditional GET request
	 * and access is allowed, but the document has not been modified.
	 */
	public function reply304NotModified()
	{
		$this->setStatusCode(304, 'Not Modified');
	}
	
	/**
	 * @brief The requested resource resides temporarily under a different URI.
	 * @param string $uri Uri to find the resource
	 */
	public function reply307TemporaryRedirect($uri = null)
	{
		$this->setStatusCode(307, 'Temporary Redirect');
		if ($uri !== null)
			$this->addHeader('Location', $uri);
	}
	
	/**
	 * @brief The request could not be understood by
	 * the server due to malformed syntax.
	 */
	public function reply400BadRequest()
	{
		$this->setStatusCode(400, 'Bad Request');
	}
	
	/**
	 * @brief The request requires user authentication.
	 * @todo RFC indicates that it must include www-authenticate challenge.
	 */
	public function reply401Unauthorized()
	{
		$this->setStatusCode(401, 'Unauthorized');
	}
	
	/**
	 * @brief The server understood the request, but is refusing to fulfill it.
	 */
	public function reply403Forbidden()
	{
		$this->setStatusCode(403, 'Forbidden');
	}
	
	/**
	 * @brief The server has not found anything matching the Request-URI.
	 */
	public function reply404NotFound()
	{
		$this->setStatusCode(404, 'Not Found');
	}
	
	/**
	 * @brief The method specified in the Request-Line is not allowed for
	 * the resource identified by the Request-URI.
	 * @param array $allowed_methods All the allowed methods
	 */
	public function reply405MethodNotAllowed($allowed_methods)
	{
		if (!is_array($allowed_methods) || count($allowed_methods) == 0)
			throw new \InvalidArgumentException('405 HTTP Status codes needs at least one allowed method.');
		$this->addHeader('Allow', strtoupper(implode(', ', $allowed_methods)));
		$this->setStatusCode(405, 'Method Not Allowed');
	}
	
	/**
	 * @brief Agent does not include a generatable entity in its "Accept" header.
	 */
	public function reply406NotAcceptable()
	{
		$this->setStatusCode(406, 'Not Acceptable');
	}
	
	/**
	 * @brief The client did not produce a request within the time that the
	 * server was prepared to wait.
	 */
	public function reply408RequestTimeout()
	{
		$this->setStatusCode(408, 'Request Timeout');
	}
	
	/**
	 * @brief The requested resource is no longer available at the server
	 * and no forwarding address is known.
	 */
	public function reply410Gone()
	{
		$this->setStatusCode(410, 'Gone');
	}
	
	/**
	 * @brief The server refuses to accept the request without
	 * a defined Content-Length.
	 */
	public function reply411LengthRequired()
	{
		$this->setStatusCode(411, 'Length Required');
	}
	
	/**
	 * @brief The precondition given in one or more of the
	 * request-header fields evaluated to false when it was tested on the server.
	 */
	public function reply412PreconditionFailed()
	{
		$this->setStatusCode(412, 'Precondition Failed');
	}
	
	/**
	 * @brief The server is refusing to process a request because the request
	 * entity is larger than the server is willing or able to process.
	 */
	public function reply413RequestEntityTooLarge()
	{
		$this->setStatusCode(413, 'Request Entity Too Large');
	}
	
	/**
	 * @brief The server is refusing to service the request because the
	 * Request-URI is longer than the server is willing to interpret.
	 */
	public function reply414RequestURITooLong()
	{
		$this->setStatusCode(414, 'Request-URI Too Long');
	}
	
	/**
	 * @brief A request included a Range request-header field, and none of
	 * the range-specifier values in this field overlap.
	 */
	public function reply416RequestedRangeNotSatisfiable()
	{
		$this->setStatusCode(416, 'Requested Range Not Satisfiable');
	}
	
	/**
	 * @brief The server encountered an unexpected condition which prevented
	 * it from fulfilling the request.
	 */
	public function reply500InternalServerError()
	{
		$this->setStatusCode(500, 'Internal Server Error');
	}
	
	/**
	 * @brief The server does not support the functionality required to 
	 * fulfill the request.
	 */
	public function reply501NotImplemented()
	{
		$this->setStatusCode(501, 'Not Implemented');
	}
	
	/**
	 * @brief The server, received an invalid response from the upstream server.
	 */
	public function reply502BadGateway()
	{
		$this->setStatusCode(502, 'Bad Gateway');
	}
	
	/**
	 * @brief The server is currently unable to handle the request due
	 * to a temporary overloading or maintenance of the server.
	 * @param \DateTime $retry_after Time indication for when to retry again.
	 */	
	public function reply503ServiceUnavailable(\DateTime $retry_after)
	{
		$this->addHeader('Retry-After',
			gmdate('D, d M Y H:i:s', $retry_after->format('U')) . ' GMT' );
		$this->setStatusCode(503, 'Service Unavailable');
	}
	
	/**
	 * @brief The server, while acting as a gateway or proxy, did not receive
	 * a timely response from the upstream server specified by the URI.
	 */
	public function reply504GatewayTimeout()
	{
		$this->setStatusCode(504, 'Gateway Timeout');
	}
	
	/**
	 * @brief The server does not support, or refuses to support,
	 * the HTTP protocol version that was used in the request message.
	 */
	public function reply505HttpVersionNotSupported()
	{
		$this->setStatusCode(505, 'HTTP Version Not Supported');
	}
}
