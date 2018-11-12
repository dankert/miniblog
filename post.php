<html>
<head>
<title>Submit a new blog entry</title>
</head>
<body>

<?php if ( !empty($_POST['text']) ) {

	require('/home/dankert/Entwicklung/Queue/FileQueue.class.php');
	
	$value = array();
	
	$value['text'] = $_POST['text'];
	
	$value['title'] = $_POST['title'];

	
	if ( !empty($_FILES['image']) )
	{
		$files = array( $_FILES['image']['tmp_name']);
		$value['files'] = array('filename'=>$_FILES['image']['tmp_name'],'name'=>$_FILES['image']['name'],'content'=>base64_encode(file_get_contents($_FILES['image']['tmp_name'])));
// 		$files = array( $_FILES['image']['tmp_name'],$_FILES['image']['name']);
	}
	
	$queue = new FileQueue();
	$queue->directory = './queue/fileupload';
	
	$entry  = new QueueEntry();
	$entry->files = $files;
	$entry->value = $value;
	
	$entry->push();
	
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
