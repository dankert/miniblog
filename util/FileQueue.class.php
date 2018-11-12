<?php

require('Queue.class.php');

define('QUEUE_TYPE_MAIL',1);
define('QUEUE_TYPE_FILE_JSON',2);
define('QUEUE_TYPE_FILE_SERIALIZE',3);

class FileQueue extends Queue
{
	public $type;
	public $directory;
	
	public function Queue($directory)
	{
		if (!is_dir($directory))
			throw new Exception('Queue-Directory does not exist: '.$directory);
		
		$this->directory = $directory;
	}
	
	function push( $entry )
	{
		if (!is_dir($this->directory))
			throw new Exception('Queue-Directory does not exist: '.$this->directory);
		
		$entryDirName = $this->directory.'/'.time().'-'.rand(10000000,99999999);
		mkdir($entryDirName);


		mkdir($entryDirName.'/files');
		$files = array();
		foreach( $entry->files as $file)
		{
			if	( !is_file($file) )
				throw new Exception('file does not exist: '.$file);
			$files[] = $file;
			copy($file,$entryDirName.'/files/'.basename($file));
		}
		
		$value = array('value'=>$entry->value,'files'=>$files,'time'=>time(),'user'=>get_current_user());
		$file = fopen($entryDirName.'/value','w');
		fwrite($file,json_encode($value));
		fclose($file);
	}
	
	
	public function pull()
	{
		if (!is_dir($this->directory))
			throw new Exception('Queue-Directory does not exist: '.$this->directory);
		
		// Öffnen eines bekannten Verzeichnisses und danach seinen Inhalt einlesen
		if ($dh = opendir($this->directory)) {
			while (($file = readdir($dh)) !== false && is_dir($file) && substr($file,0,1) != '.')
			{
				$files[] = $file;
			}
			closedir($dh);
			
			if	( empty($files))
				return null;
			
			$entryName = $files[0];
			rename($this->directory.'/'.$entryName,$this->directory.'/.pull-in-progress-'.$entryName);
			
			$entry = new QueueEntry();
			$value = json_decode(file_get_contents($this->directory.'/.pull-in-progress-'.$entryName.'/value'));
			$entry->value = $value['value'];
			$entry->files = $value['files'];
		}
	}
}
?>