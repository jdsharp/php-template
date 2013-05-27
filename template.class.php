<?php
/**
 * PHP Simple Template
 *
 * This is a simple templating class that handles a few things such as variable scope
 * template path location
 *
 * PHP version 5
 *
 * LICENSE: Permission is hereby granted, free of charge, to any person obtaining a 
 * copy of this software and associated documentation files (the "Software"), to deal 
 * in the Software without restriction, including without limitation the rights to use, 
 * copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the 
 * Software, and to permit persons to whom the Software is furnished to do so, subject 
 * to the following conditions:
 * The above copyright notice and this permission notice shall be included in all copies 
 * or substantial portions of the Software. THE SOFTWARE IS PROVIDED "AS IS", WITHOUT 
 * WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES 
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT 
 * SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER 
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF 
 * OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @author     Jonathan Sharp <jdsharp.com>
 * @copyright  Copyright (c) 2013 Jonathan Sharp
 * @license    MIT 
 * @link       http://github.com/jdsharp/php-template
 */

class Template {
	var $file	= '';
	var $path	= '';
	private $vars 	= array();
	
	private $js		= array();
	private $css	= array();
	
	private $info	= array();
	private $error	= array();

	public function __construct($file = '', $vars = null)
	{
		$this->file = $file;
		if ( $vars !== null ) {
			$this->vars = $vars;
		}
	}

	public function set($k, $v)
	{
		$this->vars[$k] = $v;
	}

	public function setByRef($k, &$v)
	{
		$this->vars[$k] = &$v;
	}
	
	public function addJs($file)
	{
		$this->js[] = $file;
	}

	public function prependJs($file)
	{
		array_unshift($this->js, $file);
	}
	
	public function printJs()
	{
		$tmp = '';
		foreach ( $this->js AS $j ) {
			// We were passed a script block instead of a file URL
			if ( strtolower(substr($j, 0, 7)) == '<script' ) {
				$tmp .= $j . "\n";
			} else {
				$tmp .= '<script src="' . $j . '"></script>' . "\n";
			}
		}
		return $tmp;
	}
	
	public function addCss($file)
	{
		$this->css[] = $file;
	}
	
	public function printCss()
	{
		$tmp = '';
		foreach ( $this->css AS $t ) {
			// We were passed a style block instead of a file URL
			if ( strtolower(substr($t, 0, 6)) == '<style' ) {
				$tmp .= $t . "\n";
			} else {
				$tmp .= '<link rel="stylesheet" type="text/css" href="' . $t . '">' . "\n";
			}
		}
		return $tmp;
	}
	
	
	public function infoMessage($info)
	{
		$this->info[] = $info;
	}
	
	public function errorMessage($error)
	{
		$this->error[] = $error;
	}

	public function fetch($file = '')
	{
		ob_start();
		$this->display($file);
		$contents = ob_get_contents();
		ob_end_clean();
		return $contents;
	}

	public function display($file = '')
	{
		if ( $file != '' ) {
			$this->file = $file;
		}

		$this->file = self::path($this->file);

		// Do any processing of other template objects
		$c = get_class($this);
		foreach ($this->vars AS $k => $v) {
			if (is_object($v) && is_a($v, $c)) {
				// We have an object of the same type, lets fetch its contents
				$this->vars[$k] = $v->fetch();
			}
			// Manual extract (note we're doing this by reference)
			${$k} = &$this->vars[$k];
		}
		// Don't leak this variable into this scope
		unset($file);
		unset($c);
		return ((include $this->file) === true);
	}

	/**
	 * Flattens a multimentional array.
	 *
	 * Takes a multi-dimentional array as input and returns a flattened
	 * array as output. Implemented using a non-recursive algorithm.
	 * Example:
	 * <code>
	 * $in = array('John', 'Jim', array('Jane', 'Jasmine'), 'Jake');
	 * $out = array_flatten($in);
	 * // $out = array('John', 'Jim', 'Jane', 'Jasmine', 'Jake');
	 * </code>
	 *
	 * @author        Jonathan Sharp <jdsharp.com>
	 * @var            array
	 * @returns        array
	 */
	private static function arrayFlatten($array) {
		$tmp = array();
		while ( is_array($array) && (count($array) > 0) ) {
			$v = array_shift($array);
			if (is_array($v)) {
				$array = array_merge($v, $array);
			} else {
				$tmp[] = $v;
			}
		}
		return $tmp;
	}

	/**
	 * Ensures that our path is appropriate for what was passed in
	 *
	 * @author  Jonathan Sharp <jdsharp.com>
	 * @var     mixed
	 * @returns string
	 */
	private static function pathNormalize() {
		$ds   = DIRECTORY_SEPARATOR;
        $sep  = array('/' => '\\', '\\' => '/');
		$args = func_get_args();
		$tmp  = self::arrayFlatten($args);
		
		$path = implode($ds, $tmp);
		$path = str_replace($sep[$ds], $ds, $path);

		// Do the sub string to exclude './the/path' from getting converted'
		$path = preg_replace('|([^\.]{1})\.(' . $ds . '){1}|', '${1}', $path);
		$path = preg_replace('|[' . $ds . ']{2,}|', $ds, $path);
        
		return $path;
    }

    /**
	 * Private utility method for sanitizing and constructing a path
	 *
	 * <code>
	 * self::path('some', './number', array('of', 'path'), 'segments')
	 * </code>
	 *
	 * @author  Jonathan Sharp <jdsharp.com>
	 * @var     mixed
	 * @returns string
	 */
    private static function path() {
    	$ds   = DIRECTORY_SEPARATOR;
        $args = func_get_args();
	    $path = self::pathNormalize($args);
        
	    do {
	        $p = $path;
	        $path = preg_replace('|([^' . $ds . ']+' . $ds . '\.\.' . $ds . '){1}|', '', $path, 1);
	    } while ($p != $path);
	    
	    if (substr($path, -1) == $ds) {
	        $path = substr($path, 0, -1);
	    }
	    
		return $path;
	}
}
