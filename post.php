<html>
<head>
<title>Submit a new blog entry</title>
</head>
<body>

<?php if ( !empty($_POST['text']) ) {
	
	$dirname = './blogs/'.date('Ymd-His');
	mkdir($dirname);
	
	$textfile = fopen($dirname.'/text','w');
	fwrite($textfile,$_POST['text']);
	
	$titlefile = fopen($dirname.'/subject','w');
	fwrite($titlefile,$_POST['title']);
	fclose($titlefile);

	if ( !empty($_FILES['image']) )
	{
		$imagenamefile = fopen($dirname.'/image','w');
		fwrite($imagenamefile,'image-'.$_FILES['image']['name']);
		fclose($imagenamefile);
		move_uploaded_file($_FILES['image']['tmp_name'],$dirname.'/image-'.$_FILES['image']['name']);
	}
	
	$okfile = fopen($dirname.'/OK','w');
	fwrite($okfile,'');
	fclose($okfile);
	
	
	?>
	<h3>Saved!</h3><a href="blog.php">Start push to server now...</a><?php }
	?>

<h1>Blog</h1>
<form enctype="multipart/form-data" action="<?php echo $_SERVER['SCRIPT_NAME'] ?>" method="post">

<div class="line">
<div class="label">Titel</div>
<div class="value"><input type="text" name="title" value="" /></div>
</div>
<div class="line">
<div class="label">Text</div>
<div class="value"><textarea name="text"></textarea></div>
</div>
<div class="line">
<div class="label">File</div>
<div class="value"><input type="file" name="image"></input></div>
</div>


<br>

<input type="submit"></form>
<hr>

</body>
</html>
