<?php

//php getAllAssetsOVP.php partnerId Secret 1581724800 1598918400 batch6.csv
//$argv[1]=partnerId
//$argv[2]=adminSecret
//$argv[3]=createdAtGreaterThanOrEqual - criteria to restrict the results to 10000
//$argv[4]=createdAtLessThanOrEqual- criteria to restrict the results to 10000
//$argv[5]=fileName - filename that will be generated;


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

$partnerId = $argv[1];
$adminSecret = $argv[2];
$createdAtGreaterThanOrEqual = $argv[3];
$createdAtLessThanOrEqual = $argv[4];
$fileName = $argv[5];

$foutLog = fopen('getAllAssetsOVP.log', 'a');
$foutData = fopen($fileName, 'w');

$config = new KalturaConfiguration($partnerId);
$config->serviceUrl = 'http://www.kaltura.com/';
$client = new KalturaClient($config);
$ks = $client
    ->session
    ->start($adminSecret, null, KalturaSessionType::ADMIN, $partnerId, null, null);
$client->setKs($ks);


$line = "\nKS" . $ks . "";
    echo ($line);
    fwrite($foutLog, $line);

$filter = new KalturaMediaEntryFilter();
$filter->createdAtGreaterThanOrEqual = $createdAtGreaterThanOrEqual;
$filter->createdAtLessThanOrEqual = $createdAtLessThanOrEqual;
$pager = new KalturaFilterPager();
$pager->pageSize = 1;
$pager->pageIndex = 1;
$result = $client
    ->media
    ->listAction($filter, $pager);
var_dump($result);

if (($result->totalCount) > 0)
{
    $line = "\nTotal Count" . $result->totalCount . "";
    echo ($line);
    fwrite($foutLog, $line);

    $line = "\nPage Size=100, Total Pages " . ceil(($result->totalCount) / 100);

    for ($x = 1;$x <= ceil(($result->totalCount) / 100);$x++)
    {

        $filter = new KalturaMediaEntryFilter();
        $filter->createdAtGreaterThanOrEqual = $createdAtGreaterThanOrEqual;
        $filter->createdAtLessThanOrEqual = $createdAtLessThanOrEqual;
        $pager = new KalturaFilterPager();
        $pager->pageSize = 100;
        $pager->pageIndex = $x;
        $result = $client
            ->media
            ->listAction($filter, $pager);

        for ($i = 0;$i <= (sizeof($result->objects) - 1);$i++)
        {
            $line = "\n";
            echo ($line);
            fwrite($foutLog, $line);

            $dataLine = $result->objects[$i]->id . "," . $result->objects[$i]->referenceId . "," . $result->objects[$i]->status . "," . $result->objects[$i]->name . "," . $result->objects[$i]->endDate;

            echo ($dataLine . "\n");
            fwrite($foutData, $dataLine . "\n");

            echo ($dataLine);
            fwrite($foutLog, $dataLine);
        }
        $line = "\nPage Number" . $x;
        echo ($line);
        fwrite($foutLog, $line);

    }
}

?>
