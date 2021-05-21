<?php
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

$foutLog = fopen('RedistributeLastUpdatedAssets.log', 'a');

$syncDuration = $argv[1];
$kmcPartnerId = $argv[2];
$kmcSecretKey = $argv[3];

$config = new KalturaConfiguration($kmcPartnerId);
$config->serviceUrl = 'http://www.kaltura.com/';
$client = new KalturaClient($config);
$ks = $client
    ->session
    ->start($kmcSecretKey, null, KalturaSessionType::ADMIN, $kmcPartnerId, null, null);
$client->setKs($ks);

echo ("Starting Execution at " . (new DateTime())->format('Y-m-d H:i:s'));

if ($syncDuration && $kmcPartnerId && $kmcSecretKey)
{
    echo ("\nRequest Recevied. Executing now with syncDuration=" . $syncDuration . ", kmcPartnerId=" . $kmcPartnerId . ", kmcSecretKey=" . $kmcSecretKey);

    $updateTime = ((new DateTime())->modify('-' . $syncDuration . ' minutes'))->format('U');
    echo ("\n\nUpdate Date to be used=" . $updateTime);

    $filter = new KalturaMediaEntryFilter();
    $filter->updatedAtGreaterThanOrEqual = $updateTime;
    $pager = new KalturaFilterPager();
    $pager->pageSize = 1;
    $pager->pageIndex = 1;
    $result = $client
        ->media
        ->listAction($filter, $pager);

    if (($result->totalCount) > 0)
    {

        $line = "\nTotal Count" . $result->totalCount . "";
        echo ($line);
        fwrite($foutLog, $line);

        $lastRunFileExists = false;
        if (file_exists("LastDistributedEntries"))
        {
            $lastRunFileExists = true;
            $line = "\nLast Distributed File Exists";
            echo ($line);
            fwrite($foutLog, $line);
        }
        else
        {

            $line = "\nLast Run File Doesnt Exist, Please create empty File with the name LastDistributedEntries";
            echo ($line);
            fwrite($foutLog, $line);
        }

        $line = "\nPage Size=100, Total Pages " . ceil(($result->totalCount) / 100);

        $entryIdList = "";
        if ($lastRunFileExists == true)
        {
            for ($x = 1;$x <= ceil(($result->totalCount) / 100);$x++)
            {

                $filter = new KalturaMediaEntryFilter();
                $filter->updatedAtGreaterThanOrEqual = $updateTime;
                $pager = new KalturaFilterPager();
                $pager->pageSize = 100;
                $pager->pageIndex = $x;
                $result = $client
                    ->media
                    ->listAction($filter, $pager);

                for ($i = 0;$i <= (sizeof($result->objects) - 1);$i++)
                {

                    $entryID = $result->objects[$i]->id;
                    $lastRunEntryFound = false;

                    $lastRunFile = file("LastDistributedEntries");
                    $lastRunEntries = array_map('trim', $lastRunFile);

                    if (sizeof($lastRunEntries) > 0)
                    {

                        foreach ($lastRunEntries as $lastRunEntry)
                        {
                            if ($lastRunEntry == $entryID)
                            {
                                $line = "\n$entryID was disrbuted in last run, so skipping it";
                                echo ($line);
                                fwrite($foutLog, $line);
                                $lastRunEntryFound = true;
                                continue;
                            }

                        }
                    }

                    if ($lastRunEntryFound == false)
                    {

                        $filter1 = new KalturaEntryDistributionFilter();
                        $filter1->entryIdEqual = $entryID;
                        $pager1 = null;
                        $contentdistributionPlugin = KalturaContentdistributionClientPlugin::get($client);
                        $foundEntries1 = $contentdistributionPlugin
                            ->entryDistribution
                            ->listAction($filter1, $pager);
                        if ($foundEntries1->totalCount > 0)
                        {
                            $entryDist = $foundEntries1->objects[0];

                        }
                        else
                        {
                            $line = "\n Entry Distribution not found for $entryID  ";
                            echo ($line);
                            fwrite($foutLog, $line);
                        }

                        $entryDistId = $entryDist->id;
                        $contentdistributionPlugin = KalturaContentdistributionClientPlugin::get($client);
                        try
                        {
                            $contentdistributionPlugin
                                ->entryDistribution
                                ->submitUpdate($entryDistId);

                            $line = "\n Submit Update for $entryID";
                            echo ($line);
                            fwrite($foutLog, $line);
                        }
                        catch(Exception $ex)
                        {
                            $line = "\n Entry Distribution failed $entryID ... Retrying..";
                            echo ($line);
                            fwrite($foutLog, $line);
                            $contentdistributionPlugin
                                ->entryDistribution
                                ->retrySubmit($entryDistId);
                        }

                        $entryIdList .= "$entryID\n";

                    }

                }

                $line = "\nPage Number" . $x . " Done";
                echo ($line);
                fwrite($foutLog, $line);

            }
        }

        $foutLastDistributedAssets = fopen('LastDistributedEntries', 'w');
        fwrite($foutLastDistributedAssets, $entryIdList);

    }
    else
    {
        $line = "\nNo Entries Found For Distribution";
        echo ($line);
        fwrite($foutLog, $line);
    }

}
else
{
    echo ("Please Enter All 3 required params : syncDuration, kmcPartnerId kmcSecretKey");
}

echo ("\nFinished Execution at " . (new DateTime())->format('Y-m-d H:i:s') . "\n");

?>
