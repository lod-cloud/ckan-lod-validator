<?php
header("Access-Control-Allow-Origin: *");
/**
* This script slurps the availability information computed by Mondeca and kindly provided by Pierre-Yves.
* 
*/

	if (isset($_GET['package'])) {
	  $package = $_GET['package'];
	} else if (isset($_POST['package'])) {
	  $url = $_GET['package'];
	} else {
          die('No package informed.');
        }

		$availability = "?";
                // temporary. will go to a DB later along with other avail metrics
                $file = fopen('sparql_availability.csv', 'r');
                while (($line = fgetcsv($file,1000,";")) !== FALSE) {
                  if ($line[0]==$package) {
                    $availability = (float) $line[1];
                    break;
                  }
                }
                fclose($file);
                $lastmonth = "last month <a href='http://labs.mondeca.com/sparqlEndpointsStatus/details/".$package.".html'>[?]</a>";
                $avail_msg = $availability."% last month.";
                if ($availability == "?") {
                  $avail_msg = "not assessed yet. <a href='http://labs.mondeca.com/sparqlEndpointsStatus/'>[more]</a>";
                } else if ($availability==100.00) {
                  $avail_msg = "<span style='color:green'>$availability%</span> $lastmonth. Congratulations!";
                } else if ($availability > 0.85) {
                  $avail_msg = "<span style='color:green'>$availability%</span> $lastmonth.";
                } else if ($availability > 0.5) {
                  $avail_msg = "<span style='color:red'>$availability%</span> $lastmonth. Can you improve?";
                } else if ($availability==0.0) {
                  $avail_msg = "<span style='color:red'>$availability%</span> $lastmonth. Please check the URL you provided.";
                }

 	echo "<div><strong>SPARQL endpoint availability:</strong> ".$avail_msg."</div>"
?>
