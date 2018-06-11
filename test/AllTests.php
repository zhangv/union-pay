<?php
use \PHPUnit\Framework\TestSuite;

class AllTests extends TestSuite {

	public static function suite() {
		$suite = new TestSuite();
		$tests = ['UnionPayTest','service'];
		foreach($tests as $t){
			$path = __DIR__ . '/'.$t;
			if(is_dir($path)){
				self::addDir($path,$suite);
			}else{
				self::addFile("{$path}.php",$suite);
			}
		}
		return $suite;
	}

	private static function addFile($path,&$suite){
		require_once($path);
		$clz = substr($path,(strrpos($path,'/')===false)?0:strrpos($path,'/')+1);
		$clz = str_replace('.php','',$clz);
		$suite->addTestSuite($clz );
	}

	private static function addDir($dir,&$suite){
		if ($dh = opendir($dir)) {
			while (($file = readdir($dh)) !== false) {
				if($file == '.' || $file == '..') continue;
				$path = $dir . '/' . $file;
				if(is_dir($path)){
					self::addDir($path,$suite);
				}else{
					self::addFile($path,$suite);
				}
			}
			closedir($dh);
		}
	}
}