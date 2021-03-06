<?php

//USAGE php DeleteAssets.php 1982541 a16d34b92e6764a4baf71244d1333996 /Users/kaushik.jain/Downloads/entryId
//Last Param is file location


ini_set('display_errors', 'On');
error_reporting(E_ALL);
require_once('php5/KalturaClient.php');
require_once('php5/KalturaTypes.php');
require_once('php5/KalturaEnums.php');
ini_set("memory_limit", "1024M");

ini_set("xdebug.var_display_max_children", -1);
ini_set("xdebug.var_display_max_data", -1);
ini_set("xdebug.var_display_max_depth", -1);

var_dump($argv);
var_dump($argc);

$fout = fopen('DeleteAssets.log', 'a');

$partnerId        = $argv[1]; //
$adminSecret      = $argv[2];
$mediaEntryIdList = $argv[3]; //'$SeriesEntryListFN';


$config             = new KalturaConfiguration($partnerId);
$config->serviceUrl = 'http://www.kaltura.com/';
$client             = new KalturaClient($config);
$ks                 = $client->session->start($adminSecret, null, KalturaSessionType::ADMIN, $partnerId, null, null);
$client->setKs($ks);
 echo $ks;


if (file_exists($mediaEntryIdList)) {
    $line = "File exists. Processing now";
    echo ($line);
    fwrite($fout, $line);
    
    $entryIdF     = file($mediaEntryIdList);
    $srcEntryRows = array_map('trim', $entryIdF);
    
    $line = "\nCOUNT = ";
    echo ($line);
    fwrite($fout, $line);
    
    
    $line = sizeof($srcEntryRows);
    echo ($line);
    fwrite($fout, $line);
    
    foreach ($srcEntryRows as $row) {
        $filter = new KalturaAssetFilter();
  $filter->entryIdIn = "1_rh06n87v";
  $pager = new KalturaFilterPager();

  try {
    $result = $client->flavorAsset->listAction($filter, $pager);
    var_dump($result);
  } catch (Exception $e) {
    echo $e->getMessage();
  }
    }
    $line = "\nFinished Deleting";
    echo ($line);
    fwrite($fout, $line);
} else {
    fwrite($fout, "File not found");
}


?>