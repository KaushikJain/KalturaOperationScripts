<?php
//USAGE php RedistributeAssetsWithDistributionProfile.php partnerId partnerSecret /Users/kaushik.jain/Downloads/entryId distributionProfileId
//second-Last Param is file location
//last param is distributionProfileId
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

$fout = fopen('RedistributeAssetsWithDistributionProfile.log', 'a');

$partnerId = $argv[1]; //
$adminSecret = $argv[2];
$mediaEntryIdList = $argv[3]; //'$SeriesEntryListFN';
$distributionProfileId = $argv[4];

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
        $line = "\n Starting Redistribution for $row";
        echo ($line);
        fwrite($fout, $line);

        $filter = new KalturaEntryDistributionFilter();
        $filter->entryIdEqual = $row;
        $pager = null;
        $contentdistributionPlugin = KalturaContentdistributionClientPlugin::get($client);
        $filter->distributionProfileIdEqual = $distributionProfileId;
        $foundEntries = $contentdistributionPlugin
            ->entryDistribution
            ->listAction($filter, $pager);
        if ($foundEntries->totalCount > 0)
        {
            $line = "\n Distribution Found, So just doing a EntryDistribution.submitUpdate  $row ";
            echo ($line);
            fwrite($fout, $line);

            $entry = $foundEntries->objects[0];

            $entryDistId = $entry->id;
            $contentdistributionPlugin = KalturaContentdistributionClientPlugin::get($client);
            try
            {
                $contentdistributionPlugin
                    ->entryDistribution
                    ->submitUpdate($entryDistId);
            }
            catch(Exception $ex)
            {
                $line = "\n Entry Distribution failed $row ... Retrying..";
                echo ($line);
                fwrite($fout, $line);
                $contentdistributionPlugin
                    ->entryDistribution
                    ->retrySubmit($entryDistId);
            }
        }
        else
        {
            $line = "\n Entry Distribution not found for $row, So Doing EntryDistribution.add and EntryDistribution.submitAdd ";
            echo ($line);
            fwrite($fout, $line);

            try
            {

                $entryDistribution = new KalturaEntryDistribution();
                $entryDistribution->distributionProfileId = $distributionProfileId;
                $contentdistributionPlugin = KalturaContentdistributionClientPlugin::get($client);
                $entryDistribution->entryId = $row;
                $entry = $contentdistributionPlugin
                    ->entryDistribution
                    ->add($entryDistribution);
                $result = $contentdistributionPlugin
                    ->entryDistribution
                    ->submitAdd($entry->id);

            }
            catch(Exception $ex)
            {
                $line = "\n Entry Add Distribution failed $row ... Retrying..";
                echo ($line);
                fwrite($fout, $line);
                $contentdistributionPlugin
                    ->entryDistribution
                    ->retrySubmit($entryDistId);

            }

        }

    }
    $line = "\nFinished Working on the entry $row \n";
    echo ($line);
    fwrite($fout, $line);
}
else
{

    fwrite($fout, "File not found");
}

?>
