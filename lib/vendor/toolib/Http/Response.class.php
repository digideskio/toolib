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
}
