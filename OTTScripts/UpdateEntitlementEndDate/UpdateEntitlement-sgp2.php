<?php

//Usage php UpdateEntitlement.php adminKs path/to/dataFile
//In Data file each entitlement update should be in the new line and each field should be seperated by a comma
// Format for each line is userID,subsID,epochEndTime,purchaseID


var_dump($argv);
var_dump($argc);

$adminKS = $argv[1];
$dataList = $argv[2];

$fout = fopen('UpdateEntitlement.log', 'a');

if (file_exists($dataList))
{
    $line = "File exists. Processing now";
    echo ($line);
    fwrite($fout, $line);

    $userDataF = file($dataList);
    $srcRows = array_map('trim', $userDataF);

    $line = "\nCOUNT = ";
    echo ($line);
    fwrite($fout, $line);

    $line = sizeof($srcRows);
    echo ($line);
    fwrite($fout, $line);

    foreach ($srcRows as $row)
    {
        $arrfields = explode(',', $row);

        $userID = $arrfields[0];
        $subsID = $arrfields[1];
        $epochEndTime = $arrfields[2];
        $purchaseID = $arrfields[3];

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://rest-as.ott.kaltura.com/api_v3/service/entitlement/action/update',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => "{
                \"ks\": \"" . $adminKS . "\",
                \"userId\": \"" . $userID . "\",
                \"version\": \"5.8.0\",
                \"entitlement\": {
                    \"endDate\": \"" . $epochEndTime . "\",
                    \"objectType\": \"KalturaSubscriptionEntitlement\",
                    \"productId\": \"" . $subsID . "\"
                },
                \"id\": \"" . $purchaseID . "\"
            }",
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ) ,
        ));

        $response = curl_exec($curl);

        curl_close($curl);

        if ($err)
        {
            $line = "cURL Error while executing /entitlement/action/update #:" . $err;
            echo ($line);
            fwrite($fout, $line);
        }
        else
        {
            $line = "Response for /entitlement/action/update for $userID == $response";
            echo ($line);
            fwrite($fout, $line);

        }
    }

}

