<?php

namespace divengine;

use RuntimeException;

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
 * @package divengine/ways
 * @author  Rafa Rodriguez [@rafageist] <rafageist@hotmail.com>
 * @version 2.4.0
 *
 * @link    https://divengine.com
 * @link    https://divengine.com/ways
 * @link    https://github.com/divengine/ways.git
 * @link    https://github.com/divengine/ways/wiki
 */

// Constants
!defined('PACKAGES') and define('PACKAGES', './');
!defined('DIV_WAYS_DEFAULT_WAY_VAR') and define('DIV_WAYS_DEFAULT_WAY_VAR', '_url');

define('DIV_WAYS_BEFORE_INCLUDE', 1);
define('DIV_WAYS_BEFORE_RUN', 2);
define('DIV_WAYS_BEFORE_OUTPUT', 3);
define('DIV_WAYS_AFTER_RUN', 4);
define('DIV_WAYS_RULE_FALSE', 'rule_false');

class ways
{

    // Constants
    const BEFORE_INCLUDE = DIV_WAYS_BEFORE_INCLUDE;

    const BEFORE_RUN = DIV_WAYS_BEFORE_RUN;

    const BEFORE_OUTPUT = DIV_WAYS_BEFORE_OUTPUT;

    const AFTER_RUN = DIV_WAYS_AFTER_RUN;

    const DEFAULT_WAY_VAR = DIV_WAYS_DEFAULT_WAY_VAR;

    const PROPERTY_ID = 'id';

    const PROPERTY_RULES = 'rules';

    private static $__version = '2.4.0';

    private static $__way_var;

    private static $__default_way = '/';

    public static $__controllers = [];

    public static $__listen = [];

    private static $__current_way;

    private static $__hooks = [];

    private static $__request_method;

    private static $__executed = 0;

    private static $__done = [];

    private static $__args_by_controller = [];

    private static $__cli_arguments;

    private static $__is_cli;

    private static $__rules = [];

    /** @var string The main way of your app */
    private static $__way_id;

    /** @var string The current way (each invoke generate new way id) */
    private static $__current_way_id;

    /** @var array Current data on the way */
    private static $__current_data = [];

    /**
     * Get current version
     *
     * @return float
     */
    public function getVersion()
    {
        return self::$__version;
    }

    /**
     * Get id of current way
     *
     * @return string
     */
    public static function getCurrentWayId()
    {
        if (self::$__current_way_id === null) {
            self::$__current_way_id = self::getWayId();
        }

        return self::$__current_way_id;
    }

    /**
     * Get data of current way
     *
     * @param string $way_id
     *
     * @return mixed
     */
    public static function getCurrentData($way_id = null)
    {
        if ($way_id === null) {
            $way_id = self::getCurrentWayId();
        }

        if (!isset(self::$__current_data[$way_id])) {
            self::$__current_data[$way_id] = [];
        }

        return self::$__current_data[$way_id];
    }

    /**
     * Update current data
     *
     * @param mixed  $data
     * @param string $way_id
     *
     * @return mixed
     */
    public static function updateCurrentData($data, $way_id = null)
    {
        if ($way_id === null) {
            $way_id = self::getCurrentWayId();
        }

        if (!isset(self::$__current_data[$way_id])) {
            self::$__current_data[$way_id] = [];
        }

        self::$__current_data[$way_id] = self::cop(self::$__current_data[$way_id], $data);

        return self::$__current_data[$way_id];
    }

    /**
     * Returns list of arguments of controller after bootstrap
     *
     * @param string $controller
     *
     * @return array|mixed|null
     */
    public static function getArgsByController($controller = null)
    {
        if ($controller === null) {
            return self::$__args_by_controller;
        }

        if (isset(self::$__args_by_controller)) {
            return self::$__args_by_controller[$controller];
        }

        return null;
    }

    /**
     * Get list of controller done after bootstrap
     *
     * @param string $way_id
     *
     * @return array
     */
    public static function getDone($way_id = null)
    {
        if ($way_id === null) {
            $way_id = self::getWayId();
        }

        return self::$__done[$way_id];
    }

    /**
     * Check if a controller was done after bootstrap
     *
     * @param        $controller
     * @param string $way_id
     *
     * @return bool
     */
    public static function isDone($controller, $way_id = null)
    {
        if ($way_id === null) {
            $way_id = self::getWayId();
        }

        return isset(self::$__done[$way_id][$controller]);
    }

