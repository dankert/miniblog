<?php

class File
{
	public $debug = false;
	public $config = array();
	
	private $text;
	private $html;
	private $filenames;
	
	public function pull()
	{
		$entrys = array();

		$dh = opendir('./blogs');
		
		while (($file = readdir($dh)) !== false)
		{
			$file = './blogs/'.$file;
			if	( is_dir($file) && is_file($file.'/OK') && !is_file($file.'/PROCESSED'))
			{
				$filenames = array();
				
				if	( file_exists($file.'/image') )
				{
					$imagename = file_get_contents($file.'/image');
					$tmpfilename = tempnam('/tmp','blog-').$imagename;
					copy( $file.'/'.$imagename,$tmpfilename);
					$filenames[] = array('name'=>$imagename,'filename'=>$tmpfilename);
				}
				
				$entrys[] = array(
					'filenames'=> $filenames,
					'keywords' => array(),
					'timestamp' => filectime($file),
					'subject'  => file_get_contents($file.'/subject'),
					'text'     => file_get_contents($file.'/text'   )
				);
				
				// als "verarbeitet" markieren...
				$processed = fopen($file.'/PROCESSED','w');
				fwrite($processed,'');
				fclose($processed);
				
				// und verschieben...
				if	( !is_dir('./blogs/archive'))
				{
					mkdir('./blogs/archive');
				}
				rename($file,'blogs/archive/'.basename($file));
				
			}
		}

		
		return $entrys;
	}
	
	
	
	private function getpart($mbox,$mid,$p,$partno)
	{
		// $partno = '1', '2', '2.1', '2.1.3', etc for multipart, 0 if simple
		
		// DECODE DATA
		$data = ($partno) ?
		imap_fetchbody($mbox,$mid,$partno):  // multipart
		imap_body($mbox,$mid);  // simple
	
		// Any part may be encoded, even plain text messages, so check everything.
		if	($p->encoding==4)
			$data = quoted_printable_decode($data);
		elseif ($p->encoding==3)
		$data = base64_decode($data);
	
		// PARAMETERS
		// get all parameters, like charset, filenames of attachments, etc.
		$params = array();
		if ($p->parameters)
			foreach ($p->parameters as $x)
			$params[strtolower($x->attribute)] = $x->value;
		if ($p->dparameters)
			foreach ($p->dparameters as $x)
			$params[strtolower($x->attribute)] = $x->value;
	
		// ATTACHMENT
		// Any part with a filename is an attachment,
		// so an attached text file (type 0) is not mistaken as the message.
		if ($params['filename'] || $params['name']) {
			// filename may be given as 'Filename' or 'Name' or both
			$filename = ($params['filename'])? $params['filename'] : $params['name'];
			// filename may be encoded, so see imap_mime_header_decode()
			$fname = tempnam(null,'blog-file-');
			$file = fopen($fname,'w');
			fwrite($file,$data);
			fclose($file);
			chmod($fname,0644);
	
			$this->filenames[] = array('filename'=>$fname,'name'=>$filename);
		}
	
		// TEXT
		if ($p->type==0 && $data) {
			// Messages may be split in different parts because of inline attachments,
			// so append parts together with blank row.
			if (strtolower($p->subtype)=='plain')
				$this->text.= trim($data) ."\n\n";
			else
				$this->html.= $data ."<br><br>";
			$charset = $params['charset'];  // assume all parts are same charset
		}
	
		// EMBEDDED MESSAGE
		// Many bounce notifications embed the original message as type 2,
		// but AOL uses type 1 (multipart), which is not handled here.
		// There are no PHP functions to parse embedded messages,
		// so this just appends the raw source to the main message.
		elseif ($p->type==2 && $data) {
			$this->text.= $data."\n\n";
		}
	
		// SUBPART RECURSION
		if ($p->parts) {
			foreach ($p->parts as $partno0=>$p2)
				$this->getpart($mbox,$mid,$p2,$partno.'.'.($partno0+1));  // 1.2, 1.2.1, etc.
		}
	}
	
}

?>