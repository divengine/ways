<?php

/**
 * Div PHP Controller 
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License
 *
 * You should have received a copy of the GNU General Public License
 * along with this program as the file LICENSE.txt; if not, please see
 * http://www.gnu.org/licenses/gpl.txt.
 *
 * @author Rafa Rodriguez <rafageist86@gmail.com>
 * @version 0.1
 */

// Constants
if (! defined('PACKAGES'))
    define('PACKAGES', './');

class divControl
{

    static $__controllers = array();

    static $__listen = array();

    static $__current_way = null;

    static $__web_root = "./";

    static $__hooks = array();

    /**
     * Boostrap
     *
     * @param string $way_var
     * @param string $default_way
     *
     * @return array
     */
    static function bootstrap( $way_var, $default_way)
    {
        $way = self::get($way_var);

        if (is_null($way))
            $way = $default_way;
        
        self::$__current_way = $way;

        return self::callAll($way);
    }

	/**
	 * Return the current way
	 *
	 * @return null
	 */
    static function getCurrentWay()
    {
    	return self::$__current_way;
    }

	/**
	 * Get relative path to root folder of website
	 *
	 * @return string
	 */
    static function getWebRoot ()
    {
        $request_uri = $_SERVER['REQUEST_URI'];
        
        if ($request_uri[0] == "/")
	        $request_uri = substr($request_uri, 1);
        
        $uri_parts = explode("/", $request_uri);
        $c = count($uri_parts);
        
        if ($c > 0)
            return str_repeat("../", $c - 1);

        return '';
    }

    /**
     * Call all controllers
     *
     * @param string $way
     * @return array
     */
    static function callAll ($way)
    {
        $data = [];
	    $done = [];
        foreach (self::$__listen as $pattern => $controllers)
        {
            if (self::matchWay($pattern, $way))
            {
                foreach ($controllers as $controller)
                {
                    $result = self::call($controller, $data, $done);
                    $data = array_merge($data, $result);
                }
            }
        }

        return $data;
    }

    /**
     * Match ways
     *
     * @param string $pattern            
     * @param string $way            
     * @return boolean
     */
    static function matchWay ($pattern, $way)
    {
	    if ($pattern[0] == '/')
	    	$pattern = substr($pattern, 1);

	    $l = strlen($pattern);
	    if (substr($pattern, $l - 1, 1) == '/')
		    $pattern = substr($pattern, 0, $l - 1);

	    if ($pattern == '*')
	    	return true;

        if ($way[0] == '/')
            $way = substr($way, 1);

        $l = strlen($way);
        if (substr($way, $l - 1, 1) == '/')
        	$way = substr($way, 0, $l - 1);

        if ($pattern == $way)
            return true;
        
        $array_pattern = explode("/", $pattern);
        $away = explode("/", $way);
        $count_pattern = count($array_pattern);
        
        // pattern suffix ".../a/b/c"
        if ($array_pattern[0] === '...' && $array_pattern[$count_pattern - 1] !== '...') {
            $s = substr($pattern, 3);
            $p = strpos($way, $s);
            
            if ($p === strlen($way) - strlen($s))
                return true;
        }
        
        if ($array_pattern[0] !== '...' && $array_pattern[$count_pattern - 1] === '...') {
            $s = substr($pattern, 0, strlen($pattern) - 3);
            $p = strpos($way, $s);
            
            if ($p === 0)
                return true;
        }
        
        // pattern preffix and suffix ".../a/b/c/..."
        if ($array_pattern[0] === '...' && $array_pattern[$count_pattern - 1] === '...') {
            $s = substr($pattern, 0, strlen($pattern) - 3);
            $s = substr($s, 3);
            // $s begin and finish with '/', --> /a/b/c/
            
            $p = strpos($way, $s);
            
            if ($p !== 0 && $p !== strlen($way) - strlen($s))
                return true;
        }
        
        // pattern *
        // for example: a/b/c, a/b/*, a/*/c, */b/c, */b/*, */*/*,
        // a/*/*, */*/c
        
        $result = true;
        foreach ($away as $key => $part) {
            if (isset($array_pattern[$key])) {
                if ($part != $pattern[$key] && $pattern[$key] != '*') {
                    $result = false;
                    break;
                }
            } else {
                $result = false;
                break;
            }
        }
        
        return $result;
    }

    /**
     * Call to controller
     *
     * @param string $controller
     * @param array $data
     * @param array $done
     * @return mixed
     */
    static function call ($controller, $data = [], &$done = [])
    {
        if (isset(self::$__controllers[$controller])) {
        	$control = self::$__controllers[$controller];
            $class_name = $control['class_name'];

            if (isset($control['prop']['require']))
            {
            	$require =  $control['prop']['require'];

            	if ( ! is_array($require))
            		$require = [$require];

            	foreach($require as $req)
		            if ( ! isset( $done[ $req ] ) )
			            $data = array_merge( $data, self::call( $req, $data, $done ) );
            }

            if (file_exists($control['path'])) {
	            ob_start();
                include_once $control['path'];
                $output = ob_get_contents();
                ob_end_clean();

	            if (class_exists( $control['class_name']))
	            {
	            	$result = $class_name::Run($data);
	            	if ( ! is_array($result))
	            		$result = [$controller => $result];
		            $data = array_merge($data, $result);
	            }
				else
	                echo $output;
            }
        }

	    $done[$controller] = true;
	    return $data;
    }

    /**
     * Listen way
     *
     * @param string $controller            
     * @param string $way            
     */
    static function listenWay ($controller, $way)
    {
        if (! isset(self::$__listen[$way]))
            self::$__listen[$way] = array();
        self::$__listen[$way][] = $controller;
    }

    /**
     * Register a controller
     *
     * @param string $path
     */
    static function register ($path)
    {
        if (! file_exists($path) && file_exists(PACKAGES . "$path"))
            $path = PACKAGES . $path;

        $class_name = self::getClassName($path);

        $prop = self::getCodeProperties($path);

        if ( ! isset($prop['id']))
	        $prop['id'] = $path;

        self::$__controllers[$prop['id']] = [
        	    'class_name' => $class_name,
                'path' => $path,
                'prop' => $prop
        ];
        
        if (isset($prop['listen'])) {
            if (! is_array($prop['listen']))
                $prop['listen'] = array(
                        $prop['listen']
                );
            
            foreach ($prop['listen'] as $way)
                self::listenWay($prop['id'], $way);
        }
    }

    /**
     * Get class name from path
     *
     * @param string $path            
     * @return string
     */
    static function getClassName ($path)
    {
        $class_name = explode("/", $path);
        $class_name = $class_name[count($class_name) - 1];
        $class_name = str_replace('.php', '', $class_name);
        return $class_name;
    }

    /**
     * Looking for properties in PHP comments (#property = value)
     *
     * @param string $path
     * @param string $prefix
     * @return array
     */
    static function getCodeProperties ($path, $prefix = '#')
    {
        if ( ! file_exists($path))
            return array();
        
        $f = fopen($path, "r");
        
        $l = strlen($prefix);
        $prop = [];
        while (! feof($f)) {
            $s = fgets($f);
            $s = trim($s);
            if (strtolower(substr($s, 0, $l)) == strtolower($prefix)) {
                $s = substr($s, $l);
                $s = trim($s);
                $p = strpos($s, '=');
                if ($p !== false) {
                    $property_name = trim(substr($s, 0, $p));
                    $property_value = substr($s, $p + 1);
                    if ($property_name != '') {
                        if (isset($prop[$property_name])) {
                            if (! is_array($prop[$property_name]))
                                $prop[$property_name] = array(
                                        $prop[$property_name]
                                );
                            $prop[$property_name][] = trim($property_value);
                        } else
                            $prop[$property_name] = trim($property_value);
                    }
                }
            }
        }

        fclose($f);
        
        return $prop;
    }

    /**
     * Bulk controller register
     *
     * @param string $ini_file            
     */
    static function bulkRegister ($ini_file)
    {
        $ini = parse_ini_file($ini_file, INI_SCANNER_RAW);
        
        if (isset($ini['divControl'])) {
            foreach ($ini['divControl'] as $val) {
                $class_name = self::getClassName($val);
                
                self::register($val);
                
                $p = 'divControl-' . $class_name;
                
                if (isset($ini[$p]['listen'])) {
                    if (is_array($ini[$p]['listen']))
                        foreach ($ini[$p]['listen'] as $way)
                            self::listenWay($class_name, $way);
                    else
                        self::listenWay($class_name, $ini[$p]['listen']);
                }
            }
        }
    }

    /**
     * Get from GET
     *
     * @param string $var            
     * @param mixed $default
     * @return mixed
     */
    static function get ($var, $default = null)
    {
        return (isset($_GET[$var])) ? $_GET[$var] : $default;
    }

    public function registerHook ($moment, $controller, $callTo)
    {}

    public function beforeRun ($controller)
    {}

    public function afterRun ($controller, &$results)
    {}
}