<?php
/*
 * 파일명: daangn_api_proxy.php
 * 위치: /daangn_api_proxy.php
 * 기능: 당근마켓 API 프록시 (CORS 및 JSON 문제 해결)
 * 작성일: 2025-01-28
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

// 파라미터 받기
$region_id = isset($_GET['region_id']) ? intval($_GET['region_id']) : 0;
$search_keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';

if ($region_id <= 0 || empty($search_keyword)) {
    echo json_encode([
        'error' => true,
        'message' => '필수 파라미터가 누락되었습니다.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 당근마켓 URL 구성
$url = "https://www.daangn.com/kr/buy-sell/?in={$region_id}&search=" . urlencode($search_keyword) . "&_data=routes%2Fkr.buy-sell._index";

// cURL 설정
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
    'Accept: application/json, text/plain, */*',
    'Accept-Language: ko-KR,ko;q=0.9,en;q=0.8',
    'Accept-Encoding: gzip, deflate, br',
    'Referer: https://www.daangn.com/buy-sell/',
    'X-Requested-With: XMLHttpRequest',
    'sec-ch-ua: "Not_A Brand";v="8", "Chromium";v="120"',
    'sec-ch-ua-mobile: ?0',
    'sec-ch-ua-platform: "Windows"',
    'Sec-Fetch-Dest: empty',
    'Sec-Fetch-Mode: cors',
    'Sec-Fetch-Site: same-origin'
]);
curl_setopt($ch, CURLOPT_ENCODING, ''); // 자동 압축 해제

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
$info = curl_getinfo($ch);
curl_close($ch);

// 디버깅 정보
if ($error) {
    echo json_encode([
        'error' => true,
        'message' => 'cURL 오류',
        'curl_error' => $error,
        'url' => $url
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($http_code !== 200) {
    echo json_encode([
        'error' => true,
        'message' => 'HTTP 오류',
        'http_code' => $http_code,
        'url' => $url,
        'response_preview' => substr($response, 0, 500)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// HTML 응답 체크
if (strpos($response, '<!DOCTYPE') !== false || strpos($response, '<html') !== false) {
    // HTML이 반환된 경우 - 다른 방법 시도
    echo json_encode([
        'error' => true,
        'message' => 'HTML 응답이 반환되었습니다. API 형식이 변경되었을 수 있습니다.',
        'response_type' => 'html',
        'response_preview' => substr(strip_tags($response), 0, 500)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// JSON 파싱 시도
$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'error' => true,
        'message' => 'JSON 파싱 오류',
        'json_error' => json_last_error_msg(),
        'response_preview' => substr($response, 0, 500)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 성공적으로 데이터를 받은 경우
echo json_encode([
    'error' => false,
    'data' => $data,
    'debug' => [
        'http_code' => $http_code,
        'content_type' => $info['content_type'] ?? '',
        'url' => $url
    ]
], JSON_UNESCAPED_UNICODE);
?>