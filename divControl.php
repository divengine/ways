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
 * @author Rafa Rodriguez <rafacuba2015@gmail.com>
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

    static $__webroot = "./";

    static $__hooks = array();

    /**
     * Boostrap
     *
     * @param string $wayvar            
     * @param string $defaultway            
     */
    static function bootstrap ($wayvar, $defaultway)
    {
        $way = self::get($wayvar);
        if (is_null($way))
            $way = $defaultway;
        
        self::$__current_way = $way;
        
        return self::callAll($way);
    }

    static function getWebRoot ()
    {
        $ruri = $_SERVER['REQUEST_URI'];
        
        if ($ruri[0] == "/")
            $ruri = substr($ruri, 1);
        
        $puri = explode("/", $ruri);
        $c = count($puri);
        
        if ($c > 0) {
            return str_repeat("../", $c - 1);
        }
        return '';
    }

    /**
     * Call all controllers
     *
     * @param string $way            
     */
    static function callAll ($way)
    {
        $data = array();
        
        foreach (self::$__listen as $wway => $controllers) {
            if (self::matchWay($wway, $way)) {
                foreach ($controllers as $controller) {
                    $result = self::call($controller, $data);
                    if (! is_array($result))
                        $result = array(
                                $controller => $result
                        );
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
        
        if ($way[0] == '/')
            $way = substr($way, 1);
        
        if ($pattern == $way)
            return true;
        
        $apattern = explode("/", $pattern);
        $away = explode("/", $way);
        $cpattern = count($apattern);
        
        // pattern suffix ".../a/b/c"
        if ($apattern[0] === '...' && $apattern[$cpattern - 1] !== '...') {
            $s = substr($pattern, 3);
            $p = strpos($way, $s);
            
            if ($p === strlen($way) - strlen($s))
                return true;
        }
        
        if ($apattern[0] !== '...' && $apattern[$cpattern - 1] === '...') {
            $s = substr($pattern, 0, strlen($pattern) - 3);
            $p = strpos($way, $s);
            
            if ($p === 0)
                return true;
        }
        
        // pattern preffix and suffix ".../a/b/c/..."
        if ($apattern[0] === '...' && $apattern[$cpattern - 1] === '...') {
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
            if (isset($apattern[$key])) {
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
     * @param string $id            
     */
    static function call ($controller, $data = array())
    {
        if (isset(self::$__controllers[$controller])) {
            $path = self::$__controllers[$controller]['path'];
            $classname = $controller;
            
            if (file_exists($path)) {
                
                ob_start();
                include_once $path;
                ob_end_clean();
                
                return $classname::Run($data);
            }
        }
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
     * @param string $id            
     * @param string $path            
     * @param string $classname            
     */
    static function register ($path)
    {
        if (! file_exists($path) && file_exists(PACKAGES . "$path"))
            $path = PACKAGES . $path;
        
        $classname = self::getClassName($path);
        
        $prop = self::getCodeProperties($path);
        
        self::$__controllers[$classname] = array(
                'path' => $path,
                'prop' => $prop
        );
        
        if (isset($prop['listen'])) {
            if (! is_array($prop['listen']))
                $prop['listen'] = array(
                        $prop['listen']
                );
            
            foreach ($prop['listen'] as $way)
                self::listenWay($classname, $way);
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
        $classname = explode("/", $path);
        $classname = $classname[count($classname) - 1];
        $classname = str_replace('.php', '', $classname);
        return $classname;
    }

    /**
     *
     * @param string $path            
     * @return array
     */
    static function getCodeProperties ($path, $prefix = '#divcontrol@')
    {
        if (! file_exists($path))
            return array();
        
        $f = fopen($path, "r");
        
        $l = strlen($prefix);
        $prop = array();
        while (! feof($f)) {
            $s = fgets($f);
            $s = trim($s);
            if (strtolower(substr($s, 0, $l)) == strtolower($prefix)) {
                $s = substr($s, $l);
                $s = trim($s);
                $p = strpos($s, '=');
                if ($p !== false) {
                    $pname = trim(substr($s, 0, $p));
                    $pval = substr($s, $p + 1);
                    if ($pname != '') {
                        if (isset($prop[$pname])) {
                            if (! is_array($prop[$pname]))
                                $prop[$pname] = array(
                                        $prop[$pname]
                                );
                            $prop[$pname][] = $pval;
                        } else
                            $prop[$pname] = $pval;
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
        
        if (isset($ini['divcontrol'])) {
            foreach ($ini['divcontrol'] as $val) {
                $classname = self::getClassName($val);
                
                self::register($val);
                
                $p = 'divcontrol-' . $classname;
                
                if (isset($ini[$p]['listen'])) {
                    if (is_array($ini[$p]['listen']))
                        foreach ($ini[$p]['listen'] as $way)
                            self::listenWay($classname, $way);
                    else
                        self::listenWay($classname, $ini[$p]['listen']);
                }
            }
        }
    }

    /**
     * Get from GET
     *
     * @param string $var            
     * @param mixed $default            
     */
    static function get ($var, $default = null)
    {
        if (isset($_GET[$var]))
            return $_GET[$var];
        return $default;
    }

    public function registerHook ($moment, $controller, $callto)
    {}

    public function beforeRun ($controller)
    {}

    public function afetrRun ($controller, &$results)
    {}
}