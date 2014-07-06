<?php


if ($dh = opendir('./profiles'))
{
	while (($file = readdir($dh)) !== false)
	{
		if	( substr($file,-4) == '.ini' )
		{
			$config = parse_ini_file('./profiles/'.$file,true);
			
			if	( !$config['enabled'] )
					continue;
			
			$blogger = new Blogger();
			if	( $config['debug'] ) echo "<h2>Profile: $file</h2>";
			
			$blogger->config = $config;
			$blogger->debug = $config['debug'];
			
			$blogger->pull();
			$blogger->pushToCMS();
			$blogger->pushToNetwork();
		}
	}
	closedir($dh);
}



class Blogger {

	public $debug = true;
	public $config;
	
	private $blogs = array(); 
	
	public function pull()
	{
		if ($dh = opendir('./source'))
		{
			while (($file = readdir($dh)) !== false)
			{
				if	( substr($file,-4) == '.php' )
				{
					require_once('./source/'.$file);
					$className = substr($file,0,strlen($file)-10);

					if	( $this->debug )
						echo "<h3>Source-Plugin: ".$className.'</h3>';

					if	( isset($this->config[strtolower($className)] ))
					{
						$source = new $className;
		
						$source->config = $this->config[strtolower($className)];
						$source->debug    = $this->debug;

						foreach( $source->pull() as $blog )
						{
							$blog['filename'] = $this->createPageFilename($blog['subject']);
							$d = isset($blog['timestamp'])?$blog['timestamp']:time();
		
							switch( $this->config['urlschema'   ])
							{
								case 'flat':
									$blog['path'] = array();
									break;
								case 'yearly':
									$blog['path'] = array( date('Y',$d) );
									break;
								case 'monthly':
									$blog['path'] = array( date('Y',$d),date('m',$d) );
									break;
								case 'daily':
								default:
									$blog['path'] = array( date('Y',$d),date('m',$d),date('d',$d) );
									break;
							}
							$blog['url'     ] = 'http://'.$this->config['hostname'].'/'.implode('/',$blog['path']).'/'.$blog['filename'];
							$blog['shortUrl'] = $this->createShortUrl($blog['url']);
										
							$this->blogs[] = $blog;
						}
					}
				}
			}
			closedir($dh);
			
			if	( $this->debug )
			{
				echo "<h3>Blogs</h3>";
				echo '<pre>';
				print_r($this->blogs);
				echo '</pre>';
			}
		}
	
	}
	
	public function pushToCMS()
	{
		if ($dh = opendir('./cms'))
		{
			while (($file = readdir($dh)) !== false)
			{
				if	( substr($file,-4) == '.php' )
				{
					require_once('./cms/'.$file);
					$className = substr($file,0,strlen($file)-10);
						
					if	( $this->debug )
						echo "<h3>CMS-Plugin: ".$className.'</h3>';
						
					$cms = new $className;
		
					if	( isset($this->config[strtolower($className)] ))
					{
						$cms->config = $this->config[strtolower($className)];
						
						foreach( $this->blogs as $blog )
						{
							
							$cms->url       = $blog['url'];
							$cms->shortUrl  = $blog['shortUrl'];
							$cms->text      = $blog['text'];
							$cms->subject   = $blog['subject'];
							$cms->filenames = $blog['filenames'];
							$cms->filename  = $blog['filename'];
							$cms->path      = $blog['path'];
							$cms->keywords  = $blog['keywords'];
							$cms->debug     = $this->debug;
							$cms->push();
						}
					}
				}
			}
			closedir($dh);
		}
		
	}
	
	
	public function pushToNetwork()
	{
		if ($dh = opendir('./network'))
		{
			while (($file = readdir($dh)) !== false)
			{
				if	( substr($file,-4) == '.php' )
				{
					require_once('./network/'.$file);
					$className = substr($file,0,strlen($file)-10);
	
					if	( $this->debug )
						echo "Network-Plugin: "+$className;
	
					if	( isset($this->config[strtolower($className)] ))
					{
						$network = new $className;
		
						$network->config = $this->config[strtolower($className)];
						
						foreach( $this->blogs as $blog )
						{
							$network->url      = $blog['url'];
							$network->shortUrl = $blog['shortUrl'];
							$network->text     = $blog['text'];
							$network->subject  = $blog['subject'];
							$network->debug    = $this->debug;
							$network->push();
						}
					}
				}
			}
			closedir($dh);
		}
	
	}
	
	
	private function createShortUrl( $url )
	{
		$su = file_get_contents('http://l.jdhh.de/?url='.$url);
		$doc = json_decode($su,true);
		
		return 'http://l.jdhh.de/'.$doc['short_url'];
	}
	
	
	private function createPageFilename( $title )
	{
		$title = strtolower(trim($title));
		$title = strtr($title, array( 
			' '  =>  '-',
			'ä'  =>  'ae',
			'Ä'  =>  'ae',
			'ö'  =>  'oe',
			'Ö'  =>  'oe',
			'ü'  =>  'ue',
			'Ü'  =>  'ue',
			'ß'  =>  'ss',
			'_'  =>  '-' ) ); 

		$gueltig = 'abcdefghijklmnopqrstuvwxyz0123456789-';
		$tmp     = strtr($title, $gueltig, str_repeat('#', strlen($gueltig)));
		$title   = strtr($title, $tmp, str_repeat('-', strlen($tmp)));
		
		return $title;
	}
}

?>