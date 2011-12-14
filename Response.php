<?php

class Response {
    // property declaration
    public $mimeType;
    public $downloadType;
    public $status;
    public $length = 0;

    // method declaration
    function Response($response) {
	$lines = explode("\n",$response);
	foreach($lines as $line ) {
		  if (preg_match("/^Content-Type:(.*);.*/",$line,$matches)) {
		     $mt = explode("/",$matches[1]);
	   	     $this->mimeType = trim($mt[1]);
		     $this->downloadType = trim($mt[0]);
		  } else if(preg_match("/^Content-Type:(.*)/",$line,$matches)) {
		     $mt = explode("/",$matches[1]);
	   	     $this->mimeType = trim($mt[1]);
		     $this->downloadType = trim($mt[0]);
		  } else if (preg_match("/^Status:(.*)/",$line,$matches)) {
		     $this->status = trim($matches[1]);
		  } else if (preg_match("/^HTTP\/\d.\d (\d+).*/",$line,$matches)) {
		     $this->status = trim($matches[1]);
		  } else if (preg_match("/^Content-Length:(.*)/",$line,$matches)) {
		     $this->length = trim($matches[1]);
		  }

	}
        /*
	print $this->mimeType; 	        
	print $this->downloadType; 	        
	print $this->status; 	        
	print $this->length; 	        
        */
    }

}


?>
