<?php

require('QueueEntry.class.php');

abstract class Queue
{
	abstract public function push( $entry );
	
	abstract public function pull();
}

?>