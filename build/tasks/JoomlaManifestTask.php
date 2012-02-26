<?php

require_once "phing/Task.php";

class JoomlaManifestTask extends Task {

	private $type = null;
	private $extname = null;
	private $exttitle = null;
	private $folder = null;
	private $buildfolder = null;
	private $version = null;
	private $jversion = null;
	private $copyright = null;
	private $author = null;
	private $email = null;
	private $website = null;
	private $license = null;
	private $update = null;
	private $client = null;
	private $dom = null;

	public function setType($type)
	{
		$this->type = $type;
	}

	public function setExtName($extname)
	{
		$this->extname = $extname;
	}

	public function setExtTitle($exttitle)
	{
		$this->exttitle = $exttitle;
	}

	public function setFolder($folder)
	{
		$this->folder = $folder;
	}

	public function setBuildFolder($buildfolder)
	{
		$this->buildfolder = $buildfolder;
	}

	public function setVersion($version)
	{
		$this->version = $version;
	}

	public function setJVersion($jversion)
	{
		$this->jversion = $jversion;
	}

	public function setCopyright($data)
	{
		$this->copyright = $data;
	}

	public function setAuthor($data)
	{
		$this->author = $data;
	}

	public function setEmail($data)
	{
		$this->email = $data;
	}

	public function setWebsite($data)
	{
		$this->website = $data;
	}

	public function setLicense($data)
	{
		$this->license = $data;
	}

	public function setUpdate($data)
	{
		$this->update = $data;
	}

	public function setClient($client)
	{
		$this->client = $client;
	}
	
	public function init()
	{
		//nothing to do
	}

	public function main()
	{
		$this->checkAttributes();
		$this->log('Creating manifest file for '.$this->extname);
		$this->dom = new DOMDocument();
		$this->dom->encoding = 'utf-8';//set the document encoding
		$this->dom->xmlVersion = '1.0';//set xml version
		$this->dom->formatOutput = true;//Nicely formats output with indentation and extra space 

		$root = $this->dom->createElement('extension');
		$root->setAttribute('type', $this->type);
		$root->setAttribute('method', 'upgrade');
		$root->setAttribute('version', '1.5');
		
		if($this->type == 'module' || $this->type == 'template') {
			$root->setAttribute('client', $this->client);
		}
		
		if($this->type == 'plugin') {
			$root->setAttribute('group', $this->folder);
		}
		
		if($this->exttitle)
			$name = $this->dom->createElement('name', $this->exttitle);
		else
			$name = $this->dom->createElement('name', $this->extname);

		$author = $this->dom->createElement('author', $this->author);
		$creation = $this->dom->createElement('creationDate', date('F Y'));
		$copyright = $this->dom->createElement('copyright', $this->copyright);
		$license = $this->dom->createElement('license', $this->license);
		$authormail = $this->dom->createElement('authorEmail', $this->email);
		$authorurl = $this->dom->createElement('authorUrl', $this->website);
		$version = $this->dom->createElement('version', $this->version);
		$description = $this->dom->createElement('description', strtoupper($this->extname).'_EXTENSION_DESC');
		$root->appendChild($name);
		$root->appendChild($author);
		$root->appendChild($creation);
		$root->appendChild($copyright);
		$root->appendChild($license);
		$root->appendChild($authormail);
		$root->appendChild($authorurl);
		$root->appendChild($version);
		$root->appendChild($description);

		//Handle frontend file section
		if(is_dir($this->buildfolder.'/front/')) {
			$frontfiles = $this->dom->createElement('files');
			$frontfiles->setAttribute('folder', 'front');
			$frontfiles = $this->filelist($this->buildfolder.'/front/', $frontfiles);
			$root->appendChild($frontfiles);
		}
		
		//Handle media file section
		if(is_dir($this->buildfolder.'/media/')) {
			$mediafiles = $this->dom->createElement('media');
			$mediafiles->setAttribute('destination', $this->extname);
			$mediafiles = $this->filelist($this->buildfolder.'/media/', $mediafiles);
			$root->appendChild($mediafiles);
		}
		
		//Handle admin area
		if(is_dir($this->buildfolder.'/admin/')) {
			$admin = $this->dom->createElement('administration');
			
			//Handle admin files
			$adminfiles = $this->dom->createElement('files');
			$adminfiles->setAttribute('folder', 'admin');
			$adminfiles = $this->filelist($this->buildfolder.'/admin/', $adminfiles);
			$admin->appendChild($adminfiles);
			
			$root->appendChild($admin);
		}
		
		//Handle update servers
		$updateSites = explode(',', $this->update);
		if(count($updateSites) && strlen($updateSites[0])) {
			$updates = $this->dom->createElement('updateservers');
			$i = 1;
			foreach($updateSites as $updateSite) {
				$server = $this->dom->createElement('server', $updateSite);
				$server->setAttribute('type', 'extension');
				$server->setAttribute('priority', $i);
				$server->setAttribute('name', $this->extname);
				$updates->appendChild($server);
			}
			$root->appendChild($updates);
		}
		
		//Save manifest.xml file
		$this->dom->appendChild($root);
		
		//For debugging
		//echo $this->dom->saveXML();
		//For actual deployment
		file_put_contents($this->buildfolder.'/manifest.xml', $this->dom->saveXML());
	}
	
	private function checkAttributes()
	{
		if (!isset($this->type)) {
			throw new BuildException("Missing attribute 'type'");
		}
		
		if (!isset($this->extname)) {
			throw new BuildException("Missing attribute 'extname'");
		}
		
		if (!isset($this->buildfolder)) {
			throw new BuildException("Missing attribute 'buildfolder'");
		}
		
		if (!isset($this->version)) {
			throw new BuildException("Missing attribute 'version'");
		}
		
		if ($this->type == 'plugin' && !isset($this->folder)) {
			throw new BuildException("Missing attribute 'folder'");
		}
		
		if (($this->type == 'module' || $this->type == 'template') && !isset($this->client)) {
			throw new BuildException("Missing attribute 'client'");
		}
	}
	
	private function filelist($folder, $dom)
	{
		$dir = opendir($folder);
		while(false !== ($entry = readdir($dir))) {
			if(is_file($folder.$entry)) {
				$e = $this->dom->createElement('filename', $entry);
			} elseif(is_dir($folder.$entry) && $entry != '.' && $entry != '..') {
				$e = $this->dom->createElement('folder', $entry);
			}
			if(is_object($e))
				$dom->appendChild($e);
		}
		return $dom;
	}
}
