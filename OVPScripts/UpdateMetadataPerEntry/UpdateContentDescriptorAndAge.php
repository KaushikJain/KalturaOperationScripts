<?php
//php UpdateContentDescriptorAndAge.php partnerId secret entryData
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

$fout = fopen('UpdateContentDescriptorAndAge.log', 'a');

$partnerId = $argv[1]; //
$adminSecret = $argv[2];
$mediaEntryIdList = $argv[3]; //'$entryIdList';


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
        $line = "\n Updating metadata for $row";
        echo ($line);
        fwrite($fout, $line);

        $arrfields = explode('|', $row);

        $row = $arrfields[0];
        $contentDescriptor = $arrfields[1];
        $age = $arrfields[2];

        $line = "\n Updating entry=$row with ContentDescriptor=$contentDescriptor and Age=$age";
        echo ($line);
        fwrite($fout, $line);

        $filter = new KalturaMetadataFilter();
        $filter->objectIdIn = $row;
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
                    
                    //THis is done as sometimes STRINGContentDescriptor is not even present and Order is very imp when distributing. 
                    if (strpos($resultMeta0->{'objects'}[0]->{'xml'}, 'STRINGContentDescriptor') == false)
                    {
                        if (strpos($resultMeta0->{'objects'}[0]->{'xml'}, '<OTTTAGAssetMasterID/>') == true)
                        {
                           
                            echo "\nReplacing <OTTTAGAssetMasterID/>";
                            $xml = str_replace("<OTTTAGAssetMasterID/>", "<STRINGContentDescriptor>$contentDescriptor</STRINGContentDescriptor>\n<OTTTAGAssetMasterID/>", $resultMeta0->{'objects'}[0]->{'xml'});
                            $xml = simplexml_load_string($xml);
                        }
                        else if (strpos($resultMeta0->{'objects'}[0]->{'xml'}, '<OTTTAGAssetMasterID>') == true)
                        {
                            echo $xml;
                            echo "\n Replacing <OTTTAGAssetMasterID>";
                            $xml = str_replace("<OTTTAGAssetMasterID>", "<STRINGContentDescriptor>$contentDescriptor</STRINGContentDescriptor>\n<OTTTAGAssetMasterID>", $resultMeta0->{'objects'}[0]->{'xml'});
                            $xml = simplexml_load_string($xml);
                        }

                    }
                    else
                    {
                        $xml->STRINGContentDescriptor = $contentDescriptor;
                    }

                    $xml->OTTTAGAge = $age;

                    $metadataId = $resultMeta0->{'objects'}[0]->id;

                    # update metadata
                    $result = $client
                        ->metadata
                        ->update($metadataId, $xml->asXML());
                    if (isset($result->id))
                    {
                        $line = "\n Metadata Updated for $row";
                        echo ($line);
                        fwrite($fout, $line);

                    }
                }
                catch(Exception $ex)
                {
                    $line = "\n Metadata Updated for $row";
                    echo ($line);
                    fwrite($fout, $line);

                }
            }
        }

    }
    $line = "\nFinished Updating Metadata";
    echo ($line);
    fwrite($fout, $line);
}
else
{

    fwrite($fout, "File not found");
}

?>
