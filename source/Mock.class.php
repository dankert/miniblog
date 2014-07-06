<?php

class Mock
{
	public $debug = false;
	public $config = array();
	
	public function pull()
	{
		$entrys[] = array(
			'filenames'=> array( array('name'=>'Motorrad','filename'=>'/tmp/DSC00281.JPG') ),
			'keywords' => array('Kazakhstan','Motorrad'),
			'timestamp' => time(),
			'subject'  => 'Glorious Nation of Kazakhstan',
			'text'     => "In Kazakhstan, the favorite hobbies are disco dancing, archery, rape, and table tennis.\n\nWawaweewaa! Ooh lala! Oh well, king in the castle, king in the castle, I have a chair! Go do this, go do this, king in the castle."
		);
		
		if	( $this->debug )
			echo '<h4>Mock!</h4>';
		
		return $entrys;
	}
	
	
}

?>