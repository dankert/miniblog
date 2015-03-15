<?php

class Xing
{
	public $config;

	public function push()
	{
		require_once('network/xing/xingoauth.php');
		
		$connection = new XingOAuth($this->config['consumer_key'],$this->config['consumer_secret'],$this->config['oauth_token'],$this->config['oauth_token_secret']);
		
		/* If method is set change API call made. Test is called by default. */
		
		//$content = $connection->get(‘account/verify_credentials’);
		
		/* Some example calls */
		
		$praefix = $this->config['praefix_text'].' ';
		$suffix  = ' '.$this->shortUrl.' '.$this->config['suffix_text'];
		$text    = $praefix.substr($this->subject,0,140-strlen($praefix)-strlen($suffix)).$suffix;
		
		$result = $connection->post('statuses/update', array('status' => $text));
		
		if	( $this->debug )
		{
			echo '<pre>';
			print_r( $result );
			echo '</pre>';
		}
		
		
	}
}
?>