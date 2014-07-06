<?php

class Mailbox
{
	public $debug = false;
	public $config = array();
	
	private $text;
	private $html;
	private $filenames;
	
	public function pull()
	{

		$entrys = array();
		
		/* connect to gmail */
		$hostname = $this->config['host'];
		$username = $this->config['username'];
		$password = $this->config['password'];
		
		/* try to connect */
		$inbox = imap_open($hostname,$username,$password) or die('Cannot connect to IMAP server: ' . imap_last_error());
		
		/* grab emails */
		$emails = imap_search($inbox,'ALL UNDELETED UNSEEN');
		
		print_r($emails);
		
		/* if emails are returned, cycle through each... */
		if($emails) {
		
			/* put the newest emails on top */
			rsort($emails);
		
			/* for every email... */
			foreach($emails as $email_number) {
		
				/* get information specific to this email */
				// 		$overview  = imap_fetch_overview($inbox,$email_number,0);
				$headers = imap_headerinfo($inbox,$email_number);
				$structure = imap_fetchstructure($inbox,$email_number);
				// 		$message = imap_fetchbody($inbox,$email_number);
		
				// 		echo "\nOverview:";
				//  		print_r($overview);
				if	( $this->debug ) { echo '<pre>'; print_r($headers); echo '</pre>'; }
		
				// Initalize
				$this->filenames = array();
				$this->text      = '';
				$this->html      = '';
				$subject   = $headers->subject;
		
				$s = imap_fetchstructure($inbox,$email_number);
		
				if (!$s->parts)  // simple
					$this->getpart($inbox,$email_number,$s,0);  // pass 0 as part-number
				else {  // multipart: cycle through each part
					foreach ($s->parts as $partno0=>$p)
						$this->getpart($inbox,$email_number,$p,$partno0+1);
				}
		
				// 		print_r($message);
				/* output the email header information */
				// 		$output.= '<div class="toggler '.($overview[0]->seen ? 'read' : 'unread').'">';
				// 		$output.= '<span class="subject">'.$overview[0]->subject.'</span> ';
				// 		$output.= '<span class="from">'.$overview[0]->from.'</span>';
				// 		$output.= '<span class="date">on '.$overview[0]->date.'</span>';
				// 		$output.= '</div>';
		
				/* output the email body */
				// 		$output.= '<div class="body">'.$message.'</div>';
		
				if	( $this->debug ) echo "\n\nBetreff: ".$subject;
				if	( $this->debug ) echo "\n\nText: ";
				if	( $this->debug ) print_r($this->text);
				if	( $this->debug ) echo "\n\nAnlagen: ";
				if	( $this->debug ) print_r($this->filenames);
		
				$entrys[] = array(
					'filenames'=> $this->filenames,
					'keywords' => array(),
					'timestamp' => strtotime($headers->date),
					'subject'  => $subject,
					'text'     => $this->text
				);
		
				// Aufräumen:
				// - Mail als gelesen markieren und in das Archiv verschieben.
				if	( $this->config['dry'] )
					;
				else
				{
					imap_setflag_full($inbox,$email_number,'\\SEEN',0);
					
					if	(isset($this->config['archive_folder']))
					{
						imap_mail_move($inbox,$email_number,$this->config['archive_folder']) or die("IMAP: Move did not suceed: "+imap_last_error() );
				
						imap_expunge($inbox);
					}
				}
			}
		}
		
		/* close the connection */
		imap_close($inbox);
		
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