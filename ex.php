<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>당근마켓 지역 ID 고속 수집기</title>
    <!-- Bootstrap CSS 비동기 로드 -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"></noscript>
    
    <!-- Bootstrap Icons 비동기 로드 -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css"></noscript>
    
    <style>
        /* ===================================
         * 전역 스타일
         * ===================================
         */
        /* 기본 스타일 */
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8f9fa;
            padding: 20px 0;
        }
        
        /* ===================================
         * 컨테이너 스타일
         * ===================================
         */
        /* 메인 컨테이너 */
        .daangn-main-container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        
        /* 헤더 영역 */
        .daangn-header-section {
            background: linear-gradient(135deg, #ff6f0f 0%, #ff8c3a 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .daangn-header-section h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
        }
        
        .daangn-header-section p {
            margin: 10px 0 0 0;
            opacity: 0.9;
        }
        
        /* ===================================
         * 컨트롤 패널 스타일
         * ===================================
         */
        /* 컨트롤 패널 컨테이너 */
        .daangn-control-panel {
            padding: 30px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        
        /* 설정 그룹 */
        .daangn-setting-group {
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        /* 입력 그룹 스타일 */
        .daangn-input-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .daangn-input-wrapper label {
            font-weight: 500;
            color: #495057;
            margin: 0;
        }
        
        /* 입력 필드 */
        .daangn-number-input {
            width: 120px;
            padding: 8px 12px;
            border: 1px solid #ced4da;
            border-radius: 6px;
            font-size: 14px;
        }
        
        /* 성능 설정 */
        .daangn-performance-settings {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-top: 15px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }
        
        /* ===================================
         * 상태 표시 스타일
         * ===================================
         */
        /* 상태 패널 */
        .daangn-status-panel {
            padding: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        /* 상태 카드 */
        .daangn-status-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #dee2e6;
        }
        
        .daangn-status-card h4 {
            margin: 0 0 15px 0;
            color: #343a40;
            font-size: 1.1rem;
        }
        
        /* 진행률 바 */
        .daangn-progress-wrapper {
            margin-bottom: 15px;
        }
        
        .daangn-progress-bar {
            height: 35px;
            background: #e9ecef;
            border-radius: 20px;
            overflow: hidden;
            position: relative;
        }
        
        .daangn-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #ff6f0f 0%, #ff8c3a 100%);
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }
        
        /* 통계 정보 */
        .daangn-stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        
        .daangn-stat-item {
            display: flex;
            justify-content: space-between;
            padding: 8px 12px;
            background: white;
            border-radius: 6px;
            font-size: 14px;
        }
        
        .daangn-stat-label {
            color: #6c757d;
        }
        
        .daangn-stat-value {
            font-weight: 600;
            color: #343a40;
        }
        
        /* ===================================
         * 로그 영역 스타일
         * ===================================
         */
        /* 로그 컨테이너 */
        .daangn-log-container {
            padding: 0 30px 30px 30px;
        }
        
        .daangn-log-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .daangn-log-area {
            background: #212529;
            color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            height: 250px;
            overflow-y: auto;
            font-family: 'Consolas', 'Monaco', monospace;
            font-size: 12px;
            line-height: 1.6;
        }
        
        /* 로그 항목 스타일 */
        .daangn-log-success {
            color: #51cf66;
        }
        
        .daangn-log-error {
            color: #ff6b6b;
        }
        
        .daangn-log-warning {
            color: #ffd43b;
        }
        
        .daangn-log-info {
            color: #74c0fc;
        }
        
        /* ===================================
         * 결과 테이블 스타일
         * ===================================
         */
        /* 결과 영역 */
        .daangn-result-section {
            padding: 30px;
            background: #f8f9fa;
        }
        
        .daangn-result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        /* 테이블 컨테이너 */
        .daangn-table-container {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
            max-height: 500px;
            overflow-y: auto;
        }
        
        /* 테이블 스타일 */
        .daangn-result-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .daangn-result-table thead {
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .daangn-result-table th {
            background: #343a40;
            color: white;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }
        
        .daangn-result-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #e9ecef;
            font-size: 14px;
        }
        
        .daangn-result-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        /* Depth별 색상 */
        .daangn-depth-1 { background: rgba(255, 111, 15, 0.1); }
        .daangn-depth-2 { background: rgba(255, 111, 15, 0.05); }
        .daangn-depth-3 { background: rgba(255, 111, 15, 0.02); }
        
        .daangn-region-id {
            font-weight: 600;
            color: #ff6f0f;
        }
        
        /* ===================================
         * 버튼 스타일
         * ===================================
         */
        /* 기본 버튼 */
        .daangn-btn {
            padding: 10px 24px;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .daangn-btn-primary {
            background: #ff6f0f;
            color: white;
        }
        
        .daangn-btn-primary:hover {
            background: #e55f00;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 111, 15, 0.3);
        }
        
        .daangn-btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .daangn-btn-secondary:hover {
            background: #5a6268;
        }
        
        .daangn-btn-success {
            background: #28a745;
            color: white;
        }
        
        .daangn-btn-success:hover {
            background: #218838;
        }
        
        .daangn-btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .daangn-btn-warning:hover {
            background: #e0a800;
        }
        
        /* ===================================
         * 유틸리티 스타일
         * ===================================
         */
        /* 스크롤바 스타일 */
        .daangn-log-area::-webkit-scrollbar,
        .daangn-table-container::-webkit-scrollbar {
            width: 8px;
        }
        
        .daangn-log-area::-webkit-scrollbar-track,
        .daangn-table-container::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        .daangn-log-area::-webkit-scrollbar-thumb,
        .daangn-table-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        
        .daangn-log-area::-webkit-scrollbar-thumb:hover,
        .daangn-table-container::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
        
        /* 체크박스 스타일 */
        .daangn-checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .daangn-checkbox-wrapper input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        /* 로딩 스피너 */
        .daangn-spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #ff6f0f;
            border-radius: 50%;
            animation: daangn-spin 1s linear infinite;
        }
        
        @keyframes daangn-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="daangn-main-container">
        <!-- ===================================
         헤더 영역
         =================================== -->
        <div class="daangn-header-section">
            <h1><i class="bi bi-basket2-fill"></i> 당근마켓 지역 ID 고속 수집기</h1>
            <p>병렬 처리로 최대 10배 빠른 속도로 지역 정보를 수집합니다</p>
        </div>
        
        <!-- ===================================
         컨트롤 패널
         =================================== -->
        <div class="daangn-control-panel">
            <h3 class="mb-3"><i class="bi bi-gear-fill"></i> 수집 설정</h3>
            
            <!-- 기본 설정 -->
            <div class="daangn-setting-group">
                <div class="daangn-input-wrapper">
                    <label for="startId">시작 ID:</label>
                    <input type="number" id="startId" class="daangn-number-input" value="1" min="1">
                </div>
                
                <div class="daangn-input-wrapper">
                    <label for="endId">종료 ID:</label>
                    <input type="number" id="endId" class="daangn-number-input" value="1000" min="1">
                </div>
                
                <div class="daangn-checkbox-wrapper">
                    <input type="checkbox" id="testMode">
                    <label for="testMode">테스트 모드 (100개만)</label>
                </div>
            </div>
            
            <!-- 성능 설정 -->
            <div class="daangn-performance-settings">
                <i class="bi bi-speedometer2"></i>
                <label>동시 요청 수:</label>
                <select id="concurrentRequests" class="form-select" style="width: auto;">
                    <option value="5">5개 (안정적)</option>
                    <option value="10" selected>10개 (권장)</option>
                    <option value="20">20개 (빠름)</option>
                    <option value="50">50개 (매우 빠름)</option>
                </select>
                
                <label class="ms-3">딜레이:</label>
                <select id="delayTime" class="form-select" style="width: auto;">
                    <option value="0">없음</option>
                    <option value="50">50ms</option>
                    <option value="100" selected>100ms</option>
                    <option value="200">200ms</option>
                </select>
            </div>
            
            <!-- 버튼 그룹 -->
            <div class="mt-3">
                <button class="daangn-btn daangn-btn-primary" onclick="startCrawling()">
                    <i class="bi bi-play-fill"></i> 수집 시작
                </button>
                <button class="daangn-btn daangn-btn-secondary" onclick="stopCrawling()">
                    <i class="bi bi-stop-fill"></i> 중지
                </button>
                <button class="daangn-btn daangn-btn-warning" onclick="clearData()">
                    <i class="bi bi-trash"></i> 초기화
                </button>
                <button class="daangn-btn daangn-btn-success" onclick="exportData()">
                    <i class="bi bi-download"></i> 데이터 내보내기
                </button>
            </div>
        </div>
        
        <!-- ===================================
         상태 표시 영역
         =================================== -->
        <div class="daangn-status-panel">
            <!-- 진행 상태 카드 -->
            <div class="daangn-status-card">
                <h4><i class="bi bi-graph-up"></i> 진행 상태</h4>
                <div class="daangn-progress-wrapper">
                    <div class="daangn-progress-bar">
                        <div class="daangn-progress-fill" id="progressBar" style="width: 0%">0%</div>
                    </div>
                </div>
                <div class="daangn-stats-grid">
                    <div class="daangn-stat-item">
                        <span class="daangn-stat-label">진행:</span>
                        <span class="daangn-stat-value"><span id="currentId">0</span> / <span id="totalId">0</span></span>
                    </div>
                    <div class="daangn-stat-item">
                        <span class="daangn-stat-label">남은 시간:</span>
                        <span class="daangn-stat-value" id="remainingTime">-</span>
                    </div>
                </div>
            </div>
            
            <!-- 수집 통계 카드 -->
            <div class="daangn-status-card">
                <h4><i class="bi bi-bar-chart-fill"></i> 수집 통계</h4>
                <div class="daangn-stats-grid">
                    <div class="daangn-stat-item">
                        <span class="daangn-stat-label">수집 성공:</span>
                        <span class="daangn-stat-value daangn-log-success" id="collectedCount">0</span>
                    </div>
                    <div class="daangn-stat-item">
                        <span class="daangn-stat-label">수집 실패:</span>
                        <span class="daangn-stat-value daangn-log-error" id="failedCount">0</span>
                    </div>
                    <div class="daangn-stat-item">
                        <span class="daangn-stat-label">처리 속도:</span>
                        <span class="daangn-stat-value" id="processSpeed">0</span>
                    </div>
                    <div class="daangn-stat-item">
                        <span class="daangn-stat-label">성공률:</span>
                        <span class="daangn-stat-value" id="successRate">0%</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- ===================================
         로그 영역
         =================================== -->
        <div class="daangn-log-container">
            <div class="daangn-log-header">
                <h4><i class="bi bi-terminal"></i> 실시간 로그</h4>
                <button class="daangn-btn daangn-btn-secondary" onclick="clearLog()" style="padding: 6px 12px;">
                    <i class="bi bi-eraser"></i> 로그 지우기
                </button>
            </div>
            <div class="daangn-log-area" id="logArea">
                <div class="daangn-log-info">시스템 준비 완료. 수집을 시작하려면 '수집 시작' 버튼을 클릭하세요.</div>
            </div>
        </div>
        
        <!-- ===================================
         결과 테이블 영역
         =================================== -->
        <div class="daangn-result-section">
            <div class="daangn-result-header">
                <h3><i class="bi bi-table"></i> 수집된 지역 정보</h3>
                <span class="text-muted">총 <span id="tableCount">0</span>개 지역</span>
            </div>
            
            <div class="daangn-table-container">
                <table class="daangn-result-table">
                    <thead>
                        <tr>
                            <th style="width: 80px;">ID</th>
                            <th style="width: 80px;">Depth</th>
                            <th>시/도</th>
                            <th>시/군/구</th>
                            <th>동/읍/면</th>
                            <th>전체 이름</th>
                        </tr>
                    </thead>
                    <tbody id="resultBody">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        // ===================================
        // 전역 변수
        // ===================================
        let isRunning = false;
        let currentBatch = 0;
        let collectedRegions = [];
        let failedIds = [];
        let startTime = null;
        let processedCount = 0;
        
        // ===================================
        // 메인 수집 함수
        // ===================================
        async function startCrawling() {
            if (isRunning) {
                alert('이미 수집이 진행 중입니다.');
                return;
            }
            
            const startId = parseInt(document.getElementById('startId').value);
            const endId = parseInt(document.getElementById('endId').value);
            const testMode = document.getElementById('testMode').checked;
            const concurrentRequests = parseInt(document.getElementById('concurrentRequests').value);
            const delayTime = parseInt(document.getElementById('delayTime').value);
            
            if (startId >= endId) {
                alert('종료 ID는 시작 ID보다 커야 합니다.');
                return;
            }
            
            // 초기화
            isRunning = true;
            collectedRegions = [];
            failedIds = [];
            processedCount = 0;
            startTime = Date.now();
            
            const totalIds = testMode ? Math.min(100, endId - startId + 1) : (endId - startId + 1);
            const actualEndId = testMode ? Math.min(startId + 99, endId) : endId;
            
            document.getElementById('totalId').textContent = actualEndId;
            document.getElementById('currentId').textContent = startId - 1;
            
            log('수집을 시작합니다...', 'info');
            log(`설정: ${concurrentRequests}개 동시 요청, ${delayTime}ms 딜레이`, 'info');
            
            // ID 배열 생성
            const ids = [];
            for (let i = startId; i <= actualEndId; i++) {
                ids.push(i);
            }
            
            // 배치 처리
            for (let i = 0; i < ids.length; i += concurrentRequests) {
                if (!isRunning) break;
                
                const batch = ids.slice(i, i + concurrentRequests);
                currentBatch = i + batch.length;
                
                // 병렬 처리
                const promises = batch.map(id => crawlRegion(id));
                await Promise.allSettled(promises);
                
                // 진행률 업데이트
                updateProgress(startId, actualEndId);
                updateStats();
                
                // 딜레이
                if (delayTime > 0 && i + concurrentRequests < ids.length) {
                    await sleep(delayTime);
                }
            }
            
            isRunning = false;
            showSummary();
        }
        
        // ===================================
        // 개별 지역 수집 함수
        // ===================================
        async function crawlRegion(regionId) {
            try {
                const response = await fetch(`daangn_proxy.php?region_id=${regionId}`, {
                    method: 'GET',
                    headers: {
                        'Cache-Control': 'no-cache'
                    }
                });
                
                processedCount++;
                document.getElementById('currentId').textContent = processedCount + parseInt(document.getElementById('startId').value) - 1;
                
                if (response.status === 404) {
                    failedIds.push(regionId);
                    log(`ID ${regionId}: 지역 정보 없음`, 'error');
                    return;
                }
                
                const data = await response.json();
                
                // 지역 정보 추출
                let regionInfo = null;
                
                if (data.regionFilterOptions && data.regionFilterOptions.region) {
                    const region = data.regionFilterOptions.region;
                    if (region.id == regionId) {
                        regionInfo = region;
                    }
                }
                
                if (regionInfo) {
                    collectedRegions.push(regionInfo);
                    log(`ID ${regionId}: ${regionInfo.name} (Depth ${regionInfo.depth})`, 'success');
                    addToTable(regionInfo);
                } else {
                    failedIds.push(regionId);
                    log(`ID ${regionId}: 지역 정보 추출 실패`, 'error');
                }
                
            } catch (error) {
                failedIds.push(regionId);
                log(`ID ${regionId}: 오류 - ${error.message}`, 'error');
            }
        }
        
        // ===================================
        // UI 업데이트 함수
        // ===================================
        function updateProgress(startId, endId) {
            const total = endId - startId + 1;
            const progress = (processedCount / total) * 100;
            
            document.getElementById('progressBar').style.width = progress + '%';
            document.getElementById('progressBar').textContent = Math.round(progress) + '%';
            
            // 남은 시간 계산
            if (processedCount > 0) {
                const elapsed = Date.now() - startTime;
                const rate = processedCount / (elapsed / 1000);
                const remaining = (total - processedCount) / rate;
                document.getElementById('remainingTime').textContent = formatTime(remaining);
            }
        }
        
        function updateStats() {
            document.getElementById('collectedCount').textContent = collectedRegions.length;
            document.getElementById('failedCount').textContent = failedIds.length;
            document.getElementById('tableCount').textContent = collectedRegions.length;
            
            // 처리 속도 계산
            if (startTime) {
                const elapsed = (Date.now() - startTime) / 1000;
                const speed = processedCount / elapsed;
                document.getElementById('processSpeed').textContent = speed.toFixed(1) + '/초';
            }
            
            // 성공률 계산
            if (processedCount > 0) {
                const successRate = (collectedRegions.length / processedCount) * 100;
                document.getElementById('successRate').textContent = successRate.toFixed(1) + '%';
            }
        }
        
        function addToTable(region) {
            const tbody = document.getElementById('resultBody');
            const tr = document.createElement('tr');
            tr.className = `daangn-depth-${region.depth}`;
            tr.innerHTML = `
                <td><span class="daangn-region-id">${region.id}</span></td>
                <td>${region.depth}</td>
                <td>${region.name1 || '-'}</td>
                <td>${region.name2 || '-'}</td>
                <td>${region.name3 || '-'}</td>
                <td><strong>${region.name}</strong></td>
            `;
            tbody.appendChild(tr);
        }
        
        // ===================================
        // 로그 함수
        // ===================================
        function log(message, type = 'info') {
            const logArea = document.getElementById('logArea');
            const time = new Date().toLocaleTimeString('ko-KR');
            const logEntry = document.createElement('div');
            logEntry.className = `daangn-log-${type}`;
            logEntry.innerHTML = `[${time}] ${message}`;
            logArea.appendChild(logEntry);
            logArea.scrollTop = logArea.scrollHeight;
        }
        
        function clearLog() {
            document.getElementById('logArea').innerHTML = '<div class="daangn-log-info">로그가 초기화되었습니다.</div>';
        }
        
        // ===================================
        // 제어 함수
        // ===================================
        function stopCrawling() {
            isRunning = false;
            log('수집이 중지되었습니다.', 'warning');
        }
        
        function clearData() {
            if (confirm('모든 수집 데이터를 초기화하시겠습니까?')) {
                collectedRegions = [];
                failedIds = [];
                processedCount = 0;
                document.getElementById('resultBody').innerHTML = '';
                document.getElementById('currentId').textContent = '0';
                document.getElementById('collectedCount').textContent = '0';
                document.getElementById('failedCount').textContent = '0';
                document.getElementById('tableCount').textContent = '0';
                document.getElementById('progressBar').style.width = '0%';
                document.getElementById('progressBar').textContent = '0%';
                clearLog();
                log('데이터가 초기화되었습니다.', 'info');
            }
        }
        
        // ===================================
        // 데이터 내보내기 함수
        // ===================================
        function exportData() {
            if (collectedRegions.length === 0) {
                alert('수집된 데이터가 없습니다.');
                return;
            }
            
            // SQL 형식으로 내보내기
            let sql = '-- 당근마켓 지역 정보\n';
            sql += '-- 생성일: ' + new Date().toLocaleString('ko-KR') + '\n\n';
            sql += 'CREATE TABLE IF NOT EXISTS daangn_regions (\n';
            sql += '    region_id INT PRIMARY KEY,\n';
            sql += '    depth INT NOT NULL,\n';
            sql += '    name1 VARCHAR(50),\n';
            sql += '    name2 VARCHAR(50),\n';
            sql += '    name3 VARCHAR(50),\n';
            sql += '    full_name VARCHAR(100) NOT NULL,\n';
            sql += '    name1_id INT,\n';
            sql += '    name2_id INT,\n';
            sql += '    name3_id INT,\n';
            sql += '    created_at DATETIME NOT NULL\n';
            sql += ');\n\n';
            
            sql += '-- 데이터 삽입\n';
            collectedRegions.forEach(region => {
                sql += `INSERT INTO daangn_regions (region_id, depth, name1, name2, name3, full_name, name1_id, name2_id, name3_id, created_at) VALUES `;
                sql += `(${region.id}, ${region.depth}, `;
                sql += `${region.name1 ? `'${region.name1}'` : 'NULL'}, `;
                sql += `${region.name2 ? `'${region.name2}'` : 'NULL'}, `;
                sql += `${region.name3 ? `'${region.name3}'` : 'NULL'}, `;
                sql += `'${region.name}', `;
                sql += `${region.name1Id || 'NULL'}, `;
                sql += `${region.name2Id || 'NULL'}, `;
                sql += `${region.name3Id || 'NULL'}, `;
                sql += `'${new Date().toISOString().slice(0, 19).replace('T', ' ')}');\n`;
            });
            
            // 파일 다운로드
            const blob = new Blob([sql], { type: 'text/sql;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = `daangn_regions_${new Date().toISOString().slice(0, 10)}.sql`;
            link.click();
            
            log(`${collectedRegions.length}개 지역 정보를 내보냈습니다.`, 'success');
            
            // JSON 형식도 콘솔에 출력
            console.log('=== JSON 데이터 ===');
            console.log(JSON.stringify(collectedRegions, null, 2));
        }
        
        // ===================================
        // 유틸리티 함수
        // ===================================
        function sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }
        
        function formatTime(seconds) {
            if (seconds < 60) {
                return Math.round(seconds) + '초';
            } else if (seconds < 3600) {
                return Math.floor(seconds / 60) + '분 ' + Math.round(seconds % 60) + '초';
            } else {
                return Math.floor(seconds / 3600) + '시간 ' + Math.floor((seconds % 3600) / 60) + '분';
            }
        }
        
        function showSummary() {
            const elapsed = (Date.now() - startTime) / 1000;
            const summary = `
수집 완료!
- 수집된 지역: ${collectedRegions.length}개
- 실패한 ID: ${failedIds.length}개
- 총 처리 시간: ${formatTime(elapsed)}
- 평균 처리 속도: ${(processedCount / elapsed).toFixed(1)}개/초`;
            log(summary, 'success');
        }
    </script>
</body>
</html>