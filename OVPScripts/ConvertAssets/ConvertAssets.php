<?php
//php ConvertAssets.php partnerId Secret  /Users/kaushik.jain/Downloads/entryIdForFlavor

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

$fout = fopen('ConverAssets.log', 'a');

$partnerId        = $argv[1]; //
$adminSecret      = $argv[2];
$mediaEntryIdList = $argv[3]; //'EntryId List';


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
    
    
    $line = "\n Staring conversion on";
    echo ($line);
    fwrite($fout, $line);
    
    foreach ($srcEntryRows as $row) {
        $line = "\nTriggering conversion for $row";
        echo ($line);
        fwrite($fout, $line);
        try {
            $conversionProfileId = null;
            $dynamicConversionAttributes = null;
            $result = $client->media->convert($row, $conversionProfileId, $dynamicConversionAttributes);
        }
        catch (Exception $ex) {
            $line = "\n Entry Conversion Failed";
            echo ($line);
            fwrite($fout, $line);
        }
        
    }
    $line = "\nConversion Done. ";
    echo ($line);
    fwrite($fout, $line);
} else {
    
    fwrite($fout, "File not found");
}

?>