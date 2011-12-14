<?php

function startsWith($Haystack, $Needle){
    return strpos($Haystack, $Needle) === 0;
}

$q = $_GET["q"];

$handle = fopen("ckan_packages.txt", "rb");
$contents = fread($handle, filesize("ckan_packages.txt"));
fclose($handle);

$packages = explode("\n", $contents);
foreach ($packages as $package) {
  if (strpos($package, $q) !== false) {
    echo utf8_encode("$package\n");
  } 
}

?>