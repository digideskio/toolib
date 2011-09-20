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

/**
 * @brief Manage http cookies
 */
class Cookie
{
	/**
	 * @brief Date format of Cookies
	 * @var string
	 */
    const DATE_FORMAT = 'D, d-M-Y H:i:s T';
    
    /**
     * @brief The name of te cookie
     * @var string
     */
    private $name;

    /**
     * @brief Value of cookie
     * @var string
     */
    private $value = '';

    /**
     * @brief Domain of cookie
     * @var string
     */
    private $domain = '';

    /**
     * @brief Path of cookie
     * @var string
     */
    private $path = '/';

    /**
     * @brief Time when cookie expires
     * @var number
     */
    private $expiration_time = 0;

    /**
     * @brief Flag if the cookie is httponly
     * @var boolean
     */
    private $httponly = false;

    /**
     * @brief Flag if cookie is secure
     * @var boolean
     */
    private $secure = false;

    /**
     * @brief Construct a cookie
     * @param string $name The name of the cookie.
     * @param string $value The value of the cookie
     * @param integer $expiration_time The unix time stamp when cookie expires or 0 for session cookie.
     * @param string $path The effective path of the cookie.
     * @param string $domain The effective domain of the cookie.          
     * @param boolean $httponly Set the "httponly" flag of the cookie.
     * @param boolean $secure Set the "secure" flag of the cookie.
     */
    public function __construct($name, $value, $expiration_time = 0, $path = '/', $domain = '', $secure = false, $httponly = false)
    {
        $this->name = $name;
        $this->value = $value;
        $this->domain = $domain;
        $this->path = $path;
        $this->expiration_time = $expiration_time;
        $this->httponly = $httponly;
        $this->secure = $secure;
    }

    /**
     * @brief Get the name of the cookie 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @brief Get the value of the cookie
     */
    public function getValue()
    {   
        return $this->value;
    }

    /**
     * @brief Get the effective domain of the cookie
     */
    public function getDomain()
    {   
        return $this->domain;
    }

    /**
     * @brief Get the effective path of the cookie
     */
    public function getPath()
    {   
        return $this->path;
    }

    /**
     * @brief Get the time this cookie expires
     * @return integer Unix timestamp of expiration time or 0 if
     *      it is session cookie.
     */
    public function getExpirationTime()
    {
        return $this->expiration_time;
    }

    /**
     * @brief Check if cookie is session cookie based on expiration time
     */
    public function isSessionCookie()
    {   
        return ($this->expiration_time == 0);
    }    
    
    /**
     * @brief Check "httponly" flag of the cookie
     */
    public function isHttponly()
    {
        return $this->httponly;
    }

    /**
     * @brief Check "secure" flag of the cookie
     */
    public function isSecure()
    {
        return $this->secure;
    }

    /**
     * @brief Set the name of the cookie
     * @param string $name The new name.
     */
    public function setName($name)
    {
        $this->name = $name;
    }
    
    /**
     * @brief Set the value of the cookie
     * @param string $value The new value.
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @brief Set the effective domain of the cookie
     * @param string $domain The new effective domain.
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;
    }

    /**
     * @brief Set the effective path of the cookie
     * @param string $path The new effective path.
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @brief Set the "secure" flag of the cookie
     * @param boolean $enabled The new state of "secure" flag.
     */
    public function setSecure($enabled)
    {
        return $this->secure = $enabled;
    }
    
    /**
     * @brief Set the "httponly" flag of the cookie
     * @param boolean $enabled The new state of "httponly" flag.
     */
    public function setHttponly($enabled)
    {
        return $this->httponly = $enabled;
    }

    /**
     * @brief Set the expiration time of the cookie.
     * @param integer $time
     *  - The unix time stamp of the expiration date.
     *  - @b 0 if the cookie is a session cookie.
     *  .
     */
    public function setExpirationTime($time)
    {
        return $this->expiration_time = $time;
    }

    /**
     * @brief Open a cookie received through php $_COOKIE superglobal.
     * @param string $name The name of the cookie
     * @return Cookie
     *   - Cookie object with all data for this cookie
     *   - @b false if this cookie was not found.
     * .
     */
    public static function openReceived($name)
    {
        if (!isset($_COOKIE[$name]))
            return false;

        $cookie = new self($name, $_COOKIE[$name]);
        return $cookie;
    }

    /**
     * @brief Send cookie to the http response layer
     * 
     * It will use the php's setcookie() function to send
     * all cookie data to the response.
     */
    public function send()
    {
        setcookie($this->name,
            $this->value,
            ($this->isSessionCookie()?0:$this->expiration_time),
            $this->path,
            $this->domain,
            $this->secure,
            $this->httponly
        );
    }
    
    /**
     * @brief Returns the HTTP representation of the Cookie.
     * 
     * @origin: symfony-2 
     * @return string The HTTP representation of the Cookie
     */
    public function __toString()
    {
        $cookie = sprintf('%s=%s', $this->name, urlencode($this->value));

        if (0 !== $this->expiration_time) {
            $cookie .= '; expires='.substr(\DateTime::createFromFormat('U', $this->expiration_time, new \DateTimeZone('UTC'))->format(static::DATE_FORMAT), 0, -5);
        }

        if ('' !== $this->domain) {
            $cookie .= '; domain='.$this->domain;
        }

        if ('/' !== $this->path) {
            $cookie .= '; path='.$this->path;
        }

        if ($this->secure) {
            $cookie .= '; secure';
        }

        if ($this->httponly) {
            $cookie .= '; httponly';
        }

        return $cookie;
    }

    
	/**
	 * @brief Creates a Cookie instance from a Set-Cookie header value.
	 * 
	 * @origin: symfony-2
	 * @param string $cookie A Set-Cookie header value
	 * @param string $url The base URL
	 * @return Cookie A Cookie instance
	 */
    static public function fromString($cookie, $url = null)
    {
        $parts = explode(';', $cookie);

        if (false === strpos($parts[0], '=')) {
            throw new \InvalidArgumentException('The cookie string "%s" is not valid.');
        }

        list($name, $value) = explode('=', array_shift($parts), 2);

        $values = array(
            'name' => trim($name),
            'value' => urldecode(trim($value)),
            'expires' => null,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => false,
        );

        if (null !== $url) {
            if ((false === $parts = parse_url($url)) || !isset($parts['host']) || !isset($parts['path'])) {
                throw new \InvalidArgumentException(sprintf('The URL "%s" is not valid.', $url));
            }

            $values['domain'] = $parts['host'];
            $values['path'] = substr($parts['path'], 0, strrpos($parts['path'], '/'));
        }

        foreach ($parts as $part) {
            $part = trim($part);

            if ('secure' === strtolower($part)) {
                $values['secure'] = true;
                continue;
            }

            if ('httponly' === strtolower($part)) {
                $values['httponly'] = true;
                continue;
            }

            if (2 === count($elements = explode('=', $part, 2))) {
                if ('expires' === $elements[0]) {
                    if (false === $date = \DateTime::createFromFormat(static::DATE_FORMAT, $elements[1], new \DateTimeZone('UTC'))) {
                        throw new \InvalidArgumentException(sprintf('The expires part of cookie is not valid (%s).', $elements[1]));
                    }

                    $elements[1] = $date->getTimestamp();
                }

                $values[strtolower($elements[0])] = $elements[1];
            }
        }

        return new static(
            $values['name'],
            $values['value'],
            $values['expires'],
            $values['path'],
            $values['domain'],
            $values['secure'],
            $values['httponly']
        );
    }
}