    /**
     * Get relative request uri
     *
     * @return bool|string
     */
    public static function getRelativeRequestUri()
    {

        $uri = '/';
        if (isset($_SERVER['REQUEST_URI']) && !empty($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
            if (isset($_SERVER['SCRIPT_NAME'])) {
                $dir = dirname($_SERVER['SCRIPT_NAME']);
                $uri = substr($uri, strlen($dir));
            }
        }

        if ($uri === '') {
            $uri = '/';
        }

        return $uri;
    }

    /**
     * Bootstrap
     *
     * @param mixed   $way_var
     * @param string  $default_way
     * @param string  $output
     * @param boolean $show_output
     * @param string  $request_method
     *
     * @return array
     * @throws RuntimeException
     */
    public static function bootstrap($way_var = null, $default_way = null, &$output = '', $show_output = true, $request_method = null)
    {
        if (is_array($way_var)) {
            if (isset($way_var['request_method'])) {
                $request_method = $way_var['request_method'];
            }
            if (isset($way_var['show_output'])) {
                $show_output = $way_var['show_output'];
            }
            if (isset($way_var['default_way'])) {
                $default_way = $way_var['default_way'];
            }
            if (isset($way_var['way_var'])) {
                $way_var = $way_var['way_var'];
            }
        }

        if ($way_var !== null) {
            self::setWayVar($way_var);
        }

        if ($default_way !== null) {
            self::setDefaultWay($default_way);
        }

        $way = self::getCurrentWay($way_var, $default_way, $request_method);
        self::$__executed = 0;

        $data = self::getCurrentData();
        return self::callAll($way, $output, $show_output, $request_method, $default_way, $data, self::getCurrentWayId());
    }

    /**
     * Get request method from environment
     *
     * @return string
     */
    public static function getEnvironmentRequestMethod()
    {
        if (self::$__request_method === null) {
            self::$__request_method = 'GET';

            if (PHP_SAPI === 'cli') {
                self::$__request_method = 'CLI';
            }

            if (isset($_SERVER['REQUEST_METHOD'])) {
                self::$__request_method = strtoupper($_SERVER['REQUEST_METHOD']);
            }
        }

        return self::$__request_method;
    }

    /**
     * Get request method
     *
     * @return string
     */
    public static function getRequestMethod(){
        // TODO: return the method of current way ?
        return self::getEnvironmentRequestMethod();
    }

    /**
     * Return total of executions
     *
     * @return int
     */
    public static function getTotalExecutions()
    {
        return self::$__executed;
    }

    /**
     * Set default way for all future bootstraps
     *
     * @param $way
     */
    public static function setDefaultWay($way)
    {
        self::$__default_way = $way;
    }

    /**
     * Get default way
     *
     * @return string
     */
    public static function getDefaultWay()
    {
        return self::$__default_way;
    }

    /**
     * Set the GET var that storage the way
     *
     * @param $way_var
     */
    public static function setWayVar($way_var)
    {
        self::$__way_var = $way_var;
    }

    /**
     * Return the GET var that storage the way
     *
     * @return string
     */
    public static function getWayVar()
    {
        return self::$__way_var;
    }

    /**
     * Return the current way
     *
     * @param string $way_var
     * @param string $default_way
     * @param string $request_method
     *
     * @return null
     */
    public static function getCurrentWay($way_var = null, $default_way = null, $request_method = null)
    {

        if ($default_way === null || empty($default_way)) {
            $default_way = self::$__default_way;
        }

        if ($way_var === null) {
            if (self::$__way_var === null) {
                $way_var = DIV_WAYS_DEFAULT_WAY_VAR;
            } else {
                $way_var = self::$__way_var;
            }
        }

        if ($request_method === null) {
            $request_method = self::getEnvironmentRequestMethod();
        }

        // checking for current way
        if (self::$__current_way === null) {

            $way = null;

            if ($request_method !== 'CLI') {
                $way = self::get($way_var);

                if ($way === null) {
                    $way = self::getRelativeRequestUri();
                }

                if ($way === '/') {
                    $way = $default_way;
                }
            } else {
                $way = '';
                $total_arguments = count($_SERVER['argv']);
                for ($i = 1; $i < $total_arguments; $i++) {
                    $way .= '/'.$_SERVER['argv'][$i];
                }
            }

            if ($way === null || empty($way) || $way === '/') {
                $way = $default_way;
            }
            self::$__current_way = str_replace('///', '//', $request_method.'://'.$way);
        }

        // if default_way is forced to "/", this will return "/"
        $uri = parse_url(self::$__current_way);
        if ($uri === false || (!isset($uri['path']) && !isset($uri['host']))) {
            $uri = parse_url($default_way);
            if (isset($uri['scheme'])) {
                $default_way = str_replace('///', '//', $request_method.'://'.$default_way);
            }

            return $default_way;
        }

        return self::$__current_way;
    }

    /**
     * Get relative path to root folder of website
     *
     * @return string
     */
    public static function getWebRoot()
    {
        if (isset($_SERVER['REQUEST_URI'])) {
            $request_uri = $_SERVER['REQUEST_URI'];

            if ($request_uri[0] === '/') {
                $request_uri = substr($request_uri, 1);
            }

            $uri_parts = explode('/', $request_uri);
            $c = count($uri_parts);

            if ($c > 0) {
                return str_repeat('../', $c - 1);
            }
        }

        return '';
    }

    /**
     * Invoke a way and get data resulting from the flow
     *
     * @param        $way
     * @param array  $data
     *
     * @param string $output
     *
     * @return array
     * @throws RuntimeException
     */
    public static function invoke($way, $data = [], &$output = '')
    {
        // generate new way
        $way_id = self::getWayId(true);

        // save current status
        $save = self::$__current_way_id;
        $save_current_way = self::$__current_way;

        // change current status
        self::$__current_way = $way;
        self::$__current_way_id = $way_id;

        self::updateCurrentData($data, $way_id);

        // call to all control points
        $result = self::callAll($way, $output, true, null, '/', $data, $way_id);

        // restore status
        self::$__current_way_id = $save;
        self::$__current_way = $save_current_way;

        // return resulting data
        return $result;
    }

    /**
     * Get current or new way id
     *
     * @param bool $new
     *
     * @return string
     */
    public static function getWayId($new = false)
    {
        if ($new) {
            return uniqid('', true);
        }

        if (self::$__way_id === null) {
            self::$__way_id = uniqid('', true);
        }

        return self::$__way_id;
    }

    /**
     * Call all controllers
     *
     * @param mixed   $way
     * @param string  $output
     * @param boolean $show_output
     * @param string  $request_method
     * @param string  $default_way
     * @param array   $data
     * @param string  $way_id
     *
     * @return array
     * @throws \RuntimeException
     */
    public static function callAll($way, &$output = '', $show_output = true, $request_method = null, $default_way = '/', $data = [], $way_id = null)
    {

        if ($way_id === null) {
            $way_id = self::getWayId();
        }

        if (is_array($way)) {
            if (isset($way['data'])) {
                $data = self::cop($way['data'], $data);
            }
            if (isset($way['default_way'])) {
                $default_way = $way['default_way'];
            }
            if (isset($way['request_method'])) {
                $request_method = $way['request_method'];
            }
            if (isset($way['show_output'])) {
                $show_output = $way['show_output'];
            }
            if (isset($way['way'])) {
                $way = $way['way'];
            }
        }

        $default_method = self::getEnvironmentRequestMethod();
        $request_methods = [];

        $way = self::parseWay($way);

        foreach ($way['methods'] as $method) {
            $request_methods[$method] = $method;
        }

        if ($request_method !== null) {
            $request_methods[$request_method] = $request_method;
        }

        if (empty($request_methods)) {
            $request_methods[$default_method] = $default_method;
        }

        $way = $way['way'];

        foreach (self::$__listen as $pattern => &$methods) {
            $args = [];
            $pattern = trim($pattern);

            if ($pattern === null || empty($pattern) || $pattern === '/') {
                $pattern = $default_way;
            }

            if (self::match($pattern, $way, $args)) {
                foreach ($request_methods as $req_method) {
                    $controllers = [];

                    if (isset($methods['*'])) {
                        $controllers = array_unique(array_merge($controllers, $methods['*']));
                    }

                    if (isset($methods[$req_method])) {
                        $controllers = array_unique(array_merge($controllers, $methods[$req_method]));
                    }

                    foreach ($controllers as &$controller) { // great fix ! type the & before
                        if (!isset(self::$__done[$way_id][$controller])) {

                            // call to control point
                            $result = self::call($controller, $data, $args, $output, $show_output, $way_id);
                            $data = self::cop($data, $result);

                            // update current data on the way
                            self::updateCurrentData($data, $way_id);

                            // great fix ! Update again $controllers
                            // in previous self::call a new controller could be added

                            if (isset($methods['*'])) {
                                $controllers = array_unique(array_merge($controllers, $methods['*']));
                            }

                            if (isset($methods[$req_method])) {
                                $controllers = array_unique(array_merge($controllers, $methods[$req_method]));
                            }

                            if (!isset(self::$__args_by_controller[$way_id])) {
                                self::$__args_by_controller[$way_id] = [];
                            }

                            if (!isset(self::$__args_by_controller[$way_id][$controller])) {
                                self::$__args_by_controller[$way_id][$controller] = [];
                            }

                            self::$__args_by_controller[$way_id][$controller][$pattern] = $args;
                        }
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
    public static function clearSideSlashes($value)
    {
        if (isset($value[0])) {
            if ($value[0] === '/') {
                $value = substr($value, 1);
            }
            if (substr($value, -1) === '/') {
                $value = substr($value, 0, -1);
            }
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
    public static function clearDoubleSlashes($value)
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
    public static function replaceRecursive($search, $replace, $source)
    {
        while (strpos($source, $search) !== false) {
            $source = str_replace($search, $replace, $source);
        }

        return $source;
    }

    /**
     * Normalize pattern for better matching
     *
     * @param $value
     *
     * @return mixed
     */
    public static function normalizePattern($value)
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
    public static function normalizeWay($way, $pattern)
    {

        $new_way = '';

        while (true) {
            $bracket = strpos($pattern, '{');
            $star = strpos($pattern, '*');

            if ($bracket === false) {
                $bracket = -1;
            }
            if ($star === false) {
                $star = -1;
            }
            if ($bracket === -1 && $star === -1) {
                $new_way .= $way;
                break;
            }

            if ($bracket < $star || $star === -1) {
                $p = $bracket; // first open bracket
                $new_way .= substr($way, 0, $p).'/';
                $new_way = self::clearDoubleSlashes($new_way);

                $p1 = strpos($pattern, '}', $p + 1); // first close bracket

                if ($p1 === false) {
                    break;
                }

                $ch = substr($pattern, $p1 + 1, 1); // next char from close bracket

                $p3 = false;
                if (!empty($ch) && isset($way[$p])) {
                    $p3 = @strpos($way, $ch, $p);
                }

                if ($p3 === false) {
                    $new_way .= substr($way, $p);
                    $way = '';
                } else {
                    $new_way .= substr($way, $p, $p3 - $p).'/';
                    $way = substr($way, $p3);
                }

                $new_way = self::clearDoubleSlashes($new_way);
                $pattern = substr($pattern, $p1 + 1);
            } else {
                $p = $star;

                $new_way .= substr($way, 0, $p).'/';
                $new_way = self::clearDoubleSlashes($new_way);

                // can be changed with $pattern[$p+1] ?? '' but dont work in php 5.4
                // and if $p > length of $pattern - 1, will be an error
                $ch = substr($pattern, $p + 1, 1); // next char from close bracket

                $p3 = false;
                if (isset($way[$p])) {
                    $p3 = strpos($way, $ch, $p);
                }

                if ($p3 === false) {
                    $new_way .= substr($way, $p);
                    $way = '';
                } else {
                    $new_way .= substr($way, $p, $p3 - $p).'/';
                    $way = substr($way, $p3);
                }

                $new_way = self::clearDoubleSlashes($new_way);
                $pattern = substr($pattern, $p + 1);
            }
        }

        return self::clearSideSlashes(self::clearDoubleSlashes($new_way));
    }


    /**
     * Internal match
     *
     * @param       $pattern
     * @param       $way
     * @param array $args
     * @param bool  $normalizeWay
     *
     * @return bool
     * @throws \RuntimeException
     */
    private static function matchInternal($pattern, $way, &$args = [], $normalizeWay = true)
    {
        $pattern = self::clearDoubleSlashes(self::clearSideSlashes($pattern));
        if ($pattern === '*' || $pattern === '...') {
            return true;
        }

        $way = self::clearDoubleSlashes(self::clearSideSlashes($way));
        if ($pattern === $way) {
            return true;
        }

        if ($normalizeWay) {
            $way = self::normalizeWay($way, $pattern);
        }

        $pattern = self::normalizePattern($pattern);
        $array_pattern = explode('/', $pattern);
        $array_pattern_count = count($array_pattern);
        $away = explode('/', $way);
        $away_count = count($away);
        $count_pattern = count($array_pattern);

        // pattern suffix ".../a/b/c"
        if ($array_pattern[0] === '...' && $array_pattern[$count_pattern - 1] !== '...') {

            $s = substr($pattern, 3);
            $p = strpos($way, $s);

            if ($p === strlen($way) - strlen($s)) {
                return true;
            }

            $new_pattern = '';
            $new_way = '';
            $j = $away_count - 1;
            for ($i = $array_pattern_count - 1; $i > 0; $i--) {
                $new_pattern = $array_pattern[$i].'/'.$new_pattern;
                if (isset($away[$j])) {
                    $new_way = $away[$j].'/'.$new_way;
                    $j--;
                }
            }

            return self::matchInternal($new_pattern, $new_way, $args, $normalizeWay);
        }

        // pattern prefix "a/b/c/..."
        if ($array_pattern[0] !== '...' && $array_pattern[$count_pattern - 1] === '...') {
            $s = substr($pattern, 0, -3);
            $p = strpos($way, $s);

            if ($p === 0) {
                return true;
            }

            $new_pattern = '';
            $new_way = '';
            $j = 0;
            for ($i = 0; $i < $array_pattern_count - 1; $i++) {
                $new_pattern .= $array_pattern[$i].'/';
                if (isset($away[$j])) {
                    $new_way .= $away[$j].'/';
                    $j++;
                }
            }

            return self::matchInternal($new_pattern, $new_way, $args, $normalizeWay);
        }

        // pattern prefix and suffix ".../a/b/c/..."
        if ($array_pattern[0] === '...' && $array_pattern[$count_pattern - 1] === '...') {
            $s = substr($pattern, 0, -3);
            $s = substr($s, 3);
            // $s begin and finish with '/', --> /a/b/c/

            $p = strpos($way, $s);

            if ($p !== false && $p !== 0 && $p !== strlen($way) - strlen($s)) {
                return true;
            }

            // search pattern in the way (best match)
            // pattern:      <-- .../a/b/c/... -->
            // way:          1/2/3/4/a/b/c/5/6/7/8

            if ($away_count >= $array_pattern_count - 2) {
                $matches_max = 0;
                $pos = -1;
                $args_max = [];
                for ($j = 0; $j < $away_count; $j++) {
                    $matches = 0;
                    $new_args = [];
                    for ($i = 1; $i < $array_pattern_count - 1; $i++) {

                        if ($j + $i - 1 >= $away_count) {
                            $matches = 0;
                            break;
                        }

                        $part_pattern = $array_pattern[$i];

                        if ($away[$j + $i - 1] === $part_pattern) {
                            $matches++;
                        } elseif ($part_pattern[0] === '{' && substr($part_pattern, -1) === '}') {
                            $arg = substr($part_pattern, 1, -1);
                            $arg_value = $away[$j + $i - 1];
                            $value_match = self::argChecker($arg, $arg_value, $arg);

                            if ($value_match) {
                                $new_args[$arg] = $away[$j + $i - 1];
                                $matches += 0.75;
                            } else {
                                $matches = 0;
                                break;
                            }
                        } elseif ($part_pattern === '*') {
                            $matches += 0.5;
                        } else {
                            $matches = 0;
                            break;
                        }
                    }

                    if ($matches_max < $matches) {
                        $matches_max = $matches;
                        $pos = $j;
                        $args_max = $new_args;
                    }
                }

                if ($pos > -1) {
                    $args = $args_max;

                    return true;
                }
            }
        }

        // pattern *
        // for example: a/b/c, a/b/*, a/*/c, */b/c, */b/*, */*/*,
        // a/*/*, */*/c

        if (count($away) !== count($array_pattern)) {
            return false;
        }

        $result = true;
        foreach ($away as $key => $part) {
            if (!isset($array_pattern[$key])) {
                $result = false;
                break;
            }

            $part_pattern = $array_pattern[$key];

            if (isset($part_pattern[2]) && $part_pattern[0] === '{' && substr($part_pattern, -1) === '}') {
                $arg = substr($part_pattern, 1, -1);
                $value_match = self::argChecker($arg, $part, $arg);

                if ($value_match) {
                    $args[$arg] = $part;
                    continue;
                }
            }

            if ($part !== $part_pattern && $part_pattern !== '*') {
                $result = false;
                break;
            }
        }

        return $result;
    }

    /**
     * Match ways
     *
     * @param string $pattern
     * @param string $way
     * @param array  $args
     *
     * @return boolean
     *
     * @throws RuntimeException
     */
    public static function match($pattern, $way = null, &$args = [])
    {
        if ($way === null) {
            $way = self::getCurrentWay();
        }

        $result = self::matchInternal($pattern, $way, $args);

        if ($result === false) {
            $result = self::matchInternal($pattern, $way, $args, false);
        }

        $cliParams = self::getCliParams();
        $args = self::cop($cliParams, $args);

        return $result;
    }

    /**
     * Check argument value in a way
     *
     * @param string $pattern
     * @param string $arg_value
     * @param string $arg
     *
     * @return bool
     * @throws \RuntimeException
     */
    public static function argChecker($pattern, $arg_value, &$arg)
    {
        if (is_numeric($arg_value)) {
            $arg_value *= 1;
        }

        $value_match = true;
        if (strpos($pattern, '|') !== false) { // important !
            list($arg, $checker) = explode('|', $pattern);
            if (is_callable($checker)) {
                if (strpos($checker, '::') !== false) { // important !
                    list ($checker_class, $checker_method) = explode('::', $checker);
                    $value_match = $checker_class::$checker_method($arg_value);
                } elseif ($checker === 'is_bool') {
                    $value_match = (strtolower($arg_value) === 'true' || $arg_value === 1);
                } else {
                    $value_match = $checker($arg_value);
                }
            } else {
                throw new RuntimeException("Argument checker $checker is not callable");
            }
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
     * @param string  $way_id
     *
     * @return mixed
     */
    public static function call($controller, $data = [], $args = [], &$output = '', $show_output = false, $way_id = null)
    {
        if ($way_id === null) {
            $way_id = self::getWayId();
        }

        $original_controller = $controller;

        // default method to run is Run()
        $action = 'Run';

        $ignore_properties = false;
        if (strpos($controller, '@')) { // important!
            list($controller, $action) = explode('@', $controller);
            $ignore_properties = true;
        }

        if (isset(self::$__done[$way_id][$original_controller])) {
            return $data;
        }

        if (isset(self::$__controllers[$controller])) {
            // first tag the controller as done!
            self::$__done[$way_id][$original_controller] = true;

            $control = self::$__controllers[$controller];

            // check rules
            if (array_key_exists(self::PROPERTY_RULES, $control['prop'])) {
                $rules = $control['prop'][self::PROPERTY_RULES];

                if (is_string($rules)) {
                    $rules = [$rules];
                }

                $method = "{$controller}@{$action}";
                if (isset($rules[$method])) {
                    $rules = $rules[$method];
                } elseif (isset($rules[$action])) {
                    $rules = $rules[$action];
                }

                if (is_string($rules)) {
                    $rules = [$rules];
                }

                foreach ($rules as $rule) {
                    $check = true;
                    if (is_string($rule) && array_key_exists($rule, self::$__rules)) {
                        $check = self::checkRule($rule, $data, $args, $control['prop']);
                    } elseif (is_callable($rule)) {
                        $check = $rule();
                    }

                    if ($check === false) // prevent execution
                    {
                        return DIV_WAYS_RULE_FALSE;
                    }
                }
            }

            $class_name = $control['class_name'];

            // check for custom method
            if (!$ignore_properties && isset($control['prop']['method'])) {
                $action = $control['prop']['method'];
            }

            if (isset($control['prop']['require'])) {
                $require = $control['prop']['require'];

                if (!is_array($require)) {
                    $require = [$require];
                }

                foreach ($require as $req) {
                    // check if required controller is done (for performance)
                    if (!isset(self::$__done[$way_id][$req])) {
                        $result = self::call($req, $data, $args, $output, $show_output, $way_id);
                        $data = self::cop($data, $result);
                    }
                }
            }

            $hooks = [];
            if (isset(self::$__hooks[$controller])) {
                $hooks = self::$__hooks[$controller];
            }

            if ($control['is_closure'] || file_exists($control['path'])) {

                // hook before include
                if (isset($hooks[DIV_WAYS_BEFORE_INCLUDE])) {
                    $result = self::processHooks($hooks[DIV_WAYS_BEFORE_INCLUDE], $data, $args, $output, $show_output);
                    $data = self::cop($data, $result);
                }

                $include_output = '';
                if (!$control['is_closure']) {
                    ob_start();
                    include_once $control['path'];
                    $result = self::getCurrentData(); // great fix!
                    $data = self::cop($data, $result);
                    $include_output = ob_get_contents();
                    $output .= $include_output;
                    ob_end_clean();
                }

                // hook after include
                if (isset($hooks[DIV_WAYS_BEFORE_RUN])) {
                    $result = self::processHooks($hooks[DIV_WAYS_BEFORE_RUN], $data, $args, $output, $show_output);
                    $data = self::cop($data, $result);
                }

                // running...
                $sum_executed = !(isset($control['prop']['type']) && strtolower(trim($control['prop']['type'])) === 'background');
                $action_output = '';
                $result = [];
                if ($control['is_closure']) {
                    $closure = $control['closure'];
                    ob_start();
                    $result = $closure($data, $args, $control['prop']);
                    $action_output = ob_get_clean();
                    $data = self::cop($data, $result);
                } elseif (class_exists($class_name)) {
                    if (method_exists($class_name, $action)) {
                        ob_start();
                        $result = $class_name::$action($data, $args, $control['prop']);
                        $action_output = ob_get_clean();
                    }
                } else {
                    // hook before output
                    if (isset($hooks[DIV_WAYS_BEFORE_OUTPUT])) {
                        $result = self::processHooks($hooks[DIV_WAYS_BEFORE_OUTPUT], $data, $args, $output, $show_output);
                        $data = self::cop($data, $result);
                    }

                    // check if action is a function
                    if (function_exists($action)) {
                        ob_start();
                        $result = $action($data, $args);
                        $action_output = ob_get_clean();
                    } else
                        // if not exists a class::method and not exists a function, then output is the include output
                        // and action output is empty
                        if ($show_output) {
                            echo $include_output;
                        }
                }

                // if a method/function exists, action output is not empty, then
                // show action output
                $output .= $action_output;
                if ($show_output) {
                    echo $action_output;
                }

                if ($sum_executed) {
                    self::$__executed++;
                }

                if (!is_array($result)) {
                    $result = [$controller => $result];
                }

                $data = self::cop($data, $result);

                // hook after run
                if (isset($hooks[DIV_WAYS_AFTER_RUN])) {
                    $result = self::processHooks($hooks[DIV_WAYS_BEFORE_OUTPUT], $data, $args, $output, $show_output);
                    $data = self::cop($data, $result);
                }
            }
        }

        // self::$__done[$way_id][$original_controller] = true;

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
     * @param string  $way_id
     *
     * @return mixed
     */
    public static function processHooks($hooks, $data, $args, &$output = '', $show_output = false, $way_id = null)
    {
        if ($way_id === null) {
            $way_id = self::getWayId();
        }
        foreach ($hooks as $call) {
            if (is_string($call) && isset(self::$__done[$way_id][$call])) {
                continue;
            }

            if (is_callable($call)) {
                ob_start();
                if (is_string($call) && strpos($call, '::') !== false) {
                    list($call_class, $call_method) = explode('::', $call);
                    $result = $call_class::$call_method($data, $args);
                    $action_output = ob_get_contents();
                } else {
                    $result = $call($data, $args);
                    $action_output = ob_get_contents();
                }
                ob_end_clean();

                if ($show_output) {
                    echo $action_output;
                }
            } else {
                $result = self::call($call, $data, $args, $output, $show_output, $way_id);
            }

            if (is_scalar($result)) {
                if (is_string($call)) {
                    $result = [$call => $result];
                } else {
                    $result = ['hook-'.uniqid('', true) => $result];
                }
            }

            if (is_array($result) || is_object($result)) {
                $data = self::cop($data, $result);
            }
        }

        return $data;
    }

    /**
     * Parse a way
     *
     * @param string $way
     *
     * @return array
     */
    public static function parseWay($way)
    {
        $result = [
            'methods' => [],
            'way'     => '',
        ];

        $url = parse_url($way);

        if (!isset($url['scheme'])) {
            $url['scheme'] = self::getEnvironmentRequestMethod();
        }

        if (!isset($url['host'])) {
            $url['host'] = '';
        }
        if (!isset($url['path'])) {
            $url['path'] = '';
        }
        if (strpos($url['host'], '/') === 0) {
            $url['host'] = substr($url['host'], 1);
        }
        if (strpos($url['path'], '/') === 0) {
            $url['path'] = substr($url['path'], 1);
        }

        $result['methods'] = explode('-', strtoupper($url['scheme']));
        $result['way'] = $url['host'].'/'.$url['path'];

        return $result;
    }

    /**
     * Listen way
     *
     * @param string $pattern
     * @param string $controller
     * @param mixed  $properties
     *
     * @return string
     */
    public static function listen($pattern, $controller, $properties = [])
    {
        $way = self::parseWay($pattern);

        if ($pattern === '*') {
            $way['methods'][0] = '*';
        }

        // $properties is the ID when is a string
        if (is_string($properties)) {
            $properties = [self::PROPERTY_ID => $properties];
        }

        if (!isset($properties[self::PROPERTY_ID])) {
            $properties[self::PROPERTY_ID] = uniqid('closure-', true);
        }

        if (!isset($properties['type'])) {
            $properties['type'] = 'foreground';
        }

        $properties['listen'] = $way;

        if (!isset(self::$__listen[$way['way']])) {
            self::$__listen[$way['way']] = [];
        }

        foreach ($way['methods'] as $request_method) {
            if (!isset(self::$__listen[$way['way']][$request_method])) {
                self::$__listen[$way['way']][$request_method] = [];
            }
        }

        if (!is_string($controller) && is_callable($controller)) {
            self::$__controllers[$properties[self::PROPERTY_ID]] = [
                'class_name' => null,
                'prop'       => $properties,
                'path'       => null,
                'is_closure' => true,
                'closure'    => $controller,
            ];

            $controller = $properties[self::PROPERTY_ID];
        }

        foreach ($way['methods'] as $request_method) {
            self::$__listen[$way['way']][$request_method][] = $controller;
        }

        return $properties[self::PROPERTY_ID];
    }

    /**
     * Register a controller
     *
     * @param string $path
     * @param array  $properties
     */
    public static function register($path, $properties = [])
    {
        if (!file_exists($path) && file_exists(PACKAGES.$path)) {
            $path = PACKAGES.$path;
        }

        $namespace = null;
        $prop = self::getCodeProperties($path, '#', $namespace);
        $class_name = self::getClassName($path);
        $class_name = $namespace === null ? $class_name : "$namespace\\$class_name";
        $prop = self::cop($prop, $properties);

        if (!isset($prop[self::PROPERTY_ID])) {
            $prop[self::PROPERTY_ID] = $path;
        }

        self::$__controllers[$prop[self::PROPERTY_ID]] = [
            'class_name' => $class_name,
            'path'       => $path,
            'prop'       => $prop,
            'is_closure' => false,
            'closure'    => null,
        ];

        // other listeners (by method)
        foreach ($prop as $key => $value) {
            if (strpos($key, 'listen@') === 0) {
                if (!is_array($prop[$key])) {
                    $prop[$key] = [
                        $prop[$key],
                    ];
                }
                $method = trim(substr($key.' ', 7));
                $action = $prop[self::PROPERTY_ID].'@'.$method;

                if (isset($prop["rules@{$method}"])) {
                    $rules = $prop["rules@{$method}"];
                    if (!is_array($rules)) {
                        $rules = [$rules];
                    }
                    foreach ($rules as $rule) {
                        if (!empty(self::$__controllers[$prop[self::PROPERTY_ID]])) {

                            if (!isset(self::$__controllers[$prop[self::PROPERTY_ID]]['prop'][self::PROPERTY_RULES])){
                                self::$__controllers[$prop[self::PROPERTY_ID]]['prop'][self::PROPERTY_RULES] = [];
                            }

                            if (is_string(self::$__controllers[$prop[self::PROPERTY_ID]]['prop'][self::PROPERTY_RULES])) {
                                self::$__controllers[$prop[self::PROPERTY_ID]]['prop'][self::PROPERTY_RULES] = [self::$__controllers[$prop[self::PROPERTY_ID]]['prop'][self::PROPERTY_RULES]];
                            }

                            self::$__controllers[$prop[self::PROPERTY_ID]]['prop'][self::PROPERTY_RULES][$action][] = $rule;
                        }
                    }
                }

                foreach ($prop[$key] as $way) {
                    self::listen($way, $action);
                }
            }
        }

        // default listener
        if (isset($prop['listen'])) {
            if (!is_array($prop['listen'])) {
                $prop['listen'] = [
                    $prop['listen'],
                ];
            }

            if (isset($prop['rules'])) {
                $rules = $prop['rules'];

                if (!is_array($rules)) {
                    $rules = [$rules];
                }

                foreach ($rules as $rule) {
                    if (!is_array(self::$__controllers[$prop[self::PROPERTY_ID]]['prop'][self::PROPERTY_RULES]))
                        self::$__controllers[$prop[self::PROPERTY_ID]]['prop'][self::PROPERTY_RULES] = [];
                    self::$__controllers[$prop[self::PROPERTY_ID]]['prop'][self::PROPERTY_RULES]['Run'][] = $rule;
                }

            }


            foreach ($prop['listen'] as $way) {
                self::listen($way, $prop[self::PROPERTY_ID]);
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
    public static function getClassName($path)
    {
        $class_name = explode('/', $path);
        $class_name = $class_name[count($class_name) - 1];
        $class_name = str_replace('.php', '', $class_name);

        return $class_name;
    }

    /**
     * Looking for properties in PHP comments (#property = value)
     *
     * @param string $path
     * @param string $prefix
     * @param string $namespace
     *
     * @return array
     */
    public static function getCodeProperties($path, $prefix, &$namespace = null)
    {
        if (!file_exists($path)) {
            return [];
        }

        $f = fopen($path, 'rb');

        $property_value = null;

        $l = strlen($prefix);
        $prop = [];
        $namespace = null;

        while (!feof($f)) {
            $s = fgets($f);
            $s = trim($s);

            // detect namespace
            $ss = strtolower(trim($s));

            if ($namespace === null && strpos($ss, 'namespace ') === 0) {
                $namespace = trim(substr($s, 9, -1));
            }

            if (stripos($s, strtolower($prefix)) === 0) {
                $s = substr($s, $l);
                $s = trim($s);
                $p = strpos($s, '=');
                if ($p !== false) {
                    $property_name = trim(substr($s, 0, $p));
                    $property_value = substr($s, $p + 1);
                    if ($property_name !== '') {
                        if (isset($prop[$property_name])) {
                            if (!is_array($prop[$property_name])) {
                                $prop[$property_name] = [
                                    $prop[$property_name],
                                ];
                            }
                            $prop[$property_name][] = trim($property_value);
                        } else {
                            $prop[$property_name] = trim($property_value);
                        }
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
    public static function get($var, $default = null)
    {
        return isset($_GET[$var]) ? $_GET[$var] : $default;
    }

    /**
     * Register hook
     *
     * @param integer $moment
     * @param string  $controller
     * @param mixed   $call
     */
    public static function hook($moment, $controller, $call)
    {
        if (!isset(self::$__hooks[$controller])) {
            self::$__hooks[$controller] = [];
        }

        if (!isset(self::$__hooks[$controller][$moment])) {
            self::$__hooks[$controller][$moment] = [];
        }

        self::$__hooks[$controller][$moment][] = $call;
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
    final public static function cop(&$source, $complement, $level = 0)
    {
        $null = null;

        if ($source === null) {
            return $complement;
        }

        if ($complement === null) {
            return $source;
        }

        if (is_scalar($source) && is_scalar($complement)) {
            return $complement;
        }

        if (is_scalar($complement) || is_scalar($source)) {
            return $source;
        }

        if ($level < 100) { // prevent infinite loop
            if (is_object($complement)) {
                $complement = get_object_vars($complement);
            }

            foreach ($complement as $key => $value) {
                if (is_object($source)) {
                    if (isset ($source->$key)) {
                        $source->$key = self::cop($source->$key, $value, $level + 1);
                    } else {
                        $source->$key = self::cop($null, $value, $level + 1);
                    }
                }
                if (is_array($source)) {
                    if (isset ($source [$key])) {
                        $source [$key] = self::cop($source [$key], $value, $level + 1);
                    } else {
                        $source [$key] = self::cop($null, $value, $level + 1);
                    }
                }
            }
        }

        return $source;
    }

    /**
     * Get CLI arguments (each param begin with '-')
     *
     * @param array $map
     *
     * @return array
     */
    public static function getCliParams($map = null)
    {
        if (self::$__cli_arguments === null) {
            $params = [];

            if (isset($_SERVER['argv'])) {

                $i = 1;
                $last_key = false;

                do {

                    if (!isset($_SERVER['argv'][$i])) {
                        break;
                    }

                    $arg = trim($_SERVER['argv'][$i]);
                    if (isset($arg[0])) {
                        if (strpos($arg, '-') === 0) {
                            $params[$arg] = true;
                            $last_key = $arg;
                        } else {
                            if ($last_key) {
                                $params[$last_key] = $arg;
                            } else {
                                $params[] = $arg;
                            }
                            $last_key = false;
                        }
                    }

                    $i++;
                } while ($i < $_SERVER['argc']);
            }

            $result = $params;
            if ($map !== null && is_array($map)) {
                $result = [];
                foreach ($map as $param) {
                    if (isset($params[$param])) {
                        $result[$param] = $params[$param];
                    }
                }
            }

            self::$__cli_arguments = $result;
        }

        return self::$__cli_arguments;
    }

    /**
     * Redirect to way and exit
     *
     * @param string $way
     */
    public static function redirect($way)
    {
        header("Location: $way");
        exit();
    }

    /**
     * Define a rule
     *
     * @param $ruleName
     * @param $rule
     */
    public static function rule($ruleName, $rule)
    {
        self::$__rules[$ruleName] = $rule;
    }

	/**
	 * Check a rule
	 *
	 * @param $ruleName
	 *
	 * @param  array  $data
	 * @param  array  $args
	 * @param  array  $props
	 *
	 * @return bool
	 */
    public static function checkRule($ruleName, $data = [], $args = [], $props = [])
    {
        $rule = self::$__rules[$ruleName];

        return (bool) $rule($data, $args, $props);
    }

    /**
     * Return true if the script was executed in the CLI environment
     *
     * @return boolean
     */
    final public static function isCli()
    {
        if (self::$__is_cli === null) {
            self::$__is_cli = (!isset ($_SERVER ['SERVER_SOFTWARE']) && (PHP_SAPI === 'cli' || (is_numeric($_SERVER ['argc']) && $_SERVER ['argc'] > 0)));
        }

        return self::$__is_cli;
    }

    // ----------------- UTILS -----------------------

    /**
     * Output a JSON REST response
     *
     * @param mixed $data
     * @param int   $http_response_code
     */
    public static function rest($data, $http_response_code = 200)
    {
        header('Content-type: application/json', true, $http_response_code);
        http_response_code($http_response_code);
        echo json_encode($data);
    }
}