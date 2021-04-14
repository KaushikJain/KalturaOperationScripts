<?php

//USAGE php RedistributeAssets.php partnerId partnerSecret /Users/kaushik.jain/Downloads/entryId
//Last Param is file location


ini_set('display_errors', 'On');
error_reporting(E_ALL);
require_once('php5/KalturaClient.php');
require_once('php5/KalturaTypes.php');
ini_set("memory_limit", "1024M");

ini_set("xdebug.var_display_max_children", -1);
ini_set("xdebug.var_display_max_data", -1);
ini_set("xdebug.var_display_max_depth", -1);

var_dump($argv);
var_dump($argc);

$fout = fopen('RedistributeAssets.log', 'a');

$partnerId        = $argv[1]; //
$adminSecret      = $argv[2];
$mediaEntryIdList = $argv[3]; //'$SeriesEntryListFN';


$config             = new KalturaConfiguration($partnerId);
$config->serviceUrl = 'http://www.kaltura.com/';
$client             = new KalturaClient($config);
$ks                 = $client->session->start($adminSecret, null, KalturaSessionType::ADMIN, $partnerId, null, null);
$client->setKs($ks);



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
        $line = "\n Starting Redistribution for $row";
        echo ($line);
        fwrite($fout, $line);
        
        $filter                    = new KalturaEntryDistributionFilter();
        $filter->entryIdEqual      = $row;
        $pager                     = null;
        $contentdistributionPlugin = KalturaContentdistributionClientPlugin::get($client);
        $foundEntries              = $contentdistributionPlugin->entryDistribution->listAction($filter, $pager);
        if ($foundEntries->totalCount > 0) {
            $entry = $foundEntries->objects[0];
            //var_dump($entry);
        } else {
            $line = "\n Entry Distribution not found for $row  ";
            echo ($line);
            fwrite($fout, $line);
        }
        
        $entryDistId = $entry->id;
        $contentdistributionPlugin = KalturaContentdistributionClientPlugin::get($client);
        try {
            $contentdistributionPlugin->entryDistribution->submitUpdate($entryDistId);
        }
        catch (Exception $ex) {
            $line = "\n Entry Distribution failed $row ... Retrying..";
            echo ($line);
            fwrite($fout, $line);
            $contentdistributionPlugin->entryDistribution->retrySubmit($entryDistId);
        }
        
        $line = "\n Finished Re-distributing  $row ";
        echo ($line);
        fwrite($fout, $line);
        
    }
    $line = "\nFinished Updating Start and End Date";
    echo ($line);
    fwrite($fout, $line);
} else {
    
    fwrite($fout, "File not found");
}


?>