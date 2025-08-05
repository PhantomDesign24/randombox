<?php
/*
 * 파일명: daangn_proxy.php
 * 위치: /daangn_proxy.php
 * 기능: 당근마켓 API CORS 프록시
 * 작성일: 2025-01-28
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$region_id = isset($_GET['region_id']) ? intval($_GET['region_id']) : 0;

if ($region_id <= 0) {
    echo json_encode(['error' => 'Invalid region ID']);
    exit;
}

$url = "https://www.daangn.com/kr/buy-sell/?in={$region_id}&_data=routes%2Fkr.buy-sell._index";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    'Accept: application/json',
    'Accept-Language: ko-KR,ko;q=0.9',
    'Referer: https://www.daangn.com/'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    echo $response;
} else {
    echo json_encode([
        'error' => 'HTTP Error',
        'code' => $http_code
    ]);
}
?>