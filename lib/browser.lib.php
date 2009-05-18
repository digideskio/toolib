<?php
class Browser {
    /**
        Figure out what browser is used, its version and the platform it is
        running on.

        The following code was ported in part from JQuery v1.3.1
    */
    public static function detect($userAgent = NULL) {
        if ($userAgent === NULL)
            $userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
        else
            $userAgent =strtolower($userAgent);

        // Identify the browser. Check Opera and Safari first in case of spoof. Let Google Chrome be identified as Safari.
        if (preg_match('/opera/', $userAgent)) {
            $name = 'opera';
            $human_name = 'Opera';
        }
        elseif (preg_match('/webkit/', $userAgent)) {
            $name = 'safari';
            $human_name = 'Safari';
        }
        elseif (preg_match('/msie/', $userAgent)) {
            $name = 'msie';
            $human_name = 'Internet Explorer';
        }
        elseif (preg_match('/firefox/', $userAgent)) {
            $name = 'firefox';
            $human_name = 'Firefox';
        }
        elseif (preg_match('/mozilla/', $userAgent) && !preg_match('/compatible/', $userAgent)) {
            $name = 'mozilla';
            $human_name = 'Mozilla';
        }
        else {
            $name = 'unrecognized';
            $human_name = 'unrecognized';
        }

        // What version?
        switch ($name){
        case 'firefox':
            if (preg_match('/firefox\/([\d.]+)/', $userAgent, $matches)){
                $version = $matches[1];
            }
            break;
        case 'safari':
            if (preg_match('/version\/([\d.]+)/', $userAgent, $matches)){
                $version = $matches[1];
            }
            break;
        default:
            if (preg_match('/.+(?:rv|it|ra|ie)[\/: ]([\d.]+)/', $userAgent, $matches)) {
                $version = $matches[1];
            }
        };
        
        if (!isset($version))
            $version = 'unknown';

        // Running on what platform?
        if (preg_match('/linux/', $userAgent)) {
            $platform = 'linux';
        }
        elseif (preg_match('/macintosh|mac os x/', $userAgent)) {
            $platform = 'mac';
        }
        elseif (preg_match('/windows|win32/', $userAgent)) {
            $platform = 'windows';
        }
        else {
            $platform = 'unrecognized';
        }

        return array(
            'name'       => $name,
            'human name' => $human_name,
            'version'    => $version,
            'platform'   => $platform,
            'userAgent'  => $userAgent
        );
    }
};
?>