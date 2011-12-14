<?php
header("Access-Control-Allow-Origin: *");
include_once("config.php");
include_once("Response.php");

$url = "http://data.oceandrilling.org/codices/taxa/whiteinella/paradubia";
$ckanContentType = "application/rdf+xml, text/turtle, text/plain";


	if (isset($_GET['url'])) {
	  $url = $_GET['url'];
	  $ckanContentType = $_GET['contentType'];
	} else if (isset($_POST['url'])) {
	  $url = $_GET['url'];
	  $ckanContentType = $_GET['contentType'];
	}

$ct = explode("/",$ckanContentType);
$ckanDownloadType = $ct[0];
$ckanMimeType = $ct[1];
if ($ckanDownloadType=="example") {
  if ($ckanMimeType!="turtle" && $ckanMimeType!="n3") {
  	$contentTypeToRequest = "application/".$ct[1];
  } else {
    $contentTypeToRequest = "text/".$ct[1];
  }
} else {
  $contentTypeToRequest = $ckanContentType;
}
$headers = array( 
  "Accept: ".$contentTypeToRequest, 
); 

/**
* Examples where this code fails but curl via command line works: 
* http://viaf.org/viaf/86518157
* 
* http://liblists.sussex.ac.uk/lists/0B9D65E5-BB0B-F4CD-7BD9-E55B18BF7400.html  (method not allowed: maybe we should use GET?)
*/
$ch = curl_init();
curl_setopt($ch, CURLOPT_NOBODY, true); // Recommended: http://www.php.net/manual/en/function.curl-setopt.php

function connect($ch, $url, $headers) {
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
  curl_setopt($ch, CURLOPT_USERAGENT, "http://lod-cloud.net/robot");
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // to bypass HTTPS http://unitstep.net/blog/2009/05/05/using-curl-in-php-to-access-https-ssltls-protected-sites/
  // Only calling the head
  curl_setopt($ch, CURLOPT_HEADER, true); // header will be at output
  //curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'HEAD'); // makes it hang? http://stackoverflow.com/questions/770179/php-curl-head-request-takes-a-long-time-on-some-sites
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true );
  curl_setopt($ch, CURLOPT_MAXREDIRS, 10 );
  $content = curl_exec($ch);
  return $content;
}

$content = connect($ch, $url,$headers);
$response = new Response($content);

$msg = array();
if ($ckanDownloadType=="example") {
  $msg[] = "<div><strong>Example resource</strong>: <em>$url</em><ul>";
} else {
  $msg[] = "<div><strong>Download file (RDF dump)</strong>: <em>$url</em><ul>";
}
// AVAILABILITY
if ($response->status==200) { // ok
  $msg[] = '<li><span class="success">Availability</span>: Link seems currently OK.</li>';
} else if ($response->status==405) { // method not allowed  
  //$ch = curl_init();  
  //curl_setopt( $ch, CURLOPT_HTTPGET, true);
  //curl_setopt( $ch, CURLOPT_TIMEOUT, 3 );
  //$content = connect($ch, $url, $headers);
  //$response = new Response($content);
  //if ($response->status==200) {
  //  $msg[] = '<li><span class="success">Availability</span>: Link seems currently OK.';
  //} else {
    $msg[] = '<li><span class="warning">Availability</span>: Could not use method HEAD to test availability.</li>';
  //}
} else if ($response->status=="") {
  $msg[] = "<li><span class='warning'>Availability</span>: HTTP request returned empty status.</li>"; // e.g. ftp://ftp.uniprot.org/pub/databases/uniprot/current_release/rdf/uniref.rdf.gz
} else {
  $msg[] = "<li><span class='error'>Availability</span>: HTTP request failed (".$response->status."): broken link?</li>";
}
// INTERPRETABILITY
if ($ckanMimeType == $response->mimeType) {
  $msg[] = '<li><span class="success">Interpretability</span>: Format informed is OK.</li>';
} else {
  if ($response->downloadType=="" && $response->mimeType=="") {
     $msg[] = "<li><span class='warning'>Interpretability</span>: Response does not inform its content-type. The entry on CKAN claims <code>$ckanContentType</code>. You may ignore if you know that the content-type is correct.</li>";
  } else if ($ckanContentType=="" || $ckanContentType=="-") {
     $msg[] = "<li><span class='warning'>Interpretability</span>: The CKAN entry does not inform a content type. We are assuming it is a page with many files for download.</li>";     
  } else if (in_array($response->mimeType, $zip_formats)) {
     $msg[] = "<li><span class='success'>Interpretability</span>: Response claims to be in a compressed format that we know (".$response->downloadType."/".$response->mimeType."). The CKAN entry informs it is parseable as $ckanContentType. We have not yet tested that. Please make sure that is the case.</li>";
  } else {
     $msg[] = "<li><span class='error'>Interpretability</span>: Response claims to be in a format (".$response->downloadType."/".$response->mimeType.") which is different from the format informed in CKAN ($contentTypeToRequest). Typo?</li>";
  }
}
//WARNINGS
if (in_array($ckanContentType, $download_formats) && $response->length < 10000) {
  $msg[] = "<li><span class='warning'>Size:</span> You did not indicate an example format but the returned size is quite small (".$response->length."). Did you mean example/".$ckanMimeType." instead of $ckanContentType? Ignore if your download file is indeed small. If this is a page with many download links, please leave the field 'format' empty.</span></li>";
} 
if (in_array($ckanContentType, $example_formats) && $response->length > 1000000) {
  $msg[] = "<li><span class='warning'>Size:</span> You indicated via <code>$ckanContentType</code> that this was an example URI, but the returned size is quite large (".$reponse->length." bytes). Please use <code>application/".$response->mimeType."</code> if this is a download (RDF dump). (ignore if your example URI is indeed large)</li>";
}

  $msg[] = "</ul>";
  $msg [] = "<pre class='verbose' style='display: none' onclick='$(this).hide()'>Request Method: HEAD \nRequested content-type: $contentTypeToRequest \n\nResponse:\n$content</pre>";

echo join($msg," ")."</div>\n";

curl_close ($ch);

?>
