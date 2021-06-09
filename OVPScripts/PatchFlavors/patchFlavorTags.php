<?php
//USAGE php patchFlavorTags.php 2082301 12fbaaab0f601f19a796770946797c41 entryId


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

$fout = fopen('DeleteAssets.log', 'a');

$partnerId = $argv[1]; //
$adminSecret = $argv[2];
$mediaEntryIdList = $argv[3]; //'$SeriesEntryListFN';
$flavorTags = $argv[4];

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

    if (file_exists($flavorTags))
    {
        $flavorParam = file($flavorTags);
        $flavorParamRows = array_map('trim', $flavorParam);

        $line = "\nflavorParamRows Count = ";
        echo ($line);
        fwrite($fout, $line);

        $line = sizeof($flavorParamRows);
        echo ($line);
        fwrite($fout, $line);

        foreach ($srcEntryRows as $row)
        {
            $line = "\n Getting flavorAssets for $row";
            echo ($line);
            fwrite($fout, $line);

            $filter = new KalturaAssetFilter();
            $filter->entryIdIn = $row;
            $pager = new KalturaFilterPager();

            try
            {
                $result = $client
                    ->flavorAsset
                    ->listAction($filter, $pager);

                foreach ($flavorParamRows as $flavorParamRow)
                {

                    $arrfields = explode(';', $flavorParamRow);

                    $flavrorParamId = $arrfields[0];
                    $tags = $arrfields[1];

                    $line = "\n Check for FlavorParamID $flavrorParamId = $tags";
                    echo ($line);
                    fwrite($fout, $line);

                    for ($i = 0;$i <= (sizeof($result->objects) - 1);$i++)
                    {

                        if ($flavrorParamId == $result->objects[$i]->flavorParamsId)
                        {

                           
                            $flavorAssetId= $result->objects[$i]->id;

                            
                            $flavorAsset = new KalturaFlavorAsset();
                            $flavorAsset->tags = $tags;

                            $faUpdateResult = $client->flavorAsset->update($flavorAssetId, $flavorAsset);
                            
                            $line = "\n Updated $flavorAssetId with $tags for $row";
                            echo ($line);
                            fwrite($fout, $line);
                            


                        }

                    }
                }

            }
            catch(Exception $e)
            {
                echo $e->getMessage();
            }

            $line = "\n Finished Operating  $row ";
            echo ($line);
            fwrite($fout, $line);

        }
        $line = "\nFinished Deleting";
        echo ($line);
        fwrite($fout, $line);
    }
    else
    {
        fwrite($fout, "File not found");
    }
}
else
{
    fwrite($fout, "File not found");
}

?>
