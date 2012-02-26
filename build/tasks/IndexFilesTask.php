<?php

require_once "phing/Task.php";

class IndexFilesTask extends Task {

	private $path = null;
	private $dir = null;
	private $counter = 0;

	public function setPath($path)
	{
		$this->path = $path;
	}

	public function init()
	{
		//nothing to do
	}

	public function main()
	{
		if (!isset($this->path)) {
			throw new BuildException("Missing attribute 'path'");
		}
		if(!is_dir($this->path)) {
			throw new BuildException("'path' attribute not a valid path");
		}

		$this->readdir($this->path.'/');
		$this->log('Added '.$this->counter.' index.html files to the project');
	}

	private function readdir($dir) {
		if(!file_exists($dir.'index.html')) {
			file_put_contents($dir.'index.html', '<html><body bgcolor="#FFFFFF"></body></html>');
			$this->counter++;
		}
		if($dh = opendir($dir)) {
			while (($file = readdir($dh)) !== false) {
				if(filetype($dir.$file) == 'dir' && !in_array($file, array('.','..'))) {
					$this->readdir($dir.$file.'/');
				}
			}
		} else {
			throw new BuildException("Could not open path ".$dir);
		}
	}
}
