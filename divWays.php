<?php

/**
 * Div PHP Ways
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
 * @author Rafa Rodriguez [@rafageist] <rafageist@hotmail.com>
 * @version 1.0
 * @link https://github.com/divengine/divWays.git
 */

// Constants
if (! defined('PACKAGES')) define('PACKAGES', './');

define('DIV_WAYS_BEFORE_INCLUDE', 1);
define('DIV_WAYS_BEFORE_RUN', 2);
define('DIV_WAYS_BEFORE_OUTPUT', 3);
define('DIV_WAYS_AFTER_RUN', 4);

class divWays
{

    private static $__controllers = array();
	private static $__listen = array();
	private static $__current_way = null;
	private static $__hooks = array();
	private static $__request_method = null;
	private static $__executed = 0;

    /**
     * Boostrap
     *
     * @param string $way_var
     * @param string $default_way
     * @param string $output
     * @param boolean $show_output
     * @param string $request_method
     * @return array
     */
    static function bootstrap($way_var, $default_way, &$output = '', $show_output = true, $request_method = null)
    {
    	$way = null;

    	if (is_null($request_method))
	        $request_method = self::getRequestMethod();

    	if ($request_method != 'CLI')
	    {
	    	$way = self::get($way_var);
	    }
		else
		{
			// TODO: process cli arguments
			// $ php test.php way ...
			//         0       1  ...
			$way = $_SERVER['argv'][1];
		}

        if (is_null($way))
            $way = $default_way;

        self::$__current_way = $way;
		self::$__executed = 0;

        return self::callAll($way, $output, $show_output, $request_method);
    }

	/**
	 * Get request method from env
	 *
	 * @return string
	 */
    static function getRequestMethod()
    {
    	if (is_null(self::$__request_method))
	    {
		    self::$__request_method = "GET";

		    if (php_sapi_name() == "cli")
			    self::$__request_method = "CLI";

		    if (isset($_SERVER['REQUEST_METHOD']))
			    self::$__request_method = strtoupper($_SERVER['REQUEST_METHOD']);
	    }

	    return self::$__request_method;
    }

	/**
	 * Return total of executions
	 *
	 * @return int
	 */
    static function getTotalExecutions()
    {
    	return self::$__executed;
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
    	if (isset($_SERVER['REQUEST_URI']))
	    {
		    $request_uri = $_SERVER['REQUEST_URI'];

		    if ($request_uri[0] == "/")
			    $request_uri = substr($request_uri, 1);

		    $uri_parts = explode("/", $request_uri);
		    $c = count($uri_parts);

		    if ($c > 0)
			    return str_repeat("../", $c - 1);
	    }

        return '';
    }

