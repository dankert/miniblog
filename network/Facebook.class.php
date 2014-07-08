<?php

class Facebook
{
	public $config;
	public $debug;

	public $url;
	public $shortUrl;
	public $text;
	public $subject;
	
	
	public function push()
	{
		require_once('network/facebook/facebook.php');
	
		$facebook = new FacebookApi( array('appId'=>$this->config['app_id'],'secret'=>$this->config['app_secret']) );
		$facebook->api_client->session_key = $this->config['session_key'];
		
		$text = strlen($this->text)>100 ? substr($this->text,0,100).' ...' : $this->text;
		
		$attachment = array('message' => $this->config['praefix_text'].' '.$this->subject,
				'name' => $this->subject,
				'link' => $this->url,
				'description' => $text
		);
		$attachment['access_token'] = $this->config['access_token'];
		 
		if(!($sendMessage = $facebook->api('/jan.dunkerbeck/feed/','post',$attachment))){
			$errors= error_get_last();
			echo "Facebook publish error: ".$errors['type'];
			echo "<br />\n".$errors['message'];
		}
		
	}
}
?>