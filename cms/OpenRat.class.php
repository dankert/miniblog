<?php


class OpenRat {

	public $text = '';
	public $filenames = array();
	public $filename;
	public $subject;
	public $keywords = array();
	public $shortUrl;
	public $path;
	public $debug = true;
	public $config;
	
	private $client;
	
	private function request( $method,$parameter )
	{
		 
		$this->client->parameter = $parameter;
		$this->client->method    = $method;
		$this->client->request();
		
		if	( $this->client->status != '200' || $this->debug)
		{
			echo '<span style="background-color:'.($this->client->status=='200'?'green':'red').'">HTTP-Status '.$this->client->status.'</span>';
		}
		
		if	( $this->debug )
		{
			echo "<h4>".$parameter['action'].'/'.$parameter['subaction'].'</h4>';
			?><pre><?php print_r($this->client); ?></pre><?php
		}
				
		$response = json_decode($this->client->response,true);
		
		if	( $response == null )
		{
			echo '<span style="background-color:red">Kein JSON: <pre>'.htmlentities($this->client->response).'</pre></span>';
			exit;
		}
			
		return $response;
	}
	
	
	public function push()
	{
		require_once('./cms/openrat/OpenratClient.php');
		$this->client = new OpenratClient();

		/*
		 * 
		if (!isset($_SERVER['PHP_AUTH_USER'])) {
			header('WWW-Authenticate: Basic realm="Blog Upload"');
			header('HTTP/1.0 401 Unauthorized');
			echo 'sorry, authentication required to blog something';
			exit;
		}
		 */

		$username = $this->config['user'];
		$password = $this->config['password'];
	
		$this->client->host   = $this->config['host'];
		$this->client->port   = $this->config['port'];
		$this->client->path   = $this->config['path'];
		$this->client->type ="application/json";
		
		
		$response = $this->request( 'GET',
			array('action'   =>'login',
			      'subaction'=>'login') );
			
		$token = $response['session']['token'];
		$this->client->cookie =$response['session']['name'].'='.$response['session']['id'];
		
		
		$response = $this->request( 'POST', array(
			'action'        => 'login',
			'subaction'     => 'login',
			'token'         => $token,
			'dbid'          => $this->config['database'],
			'login_name'    => $username,
			'login_password'=> $password ) );
	
		$this->client->cookie =$response['session']['name'].'='.$response['session']['id'];
		$token = $response['session']['token'];
	
		
		// PRojekt auswählen
		$response = $this->request( 'POST', array(
				'action'        => 'start',
				'subaction'     => 'projectmenu',
				'token'         => $token,
				'id'            => $this->config['projectid']) );
		
		
		// Ordner laden.
		$rootfolderid = $this->config['rootfolderid'];
		$folderid = $rootfolderid;
		
		foreach( $this->path as $foldername )
		{
			$response = $this->request( 'GET', array
			(
				'action'        => 'folder',
				'subaction'     => 'edit',
				'id'            => $folderid
			) );
	
			// Prüfen, ob der nächste Unterordner bereits existiert.
			$nextfolderid = null;
			foreach( $response['output']['object'] as $objectid=>$object )
			{
				if	( $object['name'] == $foldername )
				{
					$nextfolderid = $objectid;
					break;
				} 
			}
			if	( empty($nextfolderid) )
			{
				// Der nächste Unterordner existiert noch nicht, also müssen wir diesen anlegen.
				$responseCreate = $this->request( 'POST', array
				(
					'action'        => 'folder',
					'subaction'     => 'createfolder',
					'id'            => $folderid,
					'token'         => $token,
					'name'          => $foldername
				) );
				$nextfolderid = $responseCreate['output']['objectid'];
			}
			$folderid = $nextfolderid;
		}

		// Ein Unterordner pro Blogeintrag.
		$responseCreate = $this->request( 'POST', array
				(
						'action'        => 'folder',
						'subaction'     => 'createfolder',
						'id'            => $folderid,
						'token'         => $token,
						'name'          => $this->filename
				) );
		$folderid = $responseCreate['output']['objectid'];
		
		// Seite anlegen.
		$response = $this->request( 'POST', array
		(
			'action'        => 'folder',
			'subaction'     => 'createpage',
			'id'            => $folderid,
			'templateid'    => $this->config['templateid'],
			'token'         => $token,
			'name'          => $this->subject,
			'filename'      => 'index'
		) );
		$pageobjectid = $response['output']['objectid'];
		
		/*
		 * 
		// Ggf. Datei anlegen.
		$response = $this->request( 'POST', array
		(
			'action'        => 'folder',
			'subaction'     => 'createfile',
			'token'         => $token,
			'name'          => $title,
			'filename'      => $title
		) );
		$pageobjectid = $response['objectid'];
		 */
		
		// Text speichern anlegen.
		$response = $this->request( 'POST', array
		(
			'action'        => 'pageelement',
			'subaction'     => 'edit',
			'id'            => $pageobjectid,
			'elementid'     => $this->config['elementid_text'],
			'token'         => $token,
			'release'       => '1',
			'text'          => $this->text
		) );

		foreach( $this->filenames as $file ) 
		{
			// Datei anlegen.
			$response = $this->request( 'POST', array
					(
							'action'        => 'folder',
							'subaction'     => 'createfile',
							'id'            => $folderid,
							'token'         => $token,
							'name'          => $file['name'],
							'filename'      => basename($file['filename'])
					) );
			$fileobjectid = $response['output']['objectid'];
			
			// Datei-Inhalt hochladen.
			$response = $this->request( 'POST', array
					(
							'action'        => 'file',
							'subaction'     => 'value',
							'id'            => $fileobjectid,
							'token'         => $token,
							'value'         => file_get_contents($file['filename'])
					) );
				
		}
		
		// Keywords
		foreach( $this->keywords as $keyword )
		{
			$response = $this->request( 'GET', array
					(
							'action'        => 'folder',
							'subaction'     => 'edit',
							'id'            => $this->config['keywords_folderid']
					) );
			
			// Prüfen, ob das Keyword schon existiert
			$keyword_folderid = null;
			foreach( $response['output']['object'] as $objectid=>$object )
			{
				if	( $object['name'] == $keyword )
				{
					$keyword_folderid = $objectid;
					break;
				}
			}
			if	( empty($keyword_folderid) )
			{
				// Der Keyword-Ordner existiert noch nicht, also müssen wir diesen anlegen.
				$responseCreate = $this->request( 'POST', array
						(
								'action'        => 'folder',
								'subaction'     => 'createfolder',
								'id'            => $this->config['keywords_folderid'],
								'token'         => $token,
								'name'          => $keyword
						) );
				$keyword_folderid = $responseCreate['output']['objectid'];
			}
			
			$responseCreate = $this->request( 'POST', array
					(
							'action'        => 'folder',
							'subaction'     => 'createlink',
							'id'            => $keyword_folderid,
							'token'         => $token,
							'name'          => $this->subject,
							'filename'      => $this->filename,
							'targetobjectid'=> $pageobjectid
					) );

		}
	}

	
}

?>