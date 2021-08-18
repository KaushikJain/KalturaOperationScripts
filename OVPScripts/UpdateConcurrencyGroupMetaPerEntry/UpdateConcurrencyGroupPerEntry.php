<?php
//php UpdateConcurrencyGroupPerEntry.php partnerId adminsecret /Users/kaushik.jain/Downloads/entryWithDates 6840571
ini_set('display_errors', 'On');
error_reporting(E_ALL);
require_once ('php5/KalturaClient.php');
require_once ('php5/KalturaTypes.php');
ini_set("memory_limit", "1024M");

ini_set("xdebug.var_display_max_children", -1);
ini_set("xdebug.var_display_max_data", -1);
ini_set("xdebug.var_display_max_depth", -1);

var_dump($argv);
var_dump($argc);

$fout = fopen('UpdateConcurrencyGroupPerEntry.log', 'a');

$partnerId = $argv[1]; //
$adminSecret = $argv[2];
$mediaEntryIdList = $argv[3]; //'$SeriesEntryListFN';
$customDataId = $argv[4]; //'6840571';



$config = new KalturaConfiguration($partnerId);
$config->serviceUrl = 'http://www.kaltura.com/';
$client = new KalturaClient($config);
$ks = $client
    ->session
    ->start($adminSecret, null, KalturaSessionType::ADMIN, $partnerId, null, null);
$client->setKs($ks);
if (file_exists($mediaEntryIdList))
{
    $line = "File exists. Processing now";
    echo ($line);
    fwrite($fout, $line);

    $entryIdF = file($mediaEntryIdList);
    $srcEntryRows = array_map('trim', $entryIdF);

    $line = "\nCOUNT = ";
    echo ($line);
    fwrite($fout, $line);

    $line = sizeof($srcEntryRows);
    echo ($line);
    fwrite($fout, $line);

    foreach ($srcEntryRows as $row)
    {
        $arrfields = explode(',', $row);

        $row = $arrfields[0];
        $concurrencyGroup = $arrfields[1];

        $line = "\n For $row Concurrency Group will be updated with $concurrencyGroup";
        echo ($line);
        fwrite($fout, $line);

        $filter = new KalturaMetadataFilter();
        $filter->objectIdIn = $row;
        $filter->metadataProfileIdEqual = $customDataId;
        $pager = null;
        $metadataPlugin = KalturaMetadataClientPlugin::get($client);
        $resultMeta0 = $metadataPlugin
            ->metadata
            ->listAction($filter, $pager);

        if ($resultMeta0->totalCount > 0)
        {
            
            if (isset($resultMeta0->{'objects'}[0]->{'xml'}))
            {
                try
                {
                    $xml = simplexml_load_string($resultMeta0->{'objects'}[0]->{'xml'});
                    
                    
                    //$xml=str_replace("<GeoBlockRule>","<STRINGGeo_Block_Rule>",$resultMeta0->{'objects'}[0]->{'xml'});
                    //$xml=str_replace("</GeoBlockRule>","</STRINGGeo_Block_Rule>",$xml);
                    //$xml = simplexml_load_string($xml);
                    
                    $xml->OTTTAGConcurrency_Group = $concurrencyGroup;
                    $metadataId = $resultMeta0->{'objects'}[0]->id;

                    # update metadata
                    $result = $client
                        ->metadata
                        ->update($metadataId, $xml->asXML());
                    if (isset($result->id))
                    {
                        $line = "\n OTTTAGConcurrency_Group updated for $row";
                        echo ($line);
                        fwrite($fout, $line);

                    }
                }
                catch(Exception $ex)
                {
                    $line = "\n Error thrown= $ex";
                    echo ($line);
                    fwrite($fout, $line);

                }
            }
        }
    }
    $line = "\nConcurrency Group updated for $row";
    echo ($line);
    fwrite($fout, $line);
}
else
{

    fwrite($fout, "File not found");
}

?>
