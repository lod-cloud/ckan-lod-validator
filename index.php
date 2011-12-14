<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<HTML>
<HEAD>
<TITLE>CKAN LOD Datasets</TITLE>
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=ISO-8859-1">

<script type="text/javascript" src="autocomplete/jquery.js"></script>
<script type='text/javascript' src='autocomplete/jquery.bgiframe.min.js'></script>
<script type='text/javascript' src='autocomplete/jquery.ajaxQueue.js'></script>
<script type='text/javascript' src='autocomplete/thickbox-compressed.js'></script>
<script type='text/javascript' src='autocomplete/jquery.autocomplete.js'></script>
<link rel="stylesheet" type="text/css" href="autocomplete/main.css" />
<link rel="stylesheet" type="text/css" href="autocomplete/jquery.autocomplete.css" />
<link rel="stylesheet" type="text/css" href="autocomplete/thickbox.css" />


<script>
  $(document).ready(function() {
		$("#package").autocomplete("autocomplete.php", {
			matchContains: true,
			width: 300,
			selectFirst: false
		});
	});
</script>

<script type="text/javascript">
	function KeyCode(ev) {
		if(ev){
			TastenWert = ev.which
		} else {
			TastenWert = window.event.keyCode
		}
		if (TastenWert == 13) {
			document.form.submit();
		}
	}

	document.onkeypress = KeyCode;
</script>


<style type=text/css>
  body { background: white; color: black; font-family: sans-serif; line-height: 1.4em; padding: 2.5em 3em; margin: 0; }
  :link { color: #00c; }
  :visited { color: #609; }
  a:link img { border: none; }
  a:visited img { border: none; }
  .source { font-size: 10px; color: #800; }
  h1, h2, h3 { background: white; color: #800; }
  h1 { font: 170% sans-serif; margin: 0; }
  h2 { clear: both; font: 140% sans-serif; margin: 1.5em 0 -0.5em 0;  padding-bottom:20px; }
  h3 { font: 120% sans-serif; margin: 0.5em 0; }
  h4 { font: bold 100% sans-serif; }
  h5 { font: italic 100% sans-serif; }
  h6 { font: small-caps 100% sans-serif; }
  .hide { display: none; }
  p { margin: 0.5em 0;}
  pre { background: #fff6bb; font-family: monospace; line-height: 1.2em; padding: 1em 2em; }
  dt { font-weight: bold; margin-top: 0; margin-bottom: 0; }
  dd { margin-top: 0; margin-bottom: 0; }
  code, tt { font-family: monospace; }
  ul.toc { list-style-type: none; }
  ol.toc li a { text-decoration: none; }
  .note { color: red; }
  #header { border-bottom: 1px solid #ccc; }
  #logo { float: right; }
    #logo a img {
    padding-left: 20px;
    background: white;
  }
  #authors { clear: right; float: right; font-size: 80%; text-align: right; }
  #content { clear: both; margin: 2em auto 0 0; text-align: justify }
  #download, #demo { float: left; font-family: sans-serif; margin: 1em 0 1.5em; text-align: center; width: 50%; }
  #download h2, #demo h2 { font-size: 125%; margin: 1.5em 0 -0.2em 0; }
  #download small, #demo small { color: #888; font-size: 80%; }
  #footer { border-top: 1px solid #ccc; color: #aaa; margin: 2em 0 0; }
  .dataset {
    float: left;
    width: 570px;
  }
  .red {
    color: #800;
    font-weight: bold;
  }
  .info {
    padding-left: 15px;
  }
  ul, ul.info {
    padding-left: 30px;
  }
  td {
    vertical-align: top;
    padding-bottom: 8px;
  }

  h2 small a:link, h2 small a:visited, h3 small a:link, h3 small a:visited  {
    color: #ddd;
    font-size: 90%;
  }

</style>

</HEAD>
<BODY>

<div id="logo">
  <a href="http://www.fu-berlin.de/"><img src="http://www4.wiwiss.fu-berlin.de/bizer/d2r-server/images/fu-logo.gif" alt="Freie Universit&auml;t Berlin Logo" /></a>
</div>

<div id="header">
  <h1 style="font-size: 250%">CKAN LOD Datasets</h1>
</div>

<div>
  LOD Datasets on CKAN | <a href="validate.php">Validate</a> | <a href="levels.html">Help</a>
</div>

<div id="content">

  <form action="validate.php" method="post" name="package">
		<p style="margin-bottom:40px;">Search CKAN package: <input style="width: 300px;" size="500" id="package" name="package" onKeyPress="KeyCode;"/> <input type="submit" name="submit" value=">"/></p>
	</form>

  <p>This website gives an overview of Linked Data sources cataloged on <a href="http://ckan.net">CKAN</a> and their completeness level for inclusion in the <a href="http://lod-cloud.net">LOD cloud</a>.
  It furthermore offers a validator for your CKAN entry with step-by-step guidance.</p>

  <p>If you publish a Linked Data set yourself, please add it to <a href="http://ckan.net">CKAN</a> so that it appears in the next version of the <a href="http://lod-cloud.net">LOD cloud diagram</a>. Please describe your data set according to <a href="http://www.w3.org/wiki/TaskForces/CommunityProjects/LinkingOpenData/DataSets/CKANmetainformation">Guidelines for Collecting Metadata on Linked Datasets in CKAN</a>.</p>

  <p style="margin-top:20px;">
    This list is updated hourly.
    <?php
      error_reporting(E_NONE);
      $filename = "index.txt";

      if (file_exists($filename)) {
        echo "<br/>Last update: " . date ("Y-m-d  H:i:s", filemtime($filename)). " CET";
      }

    ?>
  </p>

  <h3>Completeness Levels</h3>
  <p>
    <ul>
      <li><a href="#level_1-3">Datasets with a completeness level &lt; 4 (requiring more information)</a></li>
      <li><a href="#collection">Data set collections (tagged dataset-collection)</a></li>
      <li><a href="#needsinfo">Data sets that need more information from the data publisher (tagged lodcloud.needsinfo)</a></li>
      <li><a href="#needsfixing">Data sets that need to be fixed (tagged lodcloud.needsfixing)</a></li>
      <li><a href="#nolinks">Data sets that have no external RDF links (tagged lodcloud.nolinks)</a></li>
      <li><a href="#unconnected">Data sets that have no external or internal RDF links (tagged lodcloud.unconnected)</a></li>
      <li><a href="#lodcloud_candidates">Data sets that are candidates for the next LOD cloud (and connected)</a></li>
      <li><a href="#lodcloud">Data sets with a completeness level of 4 (reviewed and already in lodcloud group)</a></li>
    </ul>
  </p>
  
  <h2 id="level_1-3">Datasets with a completeness level &lt; 4 (requiring more information) <small><a href="#level_1-3">#</a></small></h2>

  <p>Datasets with a level below 3 have to be updated with minimal information in order to be reviewed for addition to the LOD Cloud.</p>
  <p>Datasets with level 3 and no medal are missing enhanced information. You can help us by provide these still.</p>

  <?php

    include $filename;
  ?>
  </div>
	</BODY>
</HTML>