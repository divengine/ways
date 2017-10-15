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
 * @author  Rafa Rodriguez [@rafageist] <rafageist@hotmail.com>
 * @version 1.3
 * @link    https://github.com/divengine/divWays.git
 */

// Constants
if( ! defined('PACKAGES')) define('PACKAGES', './');

define('DIV_WAYS_BEFORE_INCLUDE', 1);
define('DIV_WAYS_BEFORE_RUN', 2);
define('DIV_WAYS_BEFORE_OUTPUT', 3);
define('DIV_WAYS_AFTER_RUN', 4);

class divWays
{
	private static $__way_var = null;
	private static $__default_way = null;
	private static $__controllers = [];
	private static $__listen = [];
	private static $__current_way = null;
	private static $__hooks = [];
	private static $__request_method = null;
	private static $__executed = 0;
	private static $__done = [];
	private static $__args_by_controller = [];


	/**
	 * Returns list of arguments of controller after bootstrap
	 *
	 * @param string $controller
	 *
	 * @return array|mixed|null
	 */
	static function getArgsByController($controller = null)
	{
		if(is_null($controller)) return self::$__args_by_controller;

		if(isset(self::$__args_by_controller)) return self::$__args_by_controller[ $controller ];

		return null;
	}

	/**
	 * Get list of controller done after bootstrap
	 *
	 * @return array
	 */
	static function getDone()
	{
		return self::$__done;
	}

	/**
	 * Check if a controller was done after bootstrap
	 *
	 * @param $controller
	 *
	 * @return bool
	 */
	static function isDone($controller)
	{
		return isset(self::$__done[ $controller ]);
	}

	/**
	 * Boostrap
	 *
	 * @param string  $way_var
	 * @param string  $default_way
	 * @param string  $output
	 * @param boolean $show_output
	 * @param string  $request_method
	 *
	 * @return array
	 */
	static function bootstrap($way_var = null, $default_way = null, &$output = '', $show_output = true, $request_method = null)
	{
		if(is_null($way_var)) if(is_null(self::$__way_var)) $way_var = '_url';
		else $way_var = self::$__way_var;

		if(is_null($default_way)) if(is_null(self::$__default_way)) $default_way = '/';
		else $default_way = self::$__default_way;

		// save first way and way var for all future bootstraps
		if(is_null(self::$__way_var)) self::$__way_var = $way_var;

		if(is_null(self::$__default_way)) self::$__default_way = $default_way;

		$way = null;

		if(is_null($request_method)) $request_method = self::getRequestMethod();

		if($request_method != 'CLI')
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

		if(is_null($way) || empty($way)) $way = $default_way;

		self::$__current_way = $way;
		self::$__executed    = 0;

		return self::callAll($way, $output, $show_output, $request_method, $default_way);
	}

	/**
	 * Get request method from env
	 *
	 * @return string
	 */
	static function getRequestMethod()
	{
		if(is_null(self::$__request_method))
		{
			self::$__request_method = "GET";

			if(php_sapi_name() == "cli") self::$__request_method = "CLI";

			if(isset($_SERVER['REQUEST_METHOD'])) self::$__request_method = strtoupper($_SERVER['REQUEST_METHOD']);
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
	static function getWebRoot()
	{
		if(isset($_SERVER['REQUEST_URI']))
		{
			$request_uri = $_SERVER['REQUEST_URI'];

			if($request_uri[0] == "/") $request_uri = substr($request_uri, 1);

			$uri_parts = explode("/", $request_uri);
			$c         = count($uri_parts);

			if($c > 0) return str_repeat("../", $c - 1);
		}

		return '';
	}

	/**
	 * Call all controllers
	 *
	 * @param string  $way
	 * @param string  $output
	 * @param boolean $show_output
	 * @param string  $request_method
	 * @param string  $default_way
	 *
	 * @return array
	 */
	static function callAll($way, &$output = '', $show_output = true, $request_method = null, $default_way = "/")
	{

		if(is_null($request_method)) $request_method = self::getRequestMethod();

		$data = [];

		foreach(self::$__listen as $pattern => $methods)
		{
			$args = [];

			$pattern = trim($pattern);

			if(is_null($pattern) || empty($pattern) || $pattern == "/")
			{
				$pattern = $default_way;
			}

			if(self::match($pattern, $way, $args))
			{
				$controllers = [];

				if(isset($methods[ $request_method ])) $controllers = $methods[ $request_method ];

				foreach($controllers as $controller)
				{
					if( ! isset(self::$__done[ $controller ]))
					{
						$result = self::call($controller, $data, $args, $output, $show_output);
						$data   = self::cop($data, $result);
						if( ! isset(self::$__args_by_controller[ $controller ])) self::$__args_by_controller[ $controller ] = [];
						self::$__args_by_controller[ $controller ][ $pattern ] = $args;
					}
				}
			}
		}

		return $data;
	}

	/**
	 * Remove first and last slashes
	 *
	 * @param $value
	 *
	 * @return bool|string
	 */
	static function clearSideSlashes($value)
	{
		if (isset($value[0]))
		{
			if($value[0] == "/") $value = substr($value, 1);
			if(substr($value, - 1) == "/") $value = substr($value, 0, - 1);
		}

		return $value;
	}

	/**
	 * Clear double slashes in ways
	 *
	 * @param $value
	 *
	 * @return mixed
	 */
	static function clearDoubleSlashes($value)
	{
		return self::replaceRecursive('//', '/', $value);
	}

	/**
	 * Replace recursively in string
	 *
	 * @param string $search
	 * @param string $replace
	 * @param string $source
	 *
	 * @return mixed
	 */
	static function replaceRecursive($search, $replace, $source)
	{
		while(strpos($source, $search) !== false) $source = str_replace($search, $replace, $source);

		return $source;
	}

	/**
	 * Normalize pattern for better matching
	 *
	 * @param $value
	 *
	 * @return mixed
	 */
	static function normalizePattern($value)
	{
		$value = str_replace(['{', '}', '*'], ['/{', '}/', '/*/'], $value);
		$value = self::clearDoubleSlashes($value);
		$value = self::clearSideSlashes($value);

		return $value;
	}

	/**
	 * Normalize ways for better matching
	 *
	 * @param $way
	 * @param $pattern
	 *
	 * @return mixed|string
	 */
	static function normalizeWay($way, $pattern)
	{
		$new_way = '';

		while(true)
		{
			$bracket = strpos($pattern, "{");
			$star    = strpos($pattern, "*");

			if($bracket === false) $bracket = - 1;
			if($star === false) $star = - 1;
			if($bracket == - 1 && $star == - 1)
			{
				$new_way .= $way;
				break;
			}

			if($bracket < $star || $star == - 1)
			{
				$p       = $bracket; // first open bracket
				$new_way .= substr($way, 0, $p) . '/';
				$new_way = self::clearDoubleSlashes($new_way);

				$p1 = strpos($pattern, '}', $p + 1); // first close bracket

				if($p1 == false) break;

				$ch = substr($pattern, $p1 + 1, 1); // next char from close bracket

				$p3 = false;
				if(isset($way[ $p ])) $p3 = strpos($way, $ch, $p);

				if($p3 == false)
				{
					$new_way .= substr($way, $p);
					$way     = '';
				}
				else
				{
					$new_way .= substr($way, $p, $p3 - $p) . '/';
					$way     = substr($way, $p3);
				}

				$new_way = self::clearDoubleSlashes($new_way);
				$pattern = substr($pattern, $p1 + 1);
			}
			else
			{
				$p = $star;

				$new_way .= substr($way, 0, $p) . '/';
				$new_way = self::clearDoubleSlashes($new_way);

				$ch = substr($pattern, $p + 1, 1); // next char from close bracket

				$p3 = false;
				if(isset($way[ $p ])) $p3 = strpos($way, $ch, $p);

				if($p3 == false)
				{
					$new_way .= substr($way, $p);
					$way     = "";
				}
				else
				{
					$new_way .= substr($way, $p, $p3 - $p) . '/';
					$way     = substr($way, $p3);
				}

				$new_way = self::clearDoubleSlashes($new_way);
				$pattern = substr($pattern, $p + 1);
			}
		}

		return self::clearSideSlashes(self::clearDoubleSlashes($new_way));
	}

	/**
	 * Match ways
	 *
	 * @param string $pattern
	 * @param string $way
	 * @param array  $args
	 *
	 * @return boolean
	 */
	static function match($pattern, $way, &$args = [])
	{
		$pattern = self::clearDoubleSlashes(self::clearSideSlashes($pattern));
		if($pattern == '*' || $pattern == '...') return true;

		$way = self::clearDoubleSlashes(self::clearSideSlashes($way));
		if($pattern == $way) return true;

		$way                 = self::normalizeWay($way, $pattern);
		$pattern             = self::normalizePattern($pattern);
		$array_pattern       = explode("/", $pattern);
		$array_pattern_count = count($array_pattern);
		$away                = explode("/", $way);
		$away_count          = count($away);
		$count_pattern       = count($array_pattern);

		// pattern suffix ".../a/b/c"
		if($array_pattern[0] === '...' && $array_pattern[ $count_pattern - 1 ] !== '...')
		{
			$s = substr($pattern, 3);
			$p = strpos($way, $s);
			if($p === strlen($way) - strlen($s)) return true;

			$new_pattern = '';
			$new_way     = '';
			$j           = $away_count - 1;
			for($i = $array_pattern_count - 1; $i > 0; $i --)
			{
				$new_pattern = $array_pattern[ $i ] . '/' . $new_pattern;
				if(isset($away[ $j ]))
				{
					$new_way = $away[ $j ] . '/' . $new_way;
					$j --;
				}
			}

			return self::match($new_pattern, $new_way, $args);
		}

		// pattern prefix "a/b/c/..."
		if($array_pattern[0] !== '...' && $array_pattern[ $count_pattern - 1 ] === '...')
		{
			$s = substr($pattern, 0, strlen($pattern) - 3);
			$p = strpos($way, $s);

			if($p === 0) return true;

			$new_pattern = '';
			$new_way     = '';
			$j           = 0;
			for($i = 0; $i < $array_pattern_count - 1; $i ++)
			{
				$new_pattern = $new_pattern . '/' . $array_pattern[ $i ];
				if(isset($away[ $j ]))
				{
					$new_way = $new_way . '/' . $away[ $j ];
					$j ++;
				}
			}

			return self::match($new_pattern, $new_way, $args);
		}

		// pattern prefix and suffix ".../a/b/c/..."
		if($array_pattern[0] === '...' && $array_pattern[ $count_pattern - 1 ] === '...')
		{
			$s = substr($pattern, 0, strlen($pattern) - 3);
			$s = substr($s, 3);
			// $s begin and finish with '/', --> /a/b/c/

			$p = strpos($way, $s);

			if($p !== 0 && $p !== strlen($way) - strlen($s) && $p !== false) return true;

			// search pattern in the way (best match)
			// pattern:      <-- .../a/b/c/... -->
			// way:          1/2/3/4/a/b/c/5/6/7/8

			if($away_count >= $array_pattern_count - 2)
			{
				$matches_max = 0;
				$pos         = - 1;
				$args_max    = [];
				for($j = 0; $j < $away_count; $j ++)
				{
					$matches  = 0;
					$new_args = [];
					for($i = 1; $i < $array_pattern_count - 1; $i ++)
					{

						if($j + $i - 1 >= $away_count)
						{
							$matches = 0;
							break;
						}

						$part_pattern = $array_pattern[ $i ];

						if($away[ $j + $i - 1 ] == $part_pattern) $matches ++;
						elseif($part_pattern[0] == '{' && substr($part_pattern, - 1) == '}')
						{
							$arg         = substr($part_pattern, 1, - 1);
							$arg_value   = $away[ $j + $i - 1 ];
							$value_match = self::argChecker($arg, $arg_value, $arg);

							if($value_match)
							{
								$new_args[ $arg ] = $away[ $j + $i - 1 ];
								$matches          += 0.75;
							}
							else
							{
								$matches = 0;
								break;
							}
						}
						elseif($part_pattern == '*') $matches += 0.5;
						else
						{
							$matches = 0;
							break;
						}
					}

					if($matches_max < $matches)
					{
						$matches_max = $matches;
						$pos         = $j;
						$args_max    = $new_args;
					}
				}

				if($pos > - 1)
				{
					$args = $args_max;

					return true;
				}
			}
		}

		// pattern *
		// for example: a/b/c, a/b/*, a/*/c, */b/c, */b/*, */*/*,
		// a/*/*, */*/c

		if(count($away) != count($array_pattern)) return false;

		$result = true;
		foreach($away as $key => $part)
		{
			if( ! isset($array_pattern[ $key ]))
			{
				$result = false;
				break;
			}

			$part_pattern = $array_pattern[ $key ];

			if(isset($part_pattern[2])) if($part_pattern[0] == '{' && substr($part_pattern, - 1) == '}')
			{
				$arg         = substr($part_pattern, 1, - 1);
				$value_match = self::argChecker($arg, $part, $arg);

				if($value_match)
				{
					$args[ $arg ] = $part;
					continue;
				}
			}

			if($part != $part_pattern && $part_pattern != '*')
			{
				$result = false;
				break;
			}
		}

		return $result;
	}

	/**
	 * Check argument value in a way
	 *
	 * @param string $pattern
	 * @param string $arg_value
	 * @param string $arg
	 *
	 * @throws \Exception
	 * @return bool
	 */
	static function argChecker($pattern, $arg_value, &$arg)
	{
		if(is_numeric($arg_value)) $arg_value *= 1;

		$value_match = true;

		if(strpos($pattern, '|') !== false)
		{
			$arg_parts = explode('|', $pattern);
			$arg       = $arg_parts[0];
			$checker   = $arg_parts[1];

			if(is_callable($checker))
			{
				if(strpos($checker, '::') !== false)
				{
					$checker_parts  = explode('::', $checker);
					$checker_class  = $checker_parts[0];
					$checker_method = $checker_parts[1];
					$value_match    = $checker_class::$checker_method($arg_value);
				}
				else
				{
					if($checker == 'is_bool') $value_match = strtolower($arg_value) == 'true' || $arg_value == 1 ? true : false;
					else
						$value_match = $checker($arg_value);
				}
			}
			else throw new Exception("Argument checker $checker is not callable");
		}

		return $value_match;
	}

	/**
	 * Call to controller
	 *
	 * @param string  $controller
	 * @param array   $data
	 * @param array   $args
	 * @param string  $output
	 * @param boolean $show_output
	 *
	 * @return mixed
	 */
	static function call($controller, $data = [], $args = [], &$output = '', $show_output = false)
	{
		$original_controller = $controller;

		// default method to run is Run()
		$action = 'Run';

		$ignore_properties = false;
		if(stripos($controller, '@'))
		{
			$arr               = explode('@', $controller);
			$controller        = $arr[0];
			$action            = $arr[1];
			$ignore_properties = true;
		}

		if(isset(self::$__done[ $original_controller ]))
		{
			return $data;
		}

		if(isset(self::$__controllers[ $controller ]))
		{
			$control    = self::$__controllers[ $controller ];
			$class_name = $control['class_name'];

			if( ! $ignore_properties)
			{
				// check for custom method
				if(isset($control['prop']['method'])) $action = $control['prop']['method'];
			}

			if(isset($control['prop']['require']))
			{
				$require = $control['prop']['require'];

				if( ! is_array($require)) $require = [$require];

				foreach($require as $req)
				{
					// check if required controller is done (for performance)
					if( ! isset(self::$__done[ $req ]))
					{
						$result = self::call($req, $data, $args, $output, $show_output);
						$data   = self::cop($data, $result);
					}
				}
			}

			$hooks = [];
			if(isset(self::$__hooks[ $controller ])) $hooks = self::$__hooks[ $controller ];

			if(file_exists($control['path']) || $control['is_closure'])
			{

				// hook before include
				if(isset($hooks[ DIV_WAYS_BEFORE_INCLUDE ]))
				{
					$result = self::processHooks($hooks[ DIV_WAYS_BEFORE_INCLUDE ], $data, $args, $output, $show_output);
					$data   = self::cop($data, $result);
				}

				$include_output = '';
				if( ! $control['is_closure'])
				{
					ob_start();
					include_once $control['path'];
					$include_output = ob_get_contents();
					$output         .= $include_output;
					ob_end_clean();
				}

				// hook after include
				if(isset($hooks[ DIV_WAYS_BEFORE_RUN ]))
				{
					$result = self::processHooks($hooks[ DIV_WAYS_BEFORE_RUN ], $data, $args, $output, $show_output);
					$data   = self::cop($data, $result);
				}

				// running...

				$sum_executed = true;
				if(isset($control['prop']['type'])) if(trim(strtolower($control['prop']['type'])) == 'background') $sum_executed = false;

				$action_output = '';

				if($control['is_closure'])
				{
					$closure = $control['closure'];
					ob_start();
					$result        = $closure($data, $args);
					$action_output = ob_get_contents();
					ob_end_clean();
					$data = self::cop($data, $result);
				}
				elseif(class_exists($control['class_name']))
				{
					ob_start();
					$result        = $class_name::$action($data, $args);
					$action_output = ob_get_contents();
					ob_end_clean();
				}
				else
				{
					$result = [];

					// hook before output
					if(isset($hooks[ DIV_WAYS_BEFORE_OUTPUT ]))
					{
						$result = self::processHooks($hooks[ DIV_WAYS_BEFORE_OUTPUT ], $data, $args, $output, $show_output);
						$data   = self::cop($data, $result);
					}

					// check if action is a function
					if(function_exists($action))
					{
						ob_start();
						$result        = $action($data, $args);
						$action_output = ob_get_contents();
						ob_end_clean();
					}
					else
						// if not exists a class::method and not exists a function, then output is the include output
						// and action output is empty
						if($show_output) echo $include_output;
				}

				// if a method/function exists, action output is not empty, then
				// show action output
				$output .= $action_output;
				if($show_output) echo $action_output;

				if($sum_executed) self::$__executed ++;

				if( ! is_array($result)) $result = [$controller => $result];

				$data = self::cop($data, $result);

				// hook after run
				if(isset($hooks[ DIV_WAYS_AFTER_RUN ]))
				{
					$result = self::processHooks($hooks[ DIV_WAYS_BEFORE_OUTPUT ], $data, $args, $output, $show_output);
					$data   = self::cop($data, $result);
				}
			}
		}

		self::$__done[ $original_controller ] = true;

		return $data;
	}

	/**
	 * Process hooks
	 *
	 * @param array   $hooks
	 * @param mixed   $data
	 * @param array   $args
	 * @param string  $output
	 * @param boolean $show_output
	 *
	 * @return mixed
	 */
	static function processHooks($hooks, $data, $args, &$output = '', $show_output = false)
	{
		foreach($hooks as $call)
		{
			if(is_string($call) && isset(self::$__done[ $call ])) continue;

			if(is_callable($call))
			{
				ob_start();
				if(is_string($call))
				{
					if(strpos($call, '::') !== false)
					{
						$arr           = explode('::', $call);
						$call_class    = $arr[0];
						$call_method   = $arr[1];
						$result        = $call_class::$call_method($data, $args);
						$action_output = ob_get_contents();
					}
					else
					{
						$result        = $call($data, $args);
						$action_output = ob_get_contents();
					}
				}
				else
				{
					$result        = $call($data, $args);
					$action_output = ob_get_contents();
				}
				ob_end_clean();

				if($show_output) echo $action_output;
			}
			else
				$result = self::call($call, $data, $args, $output, $show_output);

			if(is_scalar($result)) if(is_string($call)) $result = [$call => $result];
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
	 *
	 * @return array
	 */
	static function parseWay($way)
	{
		$result = [
			'methods' => [],
			'way' => ''
		];

		$url = parse_url($way);

		if( ! isset($url['scheme'])) $url['scheme'] = self::getRequestMethod();
		if( ! isset($url['host'])) $url['host'] = '';
		if( ! isset($url['path'])) $url['path'] = '';
		if(substr($url['host'], 0, 1) == "/") $url['host'] = substr($url['host'], 1);
		if(substr($url['path'], 0, 1) == "/") $url['path'] = substr($url['path'], 1);

		$result['methods'] = explode('-', strtoupper($url['scheme']));
		$result['way']     = $url['host'] . "/" . $url['path'];

		return $result;
	}

	/**
	 * Listen way
	 *
	 * @param string $way
	 * @param string $controller
	 * @param array  $properties
	 */
	static function listen($way, $controller, $properties = [])
	{
		$way = self::parseWay($way);

		if( ! isset($properties['id'])) $properties['id'] = uniqid("closure-");

		if( ! isset($properties['type'])) $properties['type'] = 'foreground';

		$properties['listen'] = $way;

		if( ! isset(self::$__listen[ $way['way'] ])) self::$__listen[ $way['way'] ] = [];

		foreach($way['methods'] as $request_method) if( ! isset(self::$__listen[ $way['way'] ][ $request_method ])) self::$__listen[ $way['way'] ][ $request_method ] = [];

		if(is_callable($controller) && ! is_string($controller))
		{
			self::$__controllers[ $properties['id'] ] = [
				'class_name' => null,
				'prop' => $properties,
				'path' => null,
				'is_closure' => true,
				'closure' => $controller
			];

			$controller = $properties['id'];
		}

		foreach($way['methods'] as $request_method) self::$__listen[ $way['way'] ][ $request_method ][] = $controller;

	}

	/**
	 * Register a controller
	 *
	 * @param string $path
	 * @param array  $properties
	 */
	static function register($path, $properties = [])
	{
		if( ! file_exists($path) && file_exists(PACKAGES . "$path")) $path = PACKAGES . $path;

		$class_name = self::getClassName($path);

		$prop = self::getCodeProperties($path);

		$prop = self::cop($prop, $properties);

		if( ! isset($prop['id'])) $prop['id'] = $path;

		self::$__controllers[ $prop['id'] ] = [
			'class_name' => $class_name,
			'path' => $path,
			'prop' => $prop,
			'is_closure' => false,
			'closure' => null
		];

		if(isset($prop['listen']))
		{
			if( ! is_array($prop['listen'])) $prop['listen'] = [
				$prop['listen']
			];

			foreach($prop['listen'] as $way)
			{
				self::listen($way, $prop['id']);
			}
		}
	}

	/**
	 * Get class name from path
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	static function getClassName($path)
	{
		$class_name = explode("/", $path);
		$class_name = $class_name[ count($class_name) - 1 ];
		$class_name = str_replace('.php', '', $class_name);

		return $class_name;
	}

	/**
	 * Looking for properties in PHP comments (#property = value)
	 *
	 * @param string $path
	 * @param string $prefix
	 *
	 * @return array
	 */
	static function getCodeProperties($path, $prefix = '#')
	{
		if( ! file_exists($path)) return [];

		$f = fopen($path, "r");

		$property_value = null;

		$l    = strlen($prefix);
		$prop = [];
		while( ! feof($f))
		{
			$s = fgets($f);
			$s = trim($s);

			if(strtolower(substr($s, 0, $l)) == strtolower($prefix))
			{
				$s = substr($s, $l);
				$s = trim($s);
				$p = strpos($s, '=');
				if($p !== false)
				{
					$property_name  = trim(substr($s, 0, $p));
					$property_value = substr($s, $p + 1);
					if($property_name != '')
					{
						if(isset($prop[ $property_name ]))
						{
							if( ! is_array($prop[ $property_name ])) $prop[ $property_name ] = [
								$prop[ $property_name ]
							];
							$prop[ $property_name ][] = trim($property_value);
						}
						else
							$prop[ $property_name ] = trim($property_value);
					}
				}
			}
		}

		fclose($f);

		return $prop;
	}

	/**
	 * Get from GET
	 *
	 * @param string $var
	 * @param mixed  $default
	 *
	 * @return mixed
	 */
	static function get($var, $default = null)
	{
		return (isset($_GET[ $var ])) ? $_GET[ $var ] : $default;
	}

	/**
	 * Register hook
	 *
	 * @param integer $moment
	 * @param string  $controller
	 * @param mixed   $call
	 */
	static function hook($moment, $controller, $call)
	{
		if( ! isset(self::$__hooks[ $controller ])) self::$__hooks[ $controller ] = [];

		if( ! isset(self::$__hooks[ $controller ][ $moment ])) self::$__hooks[ $controller ][ $moment ] = [];

		self::$__hooks[ $controller ][ $moment ][] = $call;
	}

	/**
	 * Complete object/array properties
	 *
	 * @param mixed   $source
	 * @param mixed   $complement
	 * @param integer $level
	 *
	 * @return mixed
	 */
	final static function cop(&$source, $complement, $level = 0)
	{
		$null = null;

		if(is_null($source)) return $complement;

		if(is_null($complement)) return $source;

		if(is_scalar($source) && is_scalar($complement)) return $complement;

		if(is_scalar($complement) || is_scalar($source)) return $source;

		if($level < 100)
		{ // prevent infinite loop
			if(is_object($complement)) $complement = get_object_vars($complement);

			foreach($complement as $key => $value)
			{
				if(is_object($source))
				{
					if(isset ($source->$key)) $source->$key = self::cop($source->$key, $value, $level + 1);
					else
						$source->$key = self::cop($null, $value, $level + 1);
				}
				if(is_array($source))
				{
					if(isset ($source [ $key ])) $source [ $key ] = self::cop($source [ $key ], $value, $level + 1);
					else
						$source [ $key ] = self::cop($null, $value, $level + 1);
				}
			}
		}

		return $source;
	}

	/**
	 * Redirect to way and exit
	 *
	 * @param string $way
	 */
	static function redirect($way)
	{
		header("Location: $way");
		exit();
	}

}