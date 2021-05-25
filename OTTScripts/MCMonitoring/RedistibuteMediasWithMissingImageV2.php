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

$partnerId = $argv[1]; //
$syncDuration = $argv[2];
$kmcPartnerId = $argv[3];
$kmcSecretKey = $argv[4];

$config = new KalturaConfiguration($partnerId);
$config->serviceUrl = 'http://www.kaltura.com/';
$client = new KalturaClient($config);
$ks = $client
    ->session
    ->start($kmcSecretKey, null, KalturaSessionType::ADMIN, $kmcPartnerId, null, null);
$client->setKs($ks);

echo ("Starting Execution at " . (new DateTime())->format('Y-m-d H:i:s'));

if ($partnerId && $syncDuration && $kmcPartnerId && $kmcSecretKey)
{
    echo ("\nRequest Recevied. Executing now with partnerId=" . $partnerId . ", syncDuration=" . $syncDuration . ", kmcPartnerId=" . $kmcPartnerId . ", kmcSecretKey=" . $kmcSecretKey);

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://rest-as.ott.kaltura.com/api_v3/service/OTTUser/action/anonymousLogin',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => "{
	\"partnerId\" : \"" . $partnerId . "\"
}
",
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ) ,
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    $anonKsJson = json_decode($response);
    $ks = $anonKsJson->{'result'}->{'ks'};

    $updateTime = ((new DateTime())->modify('-' . $syncDuration . ' minutes'))->format('U');
    echo ("\n\nUpdate Date to be used=" . $updateTime);

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://rest-as.ott.kaltura.com/api_v3/service/asset/action/list',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => "{
    \"apiVersion\": \"5.2.8\",
    \"ks\": \"" . $ks . "\",
    \"filter\": {
        \"objectType\": \"KalturaSearchAssetFilter\",
        \"kSql\": \"(and update_date>\'$updateTime\' asset_type=\'media\')\"
    },
    \"pager\": {
        \"objectType\": \"KalturaFilterPager\",
        \"pageSize\": 500,
        \"pageIndex\": 1
    }
}",
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ) ,
    ));

    $response = curl_exec($curl);

    curl_close($curl);

    $assetListResponse = json_decode($response);
    $totalCount = $assetListResponse->{'result'}->{'totalCount'};

    echo ("\n\nTotalCount=" . $totalCount);
    if ($totalCount > 0)
    {
        $mediaAssetObjects = $assetListResponse->{'result'}->{'objects'};

        echo ("\nMediaAssetObjectCount=" . count($mediaAssetObjects)); // This may be lesser than the Total count because of pagination. here we are not taking into account pagination
        if (sizeof($mediaAssetObjects) > 0)
        {
            $emptyImagelist = [];

            foreach ($mediaAssetObjects as & $mediaAsset)
            {

                echo ("\nChecking for Media =" . $mediaId = $mediaAsset->{'id'} . "...");
                $imageList = $mediaAsset->{'images'};
                if ($mediaAsset->{'entryId'} && sizeof($imageList) == 0)
                {
                    echo ("Images are empty for media " . $mediaAsset->{'id'});
                    $emptyImagelist[$mediaId = $mediaAsset->{'id'}] = $mediaAsset->{'entryId'};
                }
                else
                {
                    echo ("Images found for the media " . $mediaAsset->{'id'});
                }
            }

            if (sizeof($emptyImagelist) > 0)
            {

                echo ("\n\nEmpty Images found for total " . count($emptyImagelist) . " medias. Re-distributing now\n");
                $distributionCounter = 0;
                foreach ($emptyImagelist as $key => $value)
                {

                    $line = "\nGetting Entry and checking Image Status $key - $value  ";
                    echo ($line);

                    $version = null;
                    $result = $client
                        ->media
                        ->get($value, $version);

                    $line = "\nImage Url for $key - $value is $result->thumbnailUrl ";
                    echo ($line);

                    $array = get_headers($result->thumbnailUrl);
                    $string = $array[0];
                    if (strpos($string, "200"))
                    {
                        $line = "\nImage Url is returning 200";
                        echo ($line);
                    }
                    else
                    {
                        $line = "\nImage Url is not returning 200 and hence not distributing and skipping this media\n";
                        echo ($line);
                        continue;
                    }

                    $filter = new KalturaEntryDistributionFilter();
                    $filter->entryIdEqual = $value;
                    $pager = null;
                    $contentdistributionPlugin = KalturaContentdistributionClientPlugin::get($client);
                    $foundEntries = $contentdistributionPlugin
                        ->entryDistribution
                        ->listAction($filter, $pager);
                    if ($foundEntries->totalCount > 0)
                    {
                        $entry = $foundEntries->objects[0];
                        //var_dump($entry);
                        
                    }
                    else
                    {
                        $line = "\n Entry Distribution not found for $key - $value  ";
                        echo ($line);

                    }

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
                        $line = "\n Entry Distribution failed $key - $value ... Retrying..";
                        echo ($line);

                        $contentdistributionPlugin
                            ->entryDistribution
                            ->retrySubmit($entryDistId);
                    }
                    $distributionCounter++;
                    $line = "\nFinished Re-distributing  $key - $value...";
                    echo ($line);

                    $curl = curl_init();

                    curl_setopt_array($curl, array(
                        CURLOPT_URL => 'https://hooks.slack.com/services/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',
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => '',
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 0,
                        CURLOPT_FOLLOWLOCATION => true,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => 'POST',
                        CURLOPT_POSTFIELDS => "{\"text\":\"Images were missing for $key - $value. Triggered the redistribution. \"}",
                        CURLOPT_HTTPHEADER => array(
                            'Content-type: application/json'
                        ) ,
                    ));

                    $response = curl_exec($curl);

                    curl_close($curl);
                    echo ("\nSent Notification to slack. Response from slack " . $response . "\n");

                }
                $distributedMediaIds = implode(",", array_keys($emptyImagelist));
                echo ("\n\nDistribution done for  " . $distributionCounter . " medias. For " . $distributedMediaIds);

            }
            else
            {
                echo ("\n\nAll medias have Images");
            }

        }
        else
        {
            echo ("\nNo New Media Updates found in last " . $syncDuration . " Minutes");
        }
    }
    else
    {
        echo ("\nNo New Media Updates found in last " . $syncDuration . " Minutes");
    }
}
else
{
    echo ("Please Enter All 4 required params : partnerId, syncDuration, kmcPartnerId kmcSecretKey");
}

echo ("\nFinished Execution at " . (new DateTime())->format('Y-m-d H:i:s') . "\n");

