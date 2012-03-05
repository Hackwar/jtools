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
	private $tag = null;
	private $dom = null;

	public function setType($type)
	{
		$types = array('component', 'file', 'language', 'library', 'module', 'package', 'plugin', 'template');
		if(in_array($type, $types)) {
			$this->type = $type;
		} else {
			throw new Exception('Manifest-Task called with an invalid type!');
			
		}
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
	
	public function setTag($tag)
	{
		$this->tag = $tag;
	}
	
	public function init()
	{
		//nothing to do
	}

	public function main()
	{
		$types = array('component', 'file', 'language', 'library', 'module', 'package', 'plugin', 'template');
		$this->checkAttributes();
		$this->log('Creating manifest file for '.$this->extname);
		$this->dom = new DOMDocument();
		$this->dom->encoding = 'utf-8';//set the document encoding
		$this->dom->xmlVersion = '1.0';//set xml version
		$this->dom->formatOutput = true;//Nicely formats output with indentation and extra space 

		//Create Root tag
		$root = $this->createRoot();
		
		//Create Metadata tags
		$root = $this->createMetadata($root);

		//Process the extension specific parts
		$root = call_user_func(array($this, 'build'.ucfirst($this->type)), $root);

		//Process media tag
		$root = $this->createMedia($root);
		
		//Adding a scriptfile if present for the supported extensions
		$root = $this->createScriptfile($root);
		
		//Create SQL install,uninstall and update tags
		$root = $this->createSQL($root);

		//Create language tag
		$root = $this->createLanguage($root);
		
		//Handle update servers
		$root = $this->createUpdatesites($root);
		
		//Save manifest.xml file
		$this->dom->appendChild($root);
		
		//For debugging
		echo $this->dom->saveXML();
		//For actual deployment
		//file_put_contents($this->buildfolder.'/manifest.xml', $this->dom->saveXML());
	}
	
	/**
	 * Create Root node of the manifest
	 */
	private function createRoot()
	{
		$root = $this->dom->createElement('extension');
		$root->setAttribute('type', $this->type);
		$root->setAttribute('method', 'upgrade');
		$root->setAttribute('version', $this->jversion);
		
		$clients = array('module', 'template', 'language');
		if(in_array($this->type, $clients)) {
			$root->setAttribute('client', $this->client);
		}
		
		if($this->type == 'plugin') {
			$root->setAttribute('group', $this->folder);
		}
		return $root;		
	}
	
	/**
	 * Create the Metadata tags
	 */
	private function createMetadata($root)
	{
		$name = 'name';
		if($this->type == 'package')
			$name = 'packagename';
		if($this->exttitle)
			$name = $this->dom->createElement($name, $this->exttitle);
		else
			$name = $this->dom->createElement($name, $this->extname);
		$root->appendChild($name);
			
		if($this->type == 'language') {
			$tag = $this->dom->createElement('tag', $this->tag);
			$root->appendChild($tag);
		}
		
		$author = $this->dom->createElement('author', $this->author);
		$creation = $this->dom->createElement('creationDate', date('F Y'));
		$copyright = $this->dom->createElement('copyright', $this->copyright);
		$license = $this->dom->createElement('license', $this->license);
		$authormail = $this->dom->createElement('authorEmail', $this->email);
		$authorurl = $this->dom->createElement('authorUrl', $this->website);
		$version = $this->dom->createElement('version', $this->version);
		$description = $this->dom->createElement('description', strtoupper($this->extname).'_EXTENSION_DESC');

		$root->appendChild($author);
		$root->appendChild($creation);
		$root->appendChild($copyright);
		$root->appendChild($license);
		$root->appendChild($authormail);
		$root->appendChild($authorurl);
		$root->appendChild($version);
		$root->appendChild($description);
		
		return $root;
	}

	/**
	 * This method generates the media tag 
	 */
	private function createMedia($root)
	{
		if(in_array($this->type, array('file', 'package')))
			return $root;

		//Handle media file section
		if(is_dir($this->buildfolder.'/media/')) {
			$mediafiles = $this->dom->createElement('media');
			$mediafiles->setAttribute('destination', $this->extname);
			$mediafiles = $this->filelist($this->buildfolder.'/media/', $mediafiles);
			$root->appendChild($mediafiles);
		}
		
		return $root;
	}

	/**
	 * This method generates a scriptfile tag
	 */
	private function createScriptfile($root)
	{
		$script = array('component', 'file', 'module', 'package', 'plugin');
		if(!in_array($this->type, $script))
			return $root;

		$path = $this->buildfolder;
		if($this->type == 'component') {
			$path .= '/admin';
		}
		if(is_file($path.'/'.$this->extname.'.script.php')) {
			$scripttag = $this->dom->createElement('scriptfile', $this->extname.'.script.php');
			$root->appendChild($scripttag);
		}
		
		return $root;
	}
	
	/**
	 * This method adds the necessary SQL tags
	 */
	private function createSQL($root)
	{
		if(!in_array($this->type, array('component', 'file', 'module', 'plugin')))
			return $root;
		
		$path = $this->buildfolder;
		if($this->type == 'component')
			$path .= '/admin';
			
		if(is_dir($path.'/sql')) {
			if(file_exists($path.'/sql/install.mysql.utf8.sql')) {
				$install = $this->dom->createElement('install');
				$sql = $this->dom->createElement('sql');
				$folder = $path.'/sql/';
				$dir = opendir($folder);
				while(false !== ($entry = readdir($dir))) {
					if(is_file($folder.$entry) && substr($entry, 0, 7) == 'install') {
						$data = explode('.', $entry);
						$e = $this->dom->createElement('file', 'sql/'.$entry);
						$e->setAttribute('charset', 'utf8');
						$e->setAttribute('folder', 'sql');
						$e->setAttribute('driver', $data[1]);
						$sql->appendChild($e);
					}
				}
				$install->appenChild($sql);
				$root->appendChild($install);
			}
			
			if(file_exists($path.'/sql/uninstall.mysql.utf8.sql')) {
				$uninstall = $this->dom->createElement('uninstall');
				$sql = $this->dom->createElement('sql');
				$folder = $path.'/sql/';
				$dir = opendir($folder);
				while(false !== ($entry = readdir($dir))) {
					if(is_file($folder.$entry) && substr($entry, 0, 9) == 'uninstall') {
						$data = explode('.', $entry);
						$e = $this->dom->createElement('file', 'sql/'.$entry);
						$e->setAttribute('charset', 'utf8');
						$e->setAttribute('folder', 'sql');
						$e->setAttribute('driver', $data[1]);
						$sql->appendChild($e);
					}
				}
				$uninstall->appendChild($sql);
				$root->appendChild($uninstall);
			}
			
			if(is_dir($path.'/sql/updates')) {
				$update = $this->dom->createElement('update');
				$schemas = $this->dom->createElement('schemas');
				$folder = $path.'/sql/updates/';
				$dir = opendir($folder);
				while(false !== ($entry = readdir($dir))) {
					if(is_dir($folder.$entry)) {
						$e = $this->dom->createElement('schemapath', 'sql/updates/'.$entry);
						$e->setAttribute('type', $entry);
						$schemas->appendChild($e);
					}
				}
				$update->appendChild($schemas);
				$root->appendChild($update);
			}
		}
		
		return $root;
	}
	
	/**
	 * This method handles the updatesites tags
	 */
	private function createUpdatesites($root)
	{
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
		
		return $root;
	}
	
	/**
	 * This method handles the language tags
	 */
	private function createLanguage($root, $add = '')
	{
		if($add == '' && $this->type == 'component')
			$add = '/front';

		if(is_dir($this->buildfolder.'/language'.$add)) {
			$lang = $this->dom->createElement('languages');
			$folder = $this->buildfolder.'/language'.$add.'/';
			$dir = opendir($folder);
			while(false !== ($entry = readdir($dir))) {
				if($entry == '.' || $entry == '..')
					continue;
				if(is_dir($folder.$entry)) {
					$folder2 = $this->buildfolder.'/language'.$add.'/'.$entry.'/';
					$dir2 = opendir($folder2);
					while(false !== ($entry2 = readdir($dir2))) {
						if(is_file($folder2.$entry2) && $entry2 != 'index.html') {
							$e = $this->dom->createElement('language', 'language'.$add.'/'.$entry.'/'.$entry2);
							$e->setAttribute('tag', $entry);
							$lang->appendChild($e);
						}
					}
				}
			}
			$root->appendChild($lang);
		}
		return $root;
	}
	
	/**
	 * This method generates the component specific tags
	 */
	private function buildComponent($root)
	{
		//Handle frontend file section
		if(is_dir($this->buildfolder.'/front/')) {
			$frontfiles = $this->dom->createElement('files');
			$frontfiles->setAttribute('folder', 'front');
			$frontfiles = $this->filelist($this->buildfolder.'/front/', $frontfiles);
			$root->appendChild($frontfiles);
		}
		
		//Handle admin area
		if(is_dir($this->buildfolder.'/admin/')) {
			$admin = $this->dom->createElement('administration');
			
			//Handle admin files
			$adminfiles = $this->dom->createElement('files');
			$adminfiles->setAttribute('folder', 'admin');
			$adminfiles = $this->filelist($this->buildfolder.'/admin/', $adminfiles);
			$admin->appendChild($adminfiles);
			
			$admin = $this->createLanguage($admin, '/admin');
			
			$menu = $this->dom->createElement('menu', $this->extname);
			
			$admin->appendChild($menu);
			
			$root->appendChild($admin);
		}
		
		return $root;
	}

	private function buildFile($root)
	{
		$languages = array('component', 'language', 'library', 'module', 'plugin', 'template');
		if(in_array($this->type, $languages)) 
		{
			//Handle media file section
			if(is_dir($this->buildfolder.'/language/')) {
				$mediafiles = $this->dom->createElement('media');
				$mediafiles->setAttribute('destination', $this->extname);
				$mediafiles = $this->filelist($this->buildfolder.'/media/', $mediafiles);
				$root->appendChild($mediafiles);
			}
		}
		
		return $root;
	}
	
	private function buildLanguage($root)
	{
		if(in_array($this->client, array('both', 'site')))
		{
			$site = $this->dom->createElement('site');
			$sitefiles = $this->dom->createElement('files');
			$sitefiles->setAttribute('folder', 'site');
			$sitefiles = $this->filelist($this->buildfolder.'/site/', $sitefiles);
			$site->appendChild($sitefiles);
			$root->appendChild($site);
		}
		if(in_array($this->client, array('both', 'administrator')))
		{
			$admin = $this->dom->createElement('administration');
			$adminfiles = $this->dom->createElement('files');
			$adminfiles->setAttribute('folder', 'admin');
			$adminfiles = $this->filelist($this->buildfolder.'/admin/', $adminfiles);
			$admin->appendChild($adminfiles);
			$root->appendChild($admin);			
		}
		
		return $root;
	}
	
	private function buildLibrary($root)
	{
			$languages = array('component', 'language', 'library', 'module', 'plugin', 'template');
		if(in_array($this->type, $languages)) 
		{
			//Handle media file section
			if(is_dir($this->buildfolder.'/language/')) {
				$mediafiles = $this->dom->createElement('media');
				$mediafiles->setAttribute('destination', $this->extname);
				$mediafiles = $this->filelist($this->buildfolder.'/media/', $mediafiles);
				$root->appendChild($mediafiles);
			}
		}
		
		return $root;
	}
	
	private function buildModule($root)
	{
			$languages = array('component', 'language', 'library', 'module', 'plugin', 'template');
		if(in_array($this->type, $languages)) 
		{
			//Handle media file section
			if(is_dir($this->buildfolder.'/language/')) {
				$mediafiles = $this->dom->createElement('media');
				$mediafiles->setAttribute('destination', $this->extname);
				$mediafiles = $this->filelist($this->buildfolder.'/media/', $mediafiles);
				$root->appendChild($mediafiles);
			}
		}
		
		return $root;
	}
	
	private function buildPackage($root)
	{
			$languages = array('component', 'language', 'library', 'module', 'plugin', 'template');
		if(in_array($this->type, $languages)) 
		{
			//Handle media file section
			if(is_dir($this->buildfolder.'/language/')) {
				$mediafiles = $this->dom->createElement('media');
				$mediafiles->setAttribute('destination', $this->extname);
				$mediafiles = $this->filelist($this->buildfolder.'/media/', $mediafiles);
				$root->appendChild($mediafiles);
			}
		}
		
		return $root;
	}
	
	private function buildPlugin($root)
	{
			$languages = array('component', 'language', 'library', 'module', 'plugin', 'template');
		if(in_array($this->type, $languages)) 
		{
			//Handle media file section
			if(is_dir($this->buildfolder.'/language/')) {
				$mediafiles = $this->dom->createElement('media');
				$mediafiles->setAttribute('destination', $this->extname);
				$mediafiles = $this->filelist($this->buildfolder.'/media/', $mediafiles);
				$root->appendChild($mediafiles);
			}
		}
		
		return $root;		
	}
	
	private function buildTemplate($root)
	{
			$languages = array('component', 'language', 'library', 'module', 'plugin', 'template');
		if(in_array($this->type, $languages)) 
		{
			//Handle media file section
			if(is_dir($this->buildfolder.'/language/')) {
				$mediafiles = $this->dom->createElement('media');
				$mediafiles->setAttribute('destination', $this->extname);
				$mediafiles = $this->filelist($this->buildfolder.'/media/', $mediafiles);
				$root->appendChild($mediafiles);
			}
		}
		
		return $root;
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
		
		$types = array('module', 'template', 'language');
		$clients = array('site', 'administrator', 'both');
		if (in_array($this->type, $types) && (!isset($this->client) || !in_array($this->client, $clients))) {
			throw new BuildException("Missing attribute 'client' or client not valid");
		}
	}
}
