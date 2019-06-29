<?php
namespace Ekliptor\CashP;
// Copyright @Ekliptor 2016-2019
// based on: https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader-examples.md
// and Composer ClassLoader.php https://getcomposer.org/

class AppAutoload {
	const DEBUG = false;
	private static $loader = null;
	
	private $namespaceMap;
	private $fallbackDirs;
	
	public static function getAppAutoload() {
		if (static::$loader !== null)
			return static::$loader;
		
		$appAutoload = new AppAutoload();
		spl_autoload_register(function ($class) use ($appAutoload) {
			// class only has the namespace if called from outside that namespace like: new Name1\Class()
			// or within the namespace declaration, but NOT with 'use Name1;' (see composer fallbackDirsPsr4 variable)
		
			// base directory for all our autoload classes
			//$srcDir = __DIR__ . '/src/'; // set inside map.php
			$classPath = str_replace('\\', '/', $class);
		
			// closure also prevents accessing self/$this if we move this to a class
			$includeFile = function($file) {
				if (file_exists($file)) {
					// require_once not needed. 2 classes with different names can not have the same path
					// use include instead of require to throw only a warning?
					require $file;
					return true;
				}
				return false;
			};
		
			// try our known namespaces first
			// composer optiizes performance by using a map of maps with the first letter of the namespace as key
			$namespaceMap = $appAutoload->getNamespaceMap();
			foreach ($namespaceMap as $namespace => $paths)
			{
				$namespacePath = str_replace('\\', '/', $namespace);
				$length = strlen($namespacePath);
				if (substr($classPath, 0, $length) !== $namespacePath)
					continue;
				foreach ($paths as $path)
				{
					$file = $path . substr($classPath, $length) . '.php';
					if ($includeFile($file))
						return;
				}
			}
		
			// try the root src dir as fallback (for 'use' keyword or not registered/global namespace)
			$fallbackDirs = $appAutoload->getFallbackDirs();
			foreach ($fallbackDirs as $dir)
			{
				$file = $dir . $classPath . '.php';
				if ($includeFile($file))
					return;
			}
			if (static::DEBUG) // happens on production when we upload a new file with OPCache enabled
				notifyError("Class Autoload failed", "File does not exist: $file");
		});
		
		// files to always include (config, global functions,...)
		$includeFiles = require __DIR__ . '/mapFiles.php';
		foreach ($includeFiles as $fileIdentifier => $file)
			$appAutoload->requireFile($fileIdentifier, $file);
		
		static::$loader = $appAutoload;
		return static::$loader;
	}
	
	public function __construct() {
		$this->namespaceMap = require __DIR__ . '/map.php';
		// last resort for loading classes are the paths specified here (the root /src/ dir)
		// we have to merge all namespace paths into this, in case $class in the callback does not hold the namespace
		$this->fallbackDirs = array(__DIR__ . '/src/');
		foreach ($this->namespaceMap as $namespace => $paths)
			$this->fallbackDirs = array_merge($paths, $this->fallbackDirs); // try namespace paths first
	}
	
	public function getNamespaceMap() {
		return $this->namespaceMap;
	}
	
	public function getFallbackDirs() {
		return $this->fallbackDirs;
	}
	
	public function requireFile($fileIdentifier, $file) {
		// use check via globals instead of require_once because it's faster, no context change (see Laravel autoloader)
		if (empty($GLOBALS['__ekliptor_autoload_files'][$fileIdentifier])) {
			require $file;
			$GLOBALS['__ekliptor_autoload_files'][$fileIdentifier] = true;
		}
	}
}
AppAutoload::getAppAutoload();
?>