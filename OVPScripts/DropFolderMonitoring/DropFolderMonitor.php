<?php

//USAGE php RedistributeAssets.php partnerId partnerSecret /Users/kaushik.jain/Downloads/entryId
//Last Param is file location


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

$fout = fopen('DropFolderMonitor.log', 'a');

$partnerId        = $argv[1]; //
$adminSecret      = $argv[2];
$dropFolderId = $argv[3]; 


$config             = new KalturaConfiguration($partnerId);
$config->serviceUrl = 'http://www.kaltura.com/';
$client             = new KalturaClient($config);
$ks                 = $client->session->start($adminSecret, null, KalturaSessionType::ADMIN, $partnerId, null, null);
$client->setKs($ks);



if ($dropFolderId!=null) {
   
    $objectType="KalturaDropFolderFilter";

    $curl = curl_init();

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HEADER, 1);

    //echo($ks );

        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://www.kaltura.com/api_v3/service/dropfolder_dropfolder/action/list",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => "ks=".$ks."&filter%5BidEqual%5D=".$dropFolderId."&filter%5BobjectType%5D=KalturaDropFolderFilter",
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/x-www-form-urlencoded'
          ),
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);

        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);
        $results = new SimpleXMLElement($body);
        

        $totalCount=$results->result[0]->totalCount;
        
       
        if ($totalCount!=1)
        {

            $line = "Total Count = ".$totalCount.",So we have a issue\n";
            echo ($line);
            fwrite($fout, $line);

            $line = "As count is Zero headers= ".$header."\n";
            echo ($line);
            fwrite($fout, $line);
            $encode=urlencode($header);


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
                        CURLOPT_POSTFIELDS => "{\"text\":\" $dropFolderId not found. Here are the headers $encode \"}",
                        CURLOPT_HTTPHEADER => array(
                            'Content-type: application/json'
                        ) ,
                    ));

                    $response = curl_exec($curl);

                    curl_close($curl);
                    echo (" Sent Notification to slack. Response from slack " . $response);


        }else {

            $line = "Total Count = ".$totalCount.",So all good\n";
            echo ($line);
            fwrite($fout, $line);

        }

       } else {
    
    fwrite($fout, "dropFolderId not found");
}


?>