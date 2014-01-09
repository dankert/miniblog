<?php

$config = parse_ini_file('./config.ini',true);

require('./client/OpenratClient.php');
$client = new OpenratClient();

if (!isset($_SERVER['PHP_AUTH_USER'])) {
	header('WWW-Authenticate: Basic realm="Blog Upload"');
	header('HTTP/1.0 401 Unauthorized');
	echo 'sorry, authentication required to blog something';
	exit;
}

$username = $_SERVER['PHP_AUTH_USER'];
$password = $_SERVER['PHP_AUTH_PW'];

function request( $client,$method,$parameter )
{
	echo "<hr />";
	$client->parameter = $parameter;
	$client->method = $method;
	$client->request();
	$response = json_decode($client->response,true);
	?><pre><?php print_r($client); ?></pre><?php 
	if	( $client->status != '200')
	{
		echo '<span style="background-color:'.($client->status=='200'?'green':'red').'">HTTP-Status '.$client->status.'</span>';
	}
	return $response;
}
	

?>

<html>
<head>
<title>Blog Form</title>
</head>
<body>

<?php if ( !empty($_POST['text']) ) {
	
	?><h1>Result</h1><?php
	
	$client->host   = $config['server']['host'];
	$client->port   = $config['server']['port'];
	$client->path   = $config['server']['path'];
	$client->type ="application/json";
	
	
	$response = request( $client,'GET',
		array('action'   =>'login',
		      'subaction'=>'login') );
		
	$token = $response['session']['token'];
	$client->cookie =$response['session']['name'].'='.$response['session']['id'];
	
	
	$response = request( $client,'POST', array(
		'action'        => 'login',
		'subaction'     => 'login',
		'token'         => $token,
		'dbid'          => $config['server']['database'],
		'login_name'    => $username,
		'login_password'=> $password ) );

	$client->cookie =$response['session']['name'].'='.$response['session']['id'];
	$token = $response['session']['token'];
	
	
	// Ordner laden.
	$rootfolderid = $config['project']['rootfolderid'];
	$urlschema    = $config['project']['urlschema'   ];
	
	$folderid = $rootfolderid;
	
	switch( $urlschema )
	{
		case 'flat':
			$foldernames = array();
			break;
		case 'yearly':
			$foldernames = array( date('Y') );
			break;
		case 'monthly':
			$foldernames = array( date('Y'),date('m') );
			break;
		case 'daily':
		default:
			$foldernames = array( date('Y'),date('m'),date('d') );
			break;
	}
	
	foreach( $foldernames as $foldername )
	{
		$response = request( $client,'GET', array
		(
			'action'        => 'folder',
			'subaction'     => 'show',
			'id'            => $folderid
		) );
		
		$nextfolderid = null;
		foreach( $response['object'] as $objectid=>$object )
		{
			if	( $object['name'] == $foldername )
			{
				$nextfolderid = $objectid;
				break;
			} 
		}
		if	( empty($nextfolderid) )
		{
			$response = request( $client,'POST', array
			(
				'action'        => 'folder',
				'subaction'     => 'createfolder',
				'token'         => $token,
				'name'          => $foldername
			) );
			$nextfolderid = $response['objectid'];
		}
		$folderid = $nextfolderid;
	}
	
	// Seite anlegen.
	$response = request( $client,'POST', array
	(
		'action'        => 'folder',
		'subaction'     => 'createpage',
		'id'            => $folderid,
		'templateid'    => $config['project']['templateid'],
		'token'         => $token,
		'name'          => $_POST['title'],
		'filename'      => $_POST['title']
	) );
	$pageobjectid = $response['objectid'];
	
	/*
	 * 
	// Ggf. Datei anlegen.
	$response = request( $client,'POST', array
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
	$response = request( $client,'POST', array
	(
		'action'        => 'pageelement',
		'subaction'     => 'edit',
		'id'            => $pageobjectid,
		'elementid'     => $config['project']['elementid_text'],
		'token'         => $token,
		'text'          => $_POST['text']
	) );
	
	?>
	
	
	<?php } ?>

<h1>Blog</h1>
<form action="<?php echo $_SERVER['SCRIPT_NAME'] ?>" method="post">

<div class="line">
<div class="label">Benutzer</div>
<div class="value"><input type="text" name="username" readonly="readonly"
	value="<?php echo $username ?>" /></div>
</div>
<div class="line">
<div class="label">Titel</div>
<div class="value"><input type="text" name="title" value="" /></div>
</div>
<div class="line">
<div class="label">Text</div>
<div class="value"><textarea name="text"></textarea></div>
</div>
</div>
<div class="line">
<div class="label">File</div>
<div class="value"><input type="file" name="image"></textarea></div>
</div>

<div class="line">
<div class="label">Options</div>
<div class="value"><select name="option"><option name="public" >test</option></select></div>
</div>

<br>

<input type="submit"></form>
<hr>

</body>
</html>
