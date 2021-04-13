<?php
// Usage php addRoleIds.php <AdminKS> userIdTest 2
// This script will add RoleIds (3rd param) to the user userId. 

var_dump($argv);
var_dump($argc);

$ks = $argv[1]; // Admin Ks 
$userIdList = $argv[2]; // List of userId with each user on the new Line 
$role_id = $argv[3]; // RoleIds to be assigned to the user. 


if (file_exists($userIdList)) {
    
    echo ("File exists. Processing now");

    $userIdF     = file($userIdList);
    $userIdRows = array_map('trim', $userIdF);

    foreach ($userIdRows as $userId) {
        $line = "\n Updating roles for $userId";
        echo ($line);
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://rest-as.ott.kaltura.com/api_v3/service/OTTUser/action/addRole",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => "{\n    \"ks\": \"" . $ks . "\",\n    \"user_id\": \"" . $userId . "\",\n    \"role_id\": \"" . $role_id . "\"   }\n}",
          CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
            "Postman-Token: c3046a6a-b87f-4c40-b985-9ad8696aede7",
            "cache-control: no-cache"
          ),
        ));
        
        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        
        if ($err) {
          echo "cURL Error #:" . $err;
        } else {
          echo $response;
        }
        
    }
}else{
  echo ("File Not found.");
}


