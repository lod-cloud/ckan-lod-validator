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

function backup_ckan_json($package_id) {
  global $datasets;
  global $ckan;

  $package = $ckan->get_package_entity($package_id);
  $datasets[$package->name] = $package;
  $s = serialize($package);
  file_put_contents("backup/".$package->name, $s);

  $backup_date = date("Y-m-d");
  if (!is_dir("backup_ckan/".$backup_date)) {
    mkdir("backup_ckan/".$backup_date);
    file_put_contents("backup_ckan/$backup_date/".$package->name, $s);
  }

  return $package;
}

$datasets = array();

if (!is_dir("backup")) {
    mkdir("backup");
}

echo "Fetching lodcloud dataset list ... ";
$group_description = $ckan->get_group_entity('lodcloud');
$number_of_datasets = count($group_description->packages) ;
echo "OK (" . $number_of_datasets . " datasets)".PHP_EOL;

foreach ($group_description->packages as $package_id) {
  echo "Fetching dataset $package_id ... ";
  try {
    $package = backup_ckan_json($package_id);
    echo $package->name . PHP_EOL;
  } catch (Exception $e) {
    try {
      $package = backup_ckan_json($package_id);
      echo $package->name . PHP_EOL;
      sleep(1);
    } catch (Exception $e) {
      echo PHP_EOL, 'Caught exception: ',  $e->getMessage(), PHP_EOL;
    }
  }
}

echo "Fetching lod dataset list ... ";
$tag_description = $ckan->get_tag_entity("lod");
$number_of_datasets = count($tag_description) ;
echo "OK (" . $number_of_datasets . " datasets)".PHP_EOL;
foreach ($tag_description as $package_id) {
  try {
    $package = $ckan->get_package_entity($package_id);
    echo "Fetching dataset $package_id ... ";
    if (!in_array($package->name, array_keys($datasets)) && (!endsWith($package->name, "_"))) {
      try {
        backup_ckan_json($package->name);
        echo $package->name . PHP_EOL;
      } catch (Exception $e) {
        echo PHP_EOL, $package->name, ': Caught exception: ',  $e->getMessage(), PHP_EOL;
      }
    }
  } catch (Exception $e) {
    try {
      $package = $ckan->get_package_entity($package_id);
      echo "Fetching dataset $package_id ... ";
      if (!in_array($package->name, array_keys($datasets)) && (!endsWith($package->name, "_"))) {
        try {
          backup_ckan_json($package->name);
          echo $package->name . PHP_EOL;
        } catch (Exception $e) {
          echo PHP_EOL, $package->name, ': Caught exception: ',  $e->getMessage(), PHP_EOL;
        }
      } else {
        echo PHP_EOL."Not written ".$package->name.PHP_EOL;
      }
    } catch (Exception $e) {
      echo PHP_EOL, $package->name, ': Caught exception: ',  $e->getMessage(), PHP_EOL;
    }
  }
}

echo "Backup made of ".sizeof($datasets)." packages.";
