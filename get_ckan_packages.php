<?php

set_time_limit(0);
ignore_user_abort(true);

function endsWith($Haystack, $Needle){
    return strpos($Haystack, $Needle) === strlen($Haystack)-1;
}

function startsWith($Haystack, $Needle){
    return strpos($Haystack, $Needle) === 0;
}

@ini_set('error_reporting', E_ALL);

include_once('Ckan_client-PHP/Ckan_client.php');

$ckan = new Ckan_client();

echo "Fetching dataset list ... ".PHP_EOL;
$group_description = $ckan->get_group_entity('lodcloud');

$package_names = array();

foreach ($group_description->packages as $package_id) {
  echo "Adding dataset ";
  try {
      $package = $ckan->get_package_entity($package_id);
      $package_names[] = $package->name;
      echo $package->name . PHP_EOL;
  } catch (Exception $e) {
      try {
          $package = $ckan->get_package_entity($package_id);
          $package_names[] = $package->name;
          echo $package->name . PHP_EOL;
          sleep(1);
      } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), PHP_EOL;
      }
  }
}

$tag_description = $ckan->get_tag_entity("lod");
echo "";
foreach ($tag_description as $package_id) {
  try {
    $package = $ckan->get_package_entity($package_id);
    if (!in_array($package->name, $package_names)) {
      echo "Adding dataset ".$package->name.PHP_EOL;
      $package_names[] = $package->name;
    }
  } catch (Exception $e) {
    echo 'Caught exception: ',  $e->getMessage(), PHP_EOL;
  }
}

$fh = fopen('ckan_packages.txt', 'w');
fwrite($fh, '');
fclose($fh);

$fh = fopen('ckan_packages.txt', 'a');
foreach ($package_names as $name) {
  fwrite($fh, $name."\n");
}
fclose($fh);

echo "Added ".sizeof($package_names)." packages.";