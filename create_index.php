<?php

  set_time_limit(0);
  ignore_user_abort(true);

  include_once("config.php");
  include_once('Ckan_client-PHP/Ckan_client.php');

  $fh_log = fopen('log.txt', 'w');
  fwrite($fh_log, '');
  fclose($fh_log);

  $ckan_lodcloud_group_id = "ce086ef6-9e56-4af1-a63c-fd768fa2dfff";
  
  $ckan = new Ckan_client();

  function startsWith($Haystack, $Needle){
    return strpos($Haystack, $Needle) === 0;
  }

    $packages = array();
    $path = "backup/";
    if ($dir=opendir($path)) {
        while($file=readdir($dir)) {
            if (!is_dir($file)) {
                $s = file_get_contents($path.$file);
                $package = unserialize($s);
                $packages[] = $package;
            }
        }
    }

    $level4levels = array();

    $datasets[1] = array();
    $datasets[2] = array();
    $datasets[3] = array();
    
    $datasets["needsinfo"] = array();
    $datasets["needsfixing"] = array();
    $datasets["nolinks"] = array();
    $datasets["unconnected"] = array();
    $datasets["candidate"] = array();

    $datasets_with_warnings = array();
    $datasets_with_warnings_levels = array();

    $datasets_candidates = array();
    
    $medal_datasets = array();
    $level4_warnings_datasets = array();
    
    foreach ($packages as $package) {

      $fh_log = fopen('log.txt', 'a');

      if (in_array("meta.duplicate", $package->tags)) {
        continue;
      }

      if (in_array("dataset-collection", $package->tags)) {
        $datasets["collection"][] = $package->name;
        continue;
      }

      $groups = $package->groups;
      foreach ($groups as $id => $group_id) {
        $group = $ckan->get_group_entity($group_id);
        $groups[$id] = $group->name;
      }

      if (in_array("lodcloud.needsfixing", $package->tags)) {
        $datasets["needsfixing"][] = $package->name;
      }
      if (in_array("lodcloud.unconnected", $package->tags)) {
        $datasets["unconnected"][] = $package->name;
      }
      if (in_array("lodcloud.candidate", $package->tags) && !(in_array("lodcloud", $groups))) {
        $datasets["candidate"][] = $package->name;
      }
      if (in_array("lodcloud.needsinfo", $package->tags)) {
        $datasets["needsinfo"][] = $package->name;
      }
      if (in_array("lodcloud.nolinks", $package->tags)) {
        $datasets["nolinks"][] = $package->name;
      }

      $datasets_with_warnings = array_merge($datasets["nolinks"], $datasets["needsinfo"], $datasets["unconnected"], $datasets["needsfixing"]);

      // copied from validate.php
        $resources = $package->resources;
        $extras = get_object_vars($package->extras);

        $topic_set = false;
        foreach (array_keys($topic_names) as $topic) {
          if (in_array($topic, $package->tags)) {
            $topic_set = true;
          }
        }
        
        $license_information = false;
        if (isset($package->license)) {
          $license_information = true;
        } else {
          foreach ($extras as $key => $value) {
            if ((trim($key) == "license_link") && (strlen(trim($value)) > 0)) {
              $license_information = true;
              break;
            }
          }
        }

        $sparql_endpoint = false;
        $download_link = false;
        $example_link = false;
        $void_or_sitemap = false;
        $mapping_link = false;
        $schema_link = false;

        foreach ($resources as $resource_object) {

          $resource = get_object_vars($resource_object);

          if ($resource["format"] == "api/sparql") {
            $sparql_endpoint = true;
          }

          if (in_array($resource["format"], $download_formats)) {
            $download_link = true;
          }

          if (in_array($resource["format"], $example_formats)) {
            $example_link = true;
          }

          if (($resource["format"] == "meta/void") || ($resource["format"] == "meta/sitemap")) {
            $void_or_sitemap = true;
          }

          if ($resource["format"] == "meta/rdf-schema") {
            $schema_link = true;
          }

          if (startsWith($resource["format"], "mapping/")) {
            $mapping_link = true;
          }
        }

        $deref_vocab_set = false;		
        $deref_vocab_tags = array("deref-vocab", "no-deref-vocab", "no-proprietary-vocab");
        foreach ($deref_vocab_tags as $tag) {
          if (in_array($tag, $package->tags)) {
            $deref_vocab_set = true;
          }
        }

        $published_by_set = false;		
        $published_by_tags = array("published-by-producer", "published-by-third-party");
        foreach ($published_by_tags as $tag) {
          if (in_array($tag, $package->tags)) {
            $published_by_set = true;
          }
        }

        $links = false;
        foreach ($extras as $key => $value) {
          if (preg_match('/^links:(.*)/i', $key, $match)) {
            $links = true;
            break;
          }
        }

        $format_tags = false;
        foreach ($package->tags as $value) {
          if (preg_match('/^format-(.*)/i', $value, $match)) {
            $format_tags = true;
            break;
          }
        }

        $shortname_provided_if_needed = false;
        if (strlen($package->title) >= 50) {
          if (isset($extras["shortname"])) {
            $shortname_provided_if_needed = true;
          }
        } else {
          $shortname_provided_if_needed = true;
        }

        /*
        $groups = $package->groups;
        foreach ($groups as $id => $group_id) {
          $group = $ckan->get_group_entity($group_id);
          $groups[$id] = $group->name;
        }
         */

        $namespace = false;
        if ($extras["namespace"]) {
          $namespace = true;
        }

        $level = 0;
        if (isset($package->author) && isset($package->url) && (in_array("lod", $package->tags))) {
          $level = 1;
          if ($topic_set && $example_link) {
            $level = 2;
            if (isset($package->notes) && $license_information && $shortname_provided_if_needed && $deref_vocab_set && $published_by_set) {
              $level = 3;
            }
          }
        }

        $level4levels[$package->name] = $level;

        if (in_array("lodcloud", $groups)) {
          $level = 4;
        }

        // check for enhanced information
        $missing_minimal_information = array(0=>array(), 1=>array(), 2=>array(), 3=>array(), 4=>array());
        $missing_enhanced_information = array(0=>array(), 1=>array(), 2=>array(), 3=>array(), 4=>array());

        if(!isset($package->url)) {
          $missing_minimal_information[1][]     = "<strong>Missing URL.</strong> Please provide an URL for the data set.";
        }

        if (!isset($package->author)) {
          $missing_minimal_information[1][]     = "<strong>Missing contact.</strong> Please provide the name of publishing org and/or person using the CKAN field <em>Author</em>. It is important to know who created this data set.";
        }

        if (!isset($package->author_email) && !isset($package->maintainer_email)) {
          $missing_enhanced_information[1][]    = "<strong>Missing contact email.</strong> Please provide a contact email using the CKAN field <em>Author email</em> or <em>Maintainer email</em>. It is important to know who to contact if there are errors or missing dataset descriptions.";
        }

        if (!in_array("lod", $package->tags)) {
          $missing_minimal_information[1][]     = "<strong>Missing lod tag.</strong> Please tag the data set with <em>lod</em>.";
        }
        
        if (!$topic_set) {
          $missing_minimal_information[2][]     = "<strong>Missing topic.</strong> No topic tag found. Use one of: <em>".  join(", ", array_keys($topic_names))."</em>.";
        }
        
        if (!$example_link) {
          $missing_minimal_information[2][]     = "<strong>Missing example URI.</strong> Please provide an example URI if available in the <em>Downloads & Resources</em> section, using one of the following formats: <em>".join(", ", $example_formats)."</em>. ";
        }

        if (!$sparql_endpoint) {
          $missing_enhanced_information[2][]     = "<strong>Missing SPARQL endpoint.</strong> Please provide a link to the SPARQL endpoint if available in the <em>Downloads & Resources</em> section, using the <em>api/sparql</em> format.";
        }
        if (!$download_link) {
          $missing_enhanced_information[2][]     = "<strong>Missing download(s).</strong> Please provide a link to the download file(s) if available in the <em>Downloads & Resources</em> section, using one of the following formats: <em>".join(", ", $download_formats)."</em>. ";
        }

        if (!is_numeric($extras["triples"])) {
          $missing_enhanced_information[2][]     = "<strong>Missing size.</strong> No data set size found. Please provide the approximate size of the data set in RDF triples using the custom CKAN field <em>triples</em>.";
        }

        if (!$links && !in_array("lodcloud.nolinks", $package->tags)) {
          $missing_enhanced_information[2][]  = "<strong>Missing link count information.</strong> Please provide the custom CKAN field <em>links:&lt;target_data_set&gt;</em> with the number of RDF links pointing at data set target_data_set. Please provide separate <em>links:&lt;target_data_set&gt;</em> statements for each data set to which <em>".$package->name."</em> links.";
        }
        
        if (!isset($package->notes)) {
          $missing_minimal_information[3][]     = "<strong>Missing description.</strong> Please provide a description of the data set using the CKAN field <em>Notes</em>.";
        }

        if (!$license_information) {
          $missing_minimal_information[3][]     = "<strong>Missing license.</strong> Please provide the data set's license using the CKAN drop-down field <em>License</em>.";
        }

        if (!isset($package->version)) {
          $missing_enhanced_information[3][]     = "<strong>Missing version.</strong> Please provide the last modification date or version of the data set using the CKAN field <em>Version</em>."; 
        }

        if (!$shortname_provided_if_needed) {
          $missing_minimal_information[3][]     = "<strong>Missing short name.</strong> The data set name is longer than 50 characters. Please provide a short name using the custom CKAN field <em>shortname</em>.";
        }
        
        if (!$namespace) {
          $missing_enhanced_information[3][]     = "<strong>Missing instance namespace.</strong> Please provide the namespace used for instances of the dataset. For example, the namespace for DBpedia instances is <em>http://dbpedia.org/resource/</em>";
        }
        
        if (!$void_or_sitemap) {
          $missing_enhanced_information[3][]     = "<strong>Missing voiD or Semantic Sitemap.</strong> Please provide a link to a voiD description or XML Sitemap if available in the <em>Downloads & Resources</em> section, using one of the following formats: <em>meta/void</em>, <em>meta/sitemap</em>.";
        }

        if (!$published_by_set) {
          $missing_minimal_information[3][]     = "<strong>Missing publisher information.</strong> Please provide a tag indicating if the dataset was published by the producer of the data, or by a third party. The tag should be one of: <em>".join($published_by_tags,", ")."</em>.";
        }

        if (!$deref_vocab_set) {
          $missing_minimal_information[3][]     = "<strong>Missing proprietary vocabulary information.</strong> Please provide a tag indicating if the dataset does not contain proprietary vocabulary terms, or if it contains proprietary terms, if they are dereferenceable or not. The tag should be one of: ".join($deref_vocab_tags,", ").".";
        }
        
        if (!$mapping_link) {
          $missing_enhanced_information[3][]     = "<strong>Missing mapping(s).</strong> If the data set provides vocabulary mappings to other vocabularies, provide a link to the mapping file in the <em>Resources</em> section, using the following format: <em>mapping/&lt;format&gt;</em>. Replace <em>&lt;format&gt;</em> with the mapping/rule language used, like R2R or RIF.";
        }

        if (!$schema_link) {
          $missing_enhanced_information[3][]     = "<strong>Missing schema.</strong> If the data set uses a proprietary vocabulary, provide a download link to the RDF/OWL Schema used by the data set (in addition to having dereferenceable vocabulary URIs) in the <em>Resources</em> section, using the following format: <em>meta/rdf-schema</em>.";
        }

        if (!$format_tags) {
          $missing_enhanced_information[3][]     = "<strong>Missing vocabularies used.</strong> Provide vocabularies used by the data set as tgags, e.g. <em>format-skos</em>, <em>format-foaf</em>.";
        }

        if (!in_array("vocab-mappings", $package->tags) && !in_array("no-vocab-mappings", $package->tags)) {
          $missing_enhanced_information[3][]     = "<strong>Missing information on vocabulary mappings.</strong> Indicate whether mappings for proprietary vocabulary terms are provided (<code>owl:equivalentClass</code>, <code>owl:equivalentProperty</code>, <code>rdfs:subClassOf</code>, and/or <code>rdfs:subPropertyOf</code> links, or mappings expressed as RIF rules or using the R2R Mapping Language) by using the tag <em>vocab-mappings</em>. Use <em>no-vocab-mappings</em> otherwise.";
        }

        if (!in_array("provenance-metadata", $package->tags) && !in_array("no-provenance-metadata", $package->tags)) {
          $missing_enhanced_information[3][]     = "<strong>Missing information on provenance metadata.</strong> Indicates whether the data set provides provenance meta-information (creator of the data set, creation date, maybe creation method) as document meta-information or via a voiD description. For instance, using the <code>dc:creator</code> or <code>dc:date</code> properties. Use the tag <em>provenance-metadata</em> / <em>no-provenance-metadata</em>.";
        }

        if (!in_array("license-metadata", $package->tags) && !in_array("no-license-metadata", $package->tags)) {
          $missing_enhanced_information[3][]     = "<strong>Missing information on licensing metadata.</strong> Indicates whether the data set provides licensing meta-information as document meta-information or via a voiD description. For instance, using the <code>dc:rights</code> property. Use the tag <em>license-metadata</em> / <em>no-license-metadata</em>.";
        }

        $missing_infos = 0;
        $missing_enhanced_infos = 0;

        for ($i = 1; $i <= $level; $i++) {
          $missing_infos += sizeof($missing_minimal_information[$i]);
          $missing_enhanced_infos += sizeof($missing_enhanced_information[$i]);
        }

        if (($missing_infos + $missing_enhanced_infos) == 0) {
            $medal_datasets[] = $package->name;
          }

        if ($level == 4) {
          if ($missing_infos > 0) {
            $level4_warnings_datasets[] = $package->name;
          }
        }
              
                    
// end of copy
                    
      if (in_array($package->name, $datasets_with_warnings)
              && !in_array($package->name, $datasets["candidate"])) {
        $datasets_with_warnings_levels[$package->name] = $level;
      }
      if (!in_array($package->name, $datasets_with_warnings)
              && !in_array($package->name, $datasets["candidate"])
              && (!in_array($package->name, $datasets["needsfixing"]))) {
        $datasets[$level][] = $package->name;
      }
      if (in_array($package->name, $datasets["candidate"])
              && (!in_array($package->name, $datasets["unconnected"]))
              && (!in_array($package->name, $datasets["needsfixing"]))) {
        $datasets_candidates[$package->name] = $level;
      }
      
      fwrite($fh_log, "$level - ".$package->name."\n");
      fclose($fh_log);
    }

    function print_dataset_link($package, $extra = null) {
      global $levels;
      global $medal_datasets;
      global $level4_warnings_datasets;
      global $level4levels;
      global $datasets_with_warnings_levels;
      global $datasets_candidates;
      
      $result = "<div class=\"dataset\"><a href=\"validate.php?package=$package\">$package</a>";
      if ($extra == 4) {
        if (in_array($package, $level4_warnings_datasets)) {
          $result .= " <img src=\"exclamation.png\"/>";
        }
        /*
        if (in_array($package, $medal_datasets)) {
          $result .= " <img src=\"medal.png\"/>";
        }
        */
      }
      $result .= "</div><div>&nbsp;";
      if ($extra == 4) {
        if (in_array($package, $level4_warnings_datasets)) {
          $result .= " <a href=\"levels.html#level".$level4levels[$package]."\">Level ".$level4levels[$package]." (".$levels[$level4levels[$package]].")</a>";
        }
      }
      if ($extra == 10) {
        if (in_array($package, array_keys($datasets_candidates))) {
          if ($datasets_candidates[$package] < 3) {
            $result .= " <a href=\"levels.html#level".$datasets_candidates[$package]."\">Level ".$datasets_candidates[$package]." (".$levels[$datasets_candidates[$package]].")</a>";
          }
        }
      }
      if (isset($extra) && ($extra < 4)) {
        if (in_array($package, $medal_datasets)) {
          $result .= "<img src=\"medal.png\"/>";
        } else {
          $result .= "<img src=\"medal-gray.png\" alt=\"Missing enhanced information.\" title=\"Missing enhanced information.\"/>";
        }
        $result .= " <a href=\"levels.html#level$extra\">Level $extra (".$levels[$extra].")</a>";
      }
      return $result."</div>";
    }
    
    $print = "";

    foreach ($datasets[1] as $dataset) {
      $print .= print_dataset_link($dataset, 1) . "\n";

    }
    foreach ($datasets[2] as $dataset) {
      $print .= print_dataset_link($dataset, 2) . "\n";
    }
    foreach ($datasets[3] as $dataset) {
      $print .= print_dataset_link($dataset, 3) . "\n";
    }
  
  
    $print .= "<h2 id=\"collection\">Data set collections (tagged dataset-collection) <small><a href=\"#collection\">#</a></small></h2>";

    foreach ($datasets["collection"] as $dataset) {
      $print .= print_dataset_link($dataset) . "\n";
    }

    $print .= "<h2 id=\"needsinfo\">Data sets that need more information from the data publisher (tagged lodcloud.needsinfo) <small><a href=\"#needsinfo\">#</a></small></h2>

    <p>The data provider or dataset homepage do not provide mininum information (and information can't be determined from SPARQL endpoint or downloads).</p>\n";

    foreach ($datasets["needsinfo"] as $dataset) {
      $print .= print_dataset_link($dataset, 10) . "\n";
    }

    $print .= "<h2 id=\"needsfixing\">Data sets that need to be fixed (tagged lodcloud.needsfixing) <small><a href=\"#needsfixing\">#</a></small></h2>\n";

    foreach ($datasets["needsfixing"] as $dataset) {
      $print .= print_dataset_link($dataset, 10) . "\n";
    }

    $print .= "<h2 id=\"nolinks\">Data sets that have no external RDF links (tagged lodcloud.nolinks) <small><a href=\"#nolinks\">#</a></small></h2>\n";

    foreach ($datasets["nolinks"] as $dataset) {
      $print .= print_dataset_link($dataset, 10) . "\n";
    }

    $print .= "<h2 id=\"unconnected\">Data sets that have no external or internal RDF links (tagged lodcloud.unconnected) <small><a href=\"#unconnected\">#</a></small></h2>\n";

    $print .= "<p>If a data set is unconnected (less than 50 in- or outlinks) to the rest of the LOD cloud, it can not be included in the next LOD cloud version.</p>\n";

    foreach ($datasets["unconnected"] as $dataset) {
      $print .= print_dataset_link($dataset, 10) . "\n";
    }

    $print .= "<h2 id=\"lodcloud_candidates\">Data sets that are candidates for the next LOD cloud (and connected) <small><a href=\"#lodcloud_candidates\">#</a></small></h2>\n";

    $print .= "<p>These data sets are candidates for the LOD cloud and will be reviewed for inclusion.</p>\n";

    $cand = array_diff($datasets["candidate"], $datasets["unconnected"]);
    foreach ($cand as $dataset) {
      $print .= print_dataset_link($dataset, 10) . "\n";
    }

    $print .= "<h2 id=\"lodcloud\">Data sets with a completeness level of 4 (reviewed and already in lodcloud group) <small><a href=\"#lodcloud\">#</a></small></h2>\n";

    $print .= "<p>If a data set is marked with an exclamation mark, its CKAN entry is missing minimal information.</p>\n";

    foreach ($datasets[4] as $dataset) {
      if (!in_array($dataset, $datasets["unconnected"]) && !in_array($dataset, $datasets["needsfixing"])) {
        $print .= print_dataset_link($dataset, 4) . "\n";
      }
    }

    
    $fh = fopen('index.txt', 'w');
    fwrite($fh, '');
    fclose($fh);

    $fh = fopen('index.txt', 'a');
    fwrite($fh, $print);
    fclose($fh);