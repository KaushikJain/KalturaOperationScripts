<?php
//USAGE php DeleteSourceByEntryId.php partnerId secret entryId


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
        $line = "\n Getting flavorAssets for $row";
        echo ($line);
        fwrite($fout, $line);

        $filter = new KalturaAssetFilter();
        $filter->entryIdEqual = $row;
        $filter->tagsLike = 'source';
        $pager = new KalturaFilterPager();

        try
        {
            $result = $client
                ->flavorAsset
                ->listAction($filter, $pager);

            $foo = false;

            for ($i = 0;$i <= (sizeof($result->objects) - 1);$i++)
            {

                if ($result->objects[$i]->flavorParamsId == 0) //addtional check to ensure that we are deleting only source param
                
                {

                    $foo = true;
                    $flavorAssetId = $result->objects[$i]->id;
                    $flavorAssetTags = $result->objects[$i]->tags;

                    $faDeleteResult = $client
                        ->flavorAsset
                        ->delete($flavorAssetId);

                    $line = "\n Deleted $flavorAssetId with $flavorAssetTags for $row";
                    echo ($line);
                    fwrite($fout, $line);
                }

            }

            if ($foo == false)
            {
                $line = "\n Source Flavor not found for $row";
                echo ($line);
                fwrite($fout, $line);
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

?>
