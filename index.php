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
	
	$client->parameter = array();
	$client->parameter['action']    = 'login';
	$client->parameter['subaction'] = 'login';
	
	$client->method = 'GET';
	$client->request();
	

	if	( $client->status != '200')
	{
		echo '<span style="background-color:'.($client->status=='200'?'green':'red').'">HTTP-Status '.$client->status.'</span>';
	}
	
	$response = json_decode($client->response,true);
	$token = $response['session']['token'];
	$client->cookie =$response['session']['name'].'='.$response['session']['id'];
	?><pre><?php print_r($client) ?></pre><hr /><?php 
	
	$client->parameter = array();
	$client->parameter['action']    = 'login';
	$client->parameter['subaction'] = 'login';
	
	$client->method = 'POST';
	$client->parameter['token'         ] = $token;
	$client->parameter['dbid'          ] = $config['server']['database'];
	$client->parameter['login_name'    ] = $username;
	$client->parameter['login_password'] = $password;
	
	$client->request();
	$response = json_decode($client->response,true);
	?><pre><?php print_r($client); ?></pre><?php 
	
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
