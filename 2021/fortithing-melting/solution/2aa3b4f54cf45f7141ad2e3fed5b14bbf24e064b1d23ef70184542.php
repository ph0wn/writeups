<?php

$challenge = '3824A6FE2C828';
$bias = 1538-22-1;
$seed = '6aaead1d9f5774a275375b5b86fa3c97';
$flag = "ph0wn{da_bI4s_s33m5_TOO_looooow_isnt_1T?}";
if(!isset($_GET['seed']) || $seed != $_GET['seed']){
  die("Who are you?");
}
$data = ["bias" => $bias, "challenge" => $challenge, "flag" => $flag];

header('Content-type: application/json');
echo json_encode($data);

?>