    /**
     * Call all controllers
     *
     * @param string $way
     * @param string $output
     * @param boolean $show_output
     * @param string $request_method
     * @return array
     */
    static function callAll ($way, &$output = '', $show_output = true, $request_method = null)
    {

    	if (is_null($request_method))
		    $request_method = self::getRequestMethod();

        $data = [];
	    $done = [];

        foreach (self::$__listen as $pattern => $methods)
        {
            if (self::match($pattern, $way))
            {
            	$controllers = [];

            	if (isset($methods[$request_method]))
	                $controllers = $methods[$request_method];

                foreach ($controllers as $controller)
                {
	                $result = self::call($controller, $data, $done, $output, $show_output);
                    $data = self::cop($data, $result);
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
    static function match ($pattern, $way)
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
        
        // pattern prefix and suffix ".../a/b/c/..."
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
     * @param string $output
     * @param boolean $show_output
     * @return mixed
     */
    static function call ($controller, $data = [], &$done = [], &$output = '', $show_output = false)
    {
    	$action = 'Run';
    	if (stripos($controller, '@'))
	    {
	    	$arr = explode('@', $controller);
	    	$controller = $arr[0];
	    	$action = $arr[1];
	    }

        if (isset(self::$__controllers[$controller]))
        {

        	$control = self::$__controllers[$controller];

            $class_name = $control['class_name'];

            if (isset($control['prop']['require']))
            {
            	$require =  $control['prop']['require'];

            	if ( ! is_array($require))
            		$require = [$require];

            	foreach($require as $req)
		            if ( ! isset( $done[ $req ] ) )
			            $data = array_merge( $data, self::call( $req, $data, $done, $output, $show_output) );
            }

            $hooks = [];
	        if (isset(self::$__hooks[$controller]))
	        	$hooks = self::$__hooks[$controller];

            if (file_exists($control['path']) || $control['is_closure']) {

            	// hook before include
	            if ( isset( $hooks[ DIV_WAYS_BEFORE_INCLUDE ] ) )
	            {
		            $result = self::processHooks($hooks[DIV_WAYS_BEFORE_INCLUDE], $data, $done, $output, $show_output);
		            $data = self::cop($data, $result);
	            }

	            $include_output = '';
	            if ( ! $control['is_closure'])
	            {
		            ob_start();
		            include_once $control['path'];
		            $include_output = ob_get_contents();
		            $output .= $include_output;
		            ob_end_clean();
	            }

	            // hook after include
	            if ( isset( $hooks[ DIV_WAYS_BEFORE_RUN ] ) )
	            {
		            $result = self::processHooks($hooks[DIV_WAYS_BEFORE_RUN], $data, $done, $output, $show_output);
		            $data = self::cop($data, $result);
	            }

	            // running...

	            $sum_executed = true;
	            if (isset($control['prop']['type']))
	            	if (trim(strtolower($control['prop']['type'])) == 'background')
	            		$sum_executed = false;

	            $action_output = '';

	            if ($control['is_closure'])
	            {
	            	$closure = $control['closure'];
		            ob_start();
	            	$result = $closure($data);
		            $action_output = ob_get_contents();
	            	ob_end_clean();
		            $data = self::cop($data, $result);
	            }
	            else if (class_exists( $control['class_name']))
	            {
		            ob_start();
	            	$result = $class_name::$action($data);
		            $action_output = ob_get_contents();
		            ob_end_clean();
	            }
				else
				{
					$result = [];

					// hook before output
					if ( isset( $hooks[ DIV_WAYS_BEFORE_OUTPUT ] ) )
					{
						$result = self::processHooks($hooks[DIV_WAYS_BEFORE_OUTPUT], $data, $done, $output, $show_output);
						$data = self::cop($data, $result);
					}

					// check if action is a function
					if (function_exists($action))
					{
						ob_start();
						$result = $action($data);
						$action_output = ob_get_contents();
						ob_end_clean();
					}
					else
						// if not exists a class::method and not exists a function, then output is the include output
						// and action output is empty
						if ($show_output) echo $include_output;
				}

				// if a method/function exists, action output is not empty, then
	            // show action output
	            $output .= $action_output;
				if ($show_output)
					echo $action_output;

				if ($sum_executed)
					self::$__executed++;

	            if (!is_array($result))
		            $result = [$controller => $result];

	            $data = self::cop($data, $result);

	            // hook after run
	            if ( isset( $hooks[ DIV_WAYS_AFTER_RUN ] ) )
	            {
		            $result = self::processHooks($hooks[DIV_WAYS_BEFORE_OUTPUT], $data, $done, $output, $show_output);
		            $data = self::cop($data, $result);
	            }
            }
        }

	    $done[$controller] = true;
	    return $data;
    }

	/**
	 * Process hooks
	 *
	 * @param array $hooks
	 * @param mixed $data
	 * @param array $done
	 * @param string $output
	 * @param boolean $show_output
	 * @return mixed
	 */
    static function processHooks($hooks, $data, &$done = [], &$output = '', $show_output = false)
    {
	    foreach($hooks as $call)
	    {
		    if (is_string($call) && isset($done[$call])) continue;

		    if (is_callable($call))
		    {
			    ob_start();
			    if (is_string($call))
			    {

				    if (strpos($call, '::') !== false)
				    {
					    $arr = explode('::', $call);
					    $call_class = $arr[0];
					    $call_method = $arr[1];
					    $result = $call_class::$call_method($data);
					    $action_output = ob_get_contents();
				    }
				    else
				    {
					    $result = $call($data);
					    $action_output = ob_get_contents();
				    }
			    }
			    else
			    {
				    $result = $call($data);
				    $action_output = ob_get_contents();
			    }
			    ob_end_clean();

			    if ($show_output)
			    	echo $action_output;
		    }
		    else
		    	$result = self::call($call, $data, $done, $output, $show_output);

		    if (is_scalar($result))
		    	if (is_string($call))
		    		$result = [$call => $result];
		        else
			        $result = ["$call" => $result];

		    $data = self::cop($data, $result);
	    }

	    return $data;
    }

	/**
	 * Parse a way
	 *
	 * @param $way
	 * @return array
	 */
    static function parseWay($way)
    {
    	$result = [
    		'methods' => [],
		    'way' => ''
	    ];

    	$url = parse_url($way);

    	if (!isset($url['scheme'])) $url['scheme'] = self::getRequestMethod();
	    if (!isset($url['host'])) $url['host'] = '';
    	if (!isset($url['path'])) $url['path'] = '';
    	if (substr($url['host'], 0, 1)=="/") $url['host'] = substr($url['host'], 1);
	    if (substr($url['path'], 0, 1)=="/") $url['path'] = substr($url['path'], 1);

	    $result['methods'] = explode('-', strtoupper($url['scheme']));
    	$result['way'] = $url['host']."/".$url['path'];

    	return $result;
    }
    /**
     * Listen way
     *
     * @param string $way
     * @param string $controller
     * @param array $properties
     */
    static function listen($way, $controller, $properties = [])
    {
		$way = self::parseWay($way);

	    if (!isset($properties['id']))
		    $properties['id'] = uniqid("closure-");

	    if (!isset($properties['type']))
	    	$properties['type'] = 'foreground';

	    $properties['listen'] = $way;

    	if (!isset(self::$__listen[$way['way']])) self::$__listen[$way['way']] = [];

    	foreach($way['methods'] as $request_method)
	        if (!isset(self::$__listen[$way['way']][$request_method]))
	    	    self::$__listen[$way['way']][$request_method] = [];

	    if (is_callable($controller) && ! is_string($controller))
	    {
	        self::$__controllers[$properties['id']] = [
	        	'class_name' => null,
		        'prop' => $properties,
	        	'path' => null,
		        'is_closure' => true,
		        'closure' => $controller
	        ];

		    $controller = $properties['id'];
	    }

	    foreach($way['methods'] as $request_method)
            self::$__listen[$way['way']][$request_method][] = $controller;

    }

    /**
     * Register a controller
     *
     * @param string $path
     * @param array $properties
     */
    static function register ($path, $properties = [])
    {
        if (! file_exists($path) && file_exists(PACKAGES . "$path"))
            $path = PACKAGES . $path;

        $class_name = self::getClassName($path);

        $prop = self::getCodeProperties($path);

        $prop = self::cop($prop, $properties);

        if ( ! isset($prop['id']))
	        $prop['id'] = $path;

        self::$__controllers[$prop['id']] = [
        	    'class_name' => $class_name,
                'path' => $path,
                'prop' => $prop,
	            'is_closure' => false,
	            'closure' => null
        ];
        
        if (isset($prop['listen'])) {
            if (! is_array($prop['listen']))
                $prop['listen'] = [
                	$prop['listen']
                ];
            
            foreach ($prop['listen'] as $way)
            {
            	self::listen($way, $prop['id']);
            }
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

        $property_value = null;
	    $nextLines = [];

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
        
        if (isset($ini['divWays'])) {
            foreach ($ini['divWays'] as $val) {
                $class_name = self::getClassName($val);
                
                self::register($val);
                
                $p = 'divControl-' . $class_name;
                
                if (isset($ini[$p]['listen'])) {
                    if (is_array($ini[$p]['listen']))
                        foreach ($ini[$p]['listen'] as $way)
                            self::listen($way, $class_name);
                    else
                        self::listen($ini[$p]['listen'], $class_name);
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

	/**
	 * Register hook
	 *
	 * @param integer $moment
	 * @param string $controller
	 * @param mixed $call
	 */
    static function hook ($moment, $controller, $call)
    {
    	if (!isset(self::$__hooks[$controller]))
		    self::$__hooks[$controller] = [];

	    if (!isset(self::$__hooks[$controller][$moment]))
		    self::$__hooks[$controller][$moment] = [];

	    self::$__hooks[$controller][$moment][] = $call;
    }

	/**
	 * Complete object/array properties
	 *
	 * @param mixed $source
	 * @param mixed $complement
	 * @param integer $level
	 * @return mixed
	 */
	final static function cop(&$source, $complement, $level = 0)
	{
		$null = null;

		if (is_null($source))
			return $complement;

		if (is_null($complement))
			return $source;

		if (is_scalar($source) && is_scalar($complement))
			return $complement;

		if (is_scalar($complement) || is_scalar($source))
			return $source;

		if ($level < 100) { // prevent infinite loop
			if (is_object($complement))
				$complement = get_object_vars($complement);

			foreach ($complement as $key => $value) {
				if (is_object($source)) {
					if (isset ($source->$key))
						$source->$key = self::cop($source->$key, $value, $level + 1);
					else
						$source->$key = self::cop($null, $value, $level + 1);
				}
				if (is_array($source)) {
					if (isset ($source [$key]))
						$source [$key] = self::cop($source [$key], $value, $level + 1);
					else
						$source [$key] = self::cop($null, $value, $level + 1);
				}
			}
		}
		return $source;
	}

}