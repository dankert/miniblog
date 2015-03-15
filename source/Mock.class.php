<?php

class Mock
{
	public $debug = false;
	public $config = array();
	
	public function pull()
	{
		$entrys[] = array(
			'filenames'=> array( array('name'=>'Transalp','filename'=>'/home/dankert/Transalp1_2004_250.jpg') ),
			'keywords' => array('Alpen','Motorrad'),
			'timestamp' => mktime(14,30,25,6,9,1973), // 9.6.73 um 14:30:25
			'subject'  => 'Glorious Nation of Kazakhstan',
			'text'     => "In Kazakhstan, the favorite hobbies are disco dancing, archery, rape, and table tennis.\n\nWawaweewaa! Ooh lala! Oh well, king in the castle, king in the castle, I have a chair! Go do this, go do this, king in the castle."
		);
		
		if	( $this->debug )
			echo '<h4>Mock!</h4>';
		
		return $entrys;
	}
	
	
}

?>