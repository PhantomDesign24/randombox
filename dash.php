<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>당근마켓 가격 분석 대시보드</title>
    
    <!-- Pretendard 폰트 -->
    <link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css" />
    
    <!-- Bootstrap CSS 비동기 로드 -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"></noscript>
    
    <!-- Bootstrap Icons 비동기 로드 -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css"></noscript>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        /* ===================================
         * 전역 스타일
         * ===================================
         */
        /* 기본 변수 및 설정 */
        :root {
            --primary-color: #FF6F0F;
            --primary-hover: #E55F00;
            --primary-light: #FFF5F0;
            --secondary-color: #2E2E3E;
            --text-primary: #1C1C1E;
            --text-secondary: #6E7191;
            --bg-primary: #FFFFFF;
            --bg-secondary: #F8F9FD;
            --border-color: #E4E7EC;
            --success-color: #34C759;
            --danger-color: #FF3B30;
            --warning-color: #FF9500;
            --info-color: #5856D6;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.04);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 8px 32px rgba(0, 0, 0, 0.12);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
        }
        
        * {
            font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, system-ui, Roboto, sans-serif;
        }
        
        body {
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            line-height: 1.6;
        }
        
        /* ===================================
         * 타이포그래피
         * ===================================
         */
        /* 제목 스타일 */
        h1, h2, h3, h4, h5, h6 {
            font-weight: 700;
            color: var(--text-primary);
            line-height: 1.4;
        }
        
        h5 {
            font-size: 1.125rem;
            margin-bottom: 1rem;
        }
        
        /* ===================================
         * 네비게이션 스타일
         * ===================================
         */
        /* 네비게이션 바 */
        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem 0;
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .navbar-brand i {
            font-size: 1.5rem;
        }
        
        /* ===================================
         * 카드 스타일
         * ===================================
         */
        /* 기본 카드 */
        .card {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        
        .card-body {
            padding: 2rem;
        }
        
        .card-title {
            font-size: 1.125rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* 통계 카드 */
        .stats-card {
            text-align: center;
            padding: 2rem 1.5rem;
            background: var(--bg-primary);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stats-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, var(--primary-light) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .stats-card:hover::before {
            opacity: 1;
        }
        
        .stats-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }
        
        .stats-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--primary-color);
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        
        .stats-info {
            font-size: 0.75rem;
            color: var(--text-secondary);
            line-height: 1.4;
        }
        
        /* ===================================
         * 버튼 스타일
         * ===================================
         */
        /* 기본 버튼 */
        .btn {
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: var(--radius-sm);
            transition: all 0.2s ease;
            border: none;
        }
        
        /* 프라이머리 버튼 */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(255, 111, 15, 0.3);
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, var(--primary-hover) 0%, var(--primary-color) 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 111, 15, 0.4);
        }
        
        /* 보조 버튼 */
        .btn-secondary {
            background: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }
        
        .btn-secondary:hover {
            background: var(--bg-primary);
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        /* 작은 버튼 */
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        
        /* ===================================
         * 입력 필드 스타일
         * ===================================
         */
        /* 폼 컨트롤 */
        .form-control, .form-select {
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 0.75rem 1rem;
            font-size: 0.9375rem;
            transition: all 0.2s ease;
            background-color: var(--bg-primary);
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(255, 111, 15, 0.1);
        }
        
        .form-label {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }
        
        /* ===================================
         * 필터 스타일
         * ===================================
         */
        /* 필터 섹션 */
        .filter-section {
            background: var(--bg-primary);
            padding: 1.5rem;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
            margin-bottom: 1.5rem;
        }
        
        /* 필터 배지 */
        .filter-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            border: 1px solid var(--border-color);
            background: var(--bg-primary);
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s ease;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .filter-badge:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            background: var(--primary-light);
        }
        
        .filter-badge.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        /* 제외 필터 태그 */
        .exclude-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.375rem 0.75rem;
            background: var(--danger-color);
            color: white;
            border-radius: 20px;
            font-size: 0.813rem;
            font-weight: 500;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .exclude-tag button {
            background: none;
            border: none;
            color: white;
            padding: 0;
            margin: 0;
            font-size: 1rem;
            cursor: pointer;
            opacity: 0.8;
            transition: opacity 0.2s ease;
        }
        
        .exclude-tag button:hover {
            opacity: 1;
        }
        
        /* 제외 필터 입력 그룹 */
        .exclude-input-group {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .exclude-input {
            flex: 1;
        }
        
        /* ===================================
         * 지역 선택 스타일
         * ===================================
         */
        /* 선택된 지역 목록 */
        .selected-regions {
            max-height: 200px;
            overflow-y: auto;
            background: var(--bg-secondary);
        }
        
        .selected-regions::-webkit-scrollbar {
            width: 6px;
        }
        
        .selected-regions::-webkit-scrollbar-track {
            background: var(--bg-secondary);
        }
        
        .selected-regions::-webkit-scrollbar-thumb {
            background: var(--border-color);
            border-radius: 3px;
        }
        
        .selected-regions::-webkit-scrollbar-thumb:hover {
            background: var(--text-secondary);
        }
        
        /* 지역 태그 */
        .region-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.375rem 0.75rem;
            background: var(--primary-color);
            color: white;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .region-tag button {
            background: none;
            border: none;
            color: white;
            padding: 0;
            margin: 0;
            font-size: 1rem;
            cursor: pointer;
            opacity: 0.8;
            transition: opacity 0.2s ease;
        }
        
        .region-tag button:hover {
            opacity: 1;
        }
        
        /* 검색 결과 */
        .search-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            box-shadow: var(--shadow-md);
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }
        
        .search-result-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            transition: background 0.2s ease;
            border-bottom: 1px solid var(--border-color);
        }
        
        .search-result-item:last-child {
            border-bottom: none;
        }
        
        .search-result-item:hover {
            background: var(--bg-secondary);
        }
        
        .search-result-item strong {
            color: var(--primary-color);
        }
        
        /* ===================================
         * 차트 스타일
         * ===================================
         */
        /* 차트 컨테이너 */
        .chart-container {
            position: relative;
            height: 350px;
            margin-top: 1rem;
        }
        
        /* ===================================
         * 테이블 스타일
         * ===================================
         */
        /* 가격 테이블 */
        .table {
            font-size: 0.875rem;
        }
        
        .table th {
            background-color: var(--bg-secondary);
            font-weight: 700;
            color: var(--text-primary);
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            border-bottom: 2px solid var(--border-color);
            padding: 1rem 0.75rem;
        }
        
        .table td {
            padding: 0.875rem 0.75rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
        }
        
        .table tbody tr:hover {
            background-color: var(--bg-secondary);
        }
        
        /* ===================================
         * 상태 표시 스타일
         * ===================================
         */
        /* 거래 상태 */
        .status-ongoing { 
            color: var(--success-color);
            font-weight: 600;
        }
        
        .status-closed { 
            color: var(--text-secondary);
        }
        
        /* ===================================
         * 배지 스타일
         * ===================================
         */
        /* 기본 배지 */
        .badge {
            padding: 0.25rem 0.625rem;
            font-weight: 600;
            font-size: 0.75rem;
            border-radius: 12px;
        }
        
        /* 아이템 카드 */
        .item-card {
            background: var(--bg-primary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 1.25rem;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .item-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
            border-color: var(--primary-color);
        }
        
        .item-card.highlight {
            border: 2px solid var(--warning-color);
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--primary-light) 100%);
        }
        
        .item-thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: var(--radius-sm);
            margin-right: 1rem;
        }
        
        .item-title {
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .item-price {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--primary-color);
        }
        
        .item-meta {
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-top: 0.5rem;
        }
        
        /* ===================================
         * 로딩 스타일
         * ===================================
         */
        /* 로딩 오버레이 */
        .loading {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(4px);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        
        .loading-content {
            text-align: center;
            color: white;
        }
        
        .spinner-border {
            width: 3rem;
            height: 3rem;
            border-width: 0.3rem;
        }
        
        /* ===================================
         * 기타 스타일
         * ===================================
         */
        /* 결과 섹션 */
        .result-section {
            display: none;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* 섹션 제목 */
        .section-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* 제외 정보 알림 */
        .excluded-info {
            background: linear-gradient(135deg, var(--warning-color) 0%, #FF7F00 100%);
            color: white;
            border-radius: var(--radius-md);
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 12px rgba(255, 149, 0, 0.3);
        }
        
        .excluded-info h6 {
            color: white;
            font-weight: 700;
            margin-bottom: 0.75rem;
        }
        
        .excluded-info ul {
            margin-bottom: 0;
            padding-left: 1.25rem;
        }
        
        .excluded-info li {
            margin-bottom: 0.25rem;
        }
        
        /* 반응형 */
        @media (max-width: 768px) {
            .card-body {
                padding: 1.5rem;
            }
            
            .stats-number {
                font-size: 2rem;
            }
            
            .item-card {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- ===================================
     * 네비게이션
     * ===================================
     -->
    <!-- 메인 네비게이션 바 -->
    <nav class="navbar navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-graph-up-arrow"></i>
                당근마켓 가격 분석 대시보드
            </a>
        </div>
    </nav>
    
    <!-- ===================================
     * 로딩 인디케이터
     * ===================================
     -->
    <!-- 로딩 오버레이 -->
    <div class="loading" id="loading">
        <div class="loading-content">
            <div class="spinner-border text-primary" role="status"></div>
            <div class="mt-3">
                <h5>데이터를 수집하고 있습니다</h5>
                <p class="text-white-50">잠시만 기다려주세요...</p>
            </div>
        </div>
    </div>
    
    <div class="container mt-4">
        <!-- ===================================
         * 검색 설정 카드
         * ===================================
         -->
        <!-- 데이터 수집 설정 카드 -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">
                    <i class="bi bi-search"></i>
                    데이터 수집 설정
                </h5>
                
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">검색 키워드</label>
                        <input type="text" class="form-control" id="keyword" placeholder="예: RTX3070" value="RTX3070">
                    </div>
                    
                    <div class="col-md-8">
                        <label class="form-label">지역 선택</label>
                        
                        <!-- 지역 검색 -->
                        <div class="mb-3">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="bi bi-search text-primary"></i>
                                </span>
                                <input type="text" class="form-control border-start-0" 
                                       id="regionSearch" 
                                       placeholder="지역명 검색 (예: 강남, 일산동)"
                                       onkeyup="searchRegions()">
                            </div>
                        </div>
                        
                        <!-- 계층별 선택 -->
                        <div class="row g-2 mb-3" id="regionSelectors">
                            <div class="col-md-4">
                                <select class="form-select" id="region1Select" onchange="loadRegions(2, this.value)">
                                    <option value="">시/도 선택</option>
                                </select>
                                <button class="btn btn-sm btn-primary mt-2 w-100" onclick="addAllSubRegions(1)" id="addAllBtn1" style="display:none;">
                                    <i class="bi bi-plus-circle"></i> 하위 지역 전체 추가
                                </button>
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" id="region2Select" onchange="loadRegions(3, this.value)" disabled>
                                    <option value="">구/군 선택</option>
                                </select>
                                <button class="btn btn-sm btn-primary mt-2 w-100" onclick="addAllSubRegions(2)" id="addAllBtn2" style="display:none;">
                                    <i class="bi bi-plus-circle"></i> 하위 동 전체 추가
                                </button>
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" id="region3Select" onchange="addRegionFromSelect()" disabled>
                                    <option value="">동/읍/면 선택</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- 선택된 지역 -->
                        <div class="mb-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold">선택된 지역</span>
                                <div>
                                    <button class="btn btn-sm btn-secondary" onclick="clearAllRegions()">
                                        <i class="bi bi-x-lg"></i> 전체 해제
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="selected-regions border rounded p-3" id="selectedRegions">
                            <div class="text-muted text-center">선택된 지역이 없습니다</div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button class="btn btn-primary btn-lg" onclick="collectData()">
                        <i class="bi bi-cloud-download"></i> 데이터 수집 시작
                    </button>
                </div>
            </div>
        </div>
        
        <!-- ===================================
         * 결과 섹션
         * ===================================
         -->
        <!-- 분석 결과 섹션 -->
        <div class="result-section" id="resultSection">
            <!-- 제외된 항목 안내 -->
            <div class="excluded-info" id="excludedInfo">
                <h6><i class="bi bi-info-circle-fill"></i> 데이터 필터링 정보</h6>
                <p class="mb-2">정확한 가격 분석을 위해 다음 항목들이 제외되었습니다:</p>
                <ul>
                    <li>제외 키워드 매칭: <strong><span id="excludedKeywordCount">0</span>개</strong></li>
                    <li>비정상 가격 (1만원 미만 또는 1천만원 초과): <strong><span id="abnormalPriceCount">0</span>개</strong></li>
                    <li>총 제외: <strong><span id="totalExcludedCount">0</span>개</strong></li>
                </ul>
            </div>
            
            <!-- 필터 섹션 -->
            <div class="filter-section">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="mb-3">거래 상태</h6>
                        <div>
                            <span class="filter-badge active" id="filter-all" onclick="toggleFilter('status', 'all')">
                                전체
                            </span>
                            <span class="filter-badge" id="filter-ongoing" onclick="toggleFilter('status', 'ongoing')">
                                판매중만
                            </span>
                            <span class="filter-badge" id="filter-closed" onclick="toggleFilter('status', 'closed')">
                                거래완료만
                            </span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="mb-3">판매 유형</h6>
                        <div>
                            <span class="filter-badge active" id="type-all" onclick="toggleFilter('type', 'all')">
                                전체
                            </span>
                            <span class="filter-badge" id="type-single" onclick="toggleFilter('type', 'single')">
                                단품
                            </span>
                            <span class="filter-badge" id="type-set" onclick="toggleFilter('type', 'set')">
                                세트/본체
                            </span>
                            <span class="filter-badge" id="type-multiple" onclick="toggleFilter('type', 'multiple')">
                                대량
                            </span>
                        </div>
                    </div>
                </div>
                
                <hr class="my-3">
                
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="mb-3">정렬 기준</h6>
                        <select class="form-select" id="sortSelect" onchange="changeSort()">
                            <option value="price-asc">가격 낮은순</option>
                            <option value="price-desc">가격 높은순</option>
                            <option value="date-desc">최신순</option>
                            <option value="date-asc">오래된순</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <h6 class="mb-3">제외 키워드 관리</h6>
                        <small class="text-muted d-block mb-2">
                            현재 검색어: <strong id="currentSearchDisplay">없음</strong>
                        </small>
                    </div>
                </div>
                
                <div class="mt-3">
                    <div id="excludeKeywordsList" class="mb-3">
                        <!-- 동적으로 생성됨 -->
                    </div>
                    <div class="exclude-input-group">
                        <input type="text" class="form-control exclude-input" id="newExcludeKeyword" 
                               placeholder="제외할 키워드 입력 (예: 삽니다, 구매, 노트북)">
                        <button class="btn btn-secondary" onclick="addExcludeKeyword()">
                            <i class="bi bi-plus-lg"></i> 추가
                        </button>
                    </div>
                    <small class="text-muted mt-2 d-block">
                        * 검색어별로 다른 제외 키워드를 설정할 수 있으며, 설정은 자동 저장됩니다.
                    </small>
                </div>
                
                <div class="mt-3">
                    <button class="btn btn-primary btn-sm" onclick="applyFilters()">
                        <i class="bi bi-funnel"></i> 필터 적용
                    </button>
                    <button class="btn btn-secondary btn-sm" onclick="resetFilters()">
                        <i class="bi bi-arrow-counterclockwise"></i> 초기화
                    </button>
                </div>
            </div>
            
            <!-- 통계 카드 -->
            <h5 class="section-title mt-4">
                <i class="bi bi-bar-chart-fill"></i>
                핵심 통계
            </h5>
            
            <div class="row g-3">
                <div class="col-6 col-md-3">
                    <div class="stats-card">
                        <div class="stats-label">수집 지역</div>
                        <div class="stats-number" id="totalRegions">0</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stats-card">
                        <div class="stats-label">전체 매물</div>
                        <div class="stats-number" id="totalItems">0</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stats-card">
                        <div class="stats-label">평균가격</div>
                        <div class="stats-number" id="avgPrice">0</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stats-card">
                        <div class="stats-label">최저가</div>
                        <div class="stats-number" id="lowestPrice">0</div>
                        <div class="stats-info" id="lowestInfo"></div>
                    </div>
                </div>
            </div>
            
            <div class="row g-3 mt-2">
                <div class="col-6 col-md-3">
                    <div class="stats-card">
                        <div class="stats-label">판매중</div>
                        <div class="stats-number text-success" id="ongoingCount">0</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stats-card">
                        <div class="stats-label">거래완료</div>
                        <div class="stats-number text-secondary" id="closedCount">0</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stats-card">
                        <div class="stats-label">중간값</div>
                        <div class="stats-number" id="medianPrice">0</div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stats-card">
                        <div class="stats-label">가격범위</div>
                        <div class="stats-number" style="font-size: 1.25rem;" id="priceRange">0</div>
                    </div>
                </div>
            </div>
            
            <!-- 차트 영역 -->
            <h5 class="section-title mt-5">
                <i class="bi bi-graph-up"></i>
                가격 분석 차트
            </h5>
            
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title">지역별 평균 가격</h6>
                            <div class="chart-container">
                                <canvas id="priceChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title">가격 분포</h6>
                            <div class="chart-container">
                                <canvas id="distributionChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 상세 데이터 테이블 -->
            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="bi bi-table"></i>
                        지역별 상세 데이터
                    </h5>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>지역</th>
                                    <th>전체</th>
                                    <th>판매중</th>
                                    <th>완료</th>
                                    <th>최저가</th>
                                    <th>25%</th>
                                    <th>중간값</th>
                                    <th>75%</th>
                                    <th>최고가</th>
                                    <th>평균가</th>
                                </tr>
                            </thead>
                            <tbody id="dataTableBody">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- 최근 매물 목록 -->
            <h5 class="section-title mt-5">
                <i class="bi bi-tag-fill"></i>
                매물 상세
            </h5>
            
            <!-- 매물 목록 필터 -->
            <div class="card mb-3">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-4">
                            <label class="form-label">페이지당 표시</label>
                            <select class="form-select" id="itemsPerPage" onchange="updateItemsDisplay()">
                                <option value="20">20개</option>
                                <option value="50" selected>50개</option>
                                <option value="100">100개</option>
                                <option value="all">전체</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">제목 필터</label>
                            <input type="text" class="form-control" id="titleFilter" 
                                   placeholder="제목에 포함된 단어 (예: 3070)" 
                                   onkeyup="updateItemsDisplay()">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">보기 방식</label>
                            <select class="form-select" id="viewMode" onchange="updateItemsDisplay()">
                                <option value="grid">카드 형태</option>
                                <option value="list">리스트 형태</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- 페이지네이션 (상단) -->
            <nav id="topPagination" class="mb-3"></nav>
            
            <div id="recentItems"></div>
            
            <!-- 페이지네이션 (하단) -->
            <nav id="bottomPagination" class="mt-3"></nav>
        </div>
    </div>
    
    <script>
    // 전체 스크립트를 즉시 실행 함수로 감싸서 안전하게 만듭니다
    (function() {
        'use strict';
        
        // ===================================
        // 전역 변수
        // ===================================
        window.collectedData = {};
        window.priceChart = null;
        window.distributionChart = null;
        window.allArticles = [];
        window.filteredArticles = [];
        window.excludedStats = {
            keywords: 0,
            abnormalPrices: 0
        };
        window.currentFilters = {
            status: 'all',
            type: 'all'
        };
        window.currentSort = 'price-asc';
        window.excludeKeywordsBySearch = JSON.parse(localStorage.getItem('daangnExcludeKeywords') || '{}');
        window.currentSearchKeyword = '';
        window.selectedRegions = new Map();
        
        const defaultExcludeKeywords = [
            '삽니다', '삽니당', '삽니댜',
            '구매', '구매합니다', '구매희망', '구매원해요',
            '구합니다', '구해요', '구해봅니다', '구함',
            '찾습니다', '찾아요', '찾고있습니다',
            '원합니다', '원해요',
            '살게요', '살께요', '사고싶어요',
            '급구', '급하게 구해요'
        ];
        
        // ===================================
        // 지역 관련 함수
        // ===================================
        window.searchRegions = async function() {
            const keyword = document.getElementById('regionSearch').value.trim();
            
            if (keyword.length < 2) {
                hideSearchResults();
                return;
            }
            
            try {
                const response = await fetch(`get_regions.php?search=${encodeURIComponent(keyword)}`);
                const result = await response.json();
                
                if (result.success && result.data.length > 0) {
                    showSearchResults(result.data);
                } else {
                    hideSearchResults();
                }
            } catch (error) {
                console.error('지역 검색 오류:', error);
            }
        };
        
        window.showSearchResults = function(regions) {
            let resultsHtml = '<div class="search-results" style="display: block;">';
            
            regions.forEach(region => {
                const searchKeyword = document.getElementById('regionSearch').value;
                const escapedKeyword = searchKeyword.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                const highlight = region.full_name.replace(
                    new RegExp(escapedKeyword, 'gi'),
                    '<strong>$&</strong>'
                );
                
                resultsHtml += `
                    <div class="search-result-item" 
                         data-region-id="${region.region_id}" 
                         data-region-name="${region.full_name.replace(/"/g, '&quot;')}"
                         onclick="addRegionFromSearch(this)">
                        ${highlight}
                    </div>
                `;
            });
            
            resultsHtml += '</div>';
            
            hideSearchResults();
            
            const searchContainer = document.getElementById('regionSearch').parentElement.parentElement;
            searchContainer.style.position = 'relative';
            searchContainer.insertAdjacentHTML('beforeend', resultsHtml);
        };
        
        window.hideSearchResults = function() {
            const results = document.querySelector('.search-results');
            if (results) {
                results.remove();
            }
        };
        
        window.addRegionFromSearch = function(element) {
            const regionId = parseInt(element.getAttribute('data-region-id'));
            const regionName = element.getAttribute('data-region-name');
            addRegion(regionId, regionName);
        };
        
        window.loadRegions = async function(depth, parentId) {
            try {
                const url = parentId 
                    ? `get_regions.php?depth=${depth}&parent_id=${parentId}`
                    : `get_regions.php?depth=${depth}`;
                
                console.log('지역 로드 요청:', { depth, parentId, url });
                    
                const response = await fetch(url);
                const result = await response.json();
                
                console.log('지역 로드 응답:', result);
                
                if (result.success && result.data) {
                    const selectId = `region${depth}Select`;
                    const select = document.getElementById(selectId);
                    
                    if (!select) {
                        console.error(`Select element not found: ${selectId}`);
                        return;
                    }
                    
                    select.innerHTML = `<option value="">${depth === 1 ? '시/도' : depth === 2 ? '구/군' : '동/읍/면'} 선택</option>`;
                    
                    result.data.forEach(region => {
                        const option = document.createElement('option');
                        option.value = region.region_id;
                        option.textContent = region.name;
                        
                        if (depth === 3 && region.full_name) {
                            option.setAttribute('data-fullname', region.full_name);
                        }
                        
                        select.appendChild(option);
                    });
                    
                    select.disabled = false;
                    
                    // 하위 전체 추가 버튼 표시
                    if (depth < 3 && parentId) {
                        const addAllBtn = document.getElementById(`addAllBtn${depth}`);
                        if (addAllBtn) {
                            addAllBtn.style.display = 'block';
                        }
                    }
                    
                    if (depth < 3) {
                        for (let i = depth + 1; i <= 3; i++) {
                            const nextSelect = document.getElementById(`region${i}Select`);
                            if (nextSelect) {
                                nextSelect.innerHTML = `<option value="">${i === 2 ? '구/군' : '동/읍/면'} 선택</option>`;
                                nextSelect.disabled = true;
                            }
                            const nextBtn = document.getElementById(`addAllBtn${i}`);
                            if (nextBtn) {
                                nextBtn.style.display = 'none';
                            }
                        }
                    }
                } else {
                    console.error('지역 로드 실패:', result.error || 'No data');
                }
            } catch (error) {
                console.error('지역 로드 오류:', error);
            }
        };
        
        window.addAllSubRegions = async function(currentDepth) {
            const parentSelect = document.getElementById(`region${currentDepth}Select`);
            const parentId = parentSelect.value;
            const parentName = parentSelect.options[parentSelect.selectedIndex].text;
            
            if (!parentId) {
                alert('상위 지역을 선택해주세요');
                return;
            }
            
            if (!confirm(`"${parentName}"의 모든 하위 지역을 추가하시겠습니까?`)) {
                return;
            }
            
            try {
                // 로딩 표시
                document.getElementById('loading').style.display = 'flex';
                
                // depth 3인 모든 하위 지역 가져오기
                const response = await fetch(`get_regions.php?all_sub=1&parent_id=${parentId}&depth=${currentDepth}`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    let addedCount = 0;
                    result.data.forEach(region => {
                        if (!selectedRegions.has(region.region_id)) {
                            selectedRegions.set(region.region_id, {
                                id: region.region_id,
                                name: region.full_name
                            });
                            addedCount++;
                        }
                    });
                    
                    updateSelectedRegions();
                    alert(`${addedCount}개 지역이 추가되었습니다.`);
                }
            } catch (error) {
                console.error('하위 지역 추가 오류:', error);
                alert('하위 지역을 추가하는 중 오류가 발생했습니다.');
            } finally {
                document.getElementById('loading').style.display = 'none';
            }
        };
        
        window.addRegionFromSelect = function() {
            const select = document.getElementById('region3Select');
            const selectedOption = select.options[select.selectedIndex];
            
            if (selectedOption.value) {
                const fullName = selectedOption.getAttribute('data-fullname') || selectedOption.text;
                addRegion(selectedOption.value, fullName);
            }
        };
        
        window.addRegion = function(regionId, regionName) {
            if (!selectedRegions.has(regionId)) {
                selectedRegions.set(regionId, {
                    id: regionId,
                    name: regionName
                });
                updateSelectedRegions();
                
                document.getElementById('regionSearch').value = '';
                hideSearchResults();
            }
        };
        
        window.removeRegion = function(regionId) {
            selectedRegions.delete(regionId);
            updateSelectedRegions();
        };
        
        window.clearAllRegions = function() {
            selectedRegions.clear();
            updateSelectedRegions();
        };
        
        window.updateSelectedRegions = function() {
            const container = document.getElementById('selectedRegions');
            
            if (selectedRegions.size === 0) {
                container.innerHTML = '<div class="text-muted text-center">선택된 지역이 없습니다</div>';
            } else {
                let html = '';
                selectedRegions.forEach((region, id) => {
                    html += `
                        <span class="region-tag">
                            ${region.name}
                            <button onclick="removeRegion(${id})" title="제거">
                                <i class="bi bi-x"></i>
                            </button>
                        </span>
                    `;
                });
                container.innerHTML = html;
            }
        };
        
        // ===================================
        // 데이터 수집 함수
        // ===================================
        window.collectData = async function() {
            const keyword = document.getElementById('keyword').value;
            if (!keyword) {
                alert('검색어를 입력해주세요');
                return;
            }
            
            currentSearchKeyword = keyword;
            
            if (!excludeKeywordsBySearch[currentSearchKeyword]) {
                excludeKeywordsBySearch[currentSearchKeyword] = [...defaultExcludeKeywords];
            }
            
            updateExcludeKeywordsList();
            
            if (selectedRegions.size === 0) {
                alert('지역을 선택해주세요');
                return;
            }
            
            document.getElementById('loading').style.display = 'flex';
            collectedData = {};
            allArticles = [];
            
            for (const [regionId, regionData] of selectedRegions) {
                try {
                    const response = await fetch(`daangn_api_proxy.php?region_id=${regionId}&keyword=${encodeURIComponent(keyword)}`);
                    const result = await response.json();
                    
                    if (!result.error && result.data) {
                        collectedData[regionId] = {
                            name: regionData.name,
                            data: result.data
                        };
                        
                        const articles = result.data.allPage?.fleamarketArticles || [];
                        articles.forEach(article => {
                            allArticles.push({
                                ...article,
                                regionName: regionData.name,
                                regionId: regionId,
                                itemType: classifyItemType(article)
                            });
                        });
                    }
                } catch (error) {
                    console.error('Error collecting data for region', regionId, error);
                }
            }
            
            document.getElementById('loading').style.display = 'none';
            
            applyFilters();
            displayResults();
        };
        
        // ===================================
        // 필터 관련 함수
        // ===================================
        window.toggleFilter = function(filterType, value) {
            if (filterType === 'status') {
                document.querySelectorAll('[id^="filter-"]').forEach(badge => {
                    badge.classList.remove('active');
                });
                document.getElementById(`filter-${value}`).classList.add('active');
            } else if (filterType === 'type') {
                document.querySelectorAll('[id^="type-"]').forEach(badge => {
                    badge.classList.remove('active');
                });
                document.getElementById(`type-${value}`).classList.add('active');
            }
            
            currentFilters[filterType] = value;
        };
        
        window.changeSort = function() {
            currentSort = document.getElementById('sortSelect').value;
            displayFilteredResults();
        };
        
        window.applyFilters = function() {
            excludedStats = { keywords: 0, abnormalPrices: 0 };
            
            filteredArticles = allArticles.filter(article => {
                const price = parseFloat(article.price);
                
                if (shouldExcludeArticle(article)) {
                    excludedStats.keywords++;
                    return false;
                }
                
                if (price < 10000 || price > 10000000) {
                    excludedStats.abnormalPrices++;
                    return false;
                }
                
                if (currentFilters.status !== 'all') {
                    if (currentFilters.status === 'ongoing' && article.status !== 'Ongoing') return false;
                    if (currentFilters.status === 'closed' && article.status !== 'Closed') return false;
                }
                
                if (currentFilters.type !== 'all') {
                    const itemType = classifyItemType(article);
                    if (currentFilters.type !== itemType) return false;
                }
                
                return true;
            });
            
            displayFilteredResults();
        };
        
        window.resetFilters = function() {
            currentFilters = { status: 'all', type: 'all' };
            document.querySelectorAll('.filter-badge').forEach(badge => {
                badge.classList.remove('active');
            });
            document.getElementById('filter-all').classList.add('active');
            document.getElementById('type-all').classList.add('active');
            
            excludeKeywordsBySearch[currentSearchKeyword] = [...defaultExcludeKeywords];
            localStorage.setItem('daangnExcludeKeywords', JSON.stringify(excludeKeywordsBySearch));
            updateExcludeKeywordsList();
            
            applyFilters();
        };
        
        // ===================================
        // 제외 키워드 관련 함수
        // ===================================
        window.addExcludeKeyword = function() {
            const input = document.getElementById('newExcludeKeyword');
            const keyword = input.value.trim();
            
            if (!keyword) return;
            
            if (!excludeKeywordsBySearch[currentSearchKeyword]) {
                excludeKeywordsBySearch[currentSearchKeyword] = [...defaultExcludeKeywords];
            }
            
            if (!excludeKeywordsBySearch[currentSearchKeyword].includes(keyword)) {
                excludeKeywordsBySearch[currentSearchKeyword].push(keyword);
                localStorage.setItem('daangnExcludeKeywords', JSON.stringify(excludeKeywordsBySearch));
                input.value = '';
                updateExcludeKeywordsList();
                applyFilters();
            }
        };
        
        window.removeExcludeKeyword = function(keyword) {
            if (excludeKeywordsBySearch[currentSearchKeyword]) {
                excludeKeywordsBySearch[currentSearchKeyword] = 
                    excludeKeywordsBySearch[currentSearchKeyword].filter(k => k !== keyword);
                localStorage.setItem('daangnExcludeKeywords', JSON.stringify(excludeKeywordsBySearch));
                updateExcludeKeywordsList();
                applyFilters();
            }
        };
        
        window.updateExcludeKeywordsList = function() {
            const container = document.getElementById('excludeKeywordsList');
            const keywords = excludeKeywordsBySearch[currentSearchKeyword] || defaultExcludeKeywords;
            
            container.innerHTML = keywords.map(keyword => `
                <span class="exclude-tag">
                    ${keyword}
                    <button onclick="removeExcludeKeyword('${keyword}')" title="삭제">
                        <i class="bi bi-x"></i>
                    </button>
                </span>
            `).join('');
        };
        
        // ===================================
        // 유틸리티 함수
        // ===================================
        window.shouldExcludeArticle = function(article) {
            const title = article.title.toLowerCase();
            const keywords = excludeKeywordsBySearch[currentSearchKeyword] || defaultExcludeKeywords;
            
            for (const keyword of keywords) {
                if (title.includes(keyword.toLowerCase())) {
                    return true;
                }
            }
            
            return false;
        };
        
        window.classifyItemType = function(article) {
            const title = article.title.toLowerCase();
            const content = (article.content || '').toLowerCase();
            const combined = title + ' ' + content;
            
            const multiplePatterns = [
                /(\d+)\s*개/, /(\d+)\s*장/, /(\d+)\s*매/, /(\d+)\s*세트/,
                /(\d+)\s*팩/, /(\d+)\s*박스/, /(\d+)\s*묶음/
            ];
            
            for (const pattern of multiplePatterns) {
                const match = combined.match(pattern);
                if (match && parseInt(match[1]) > 1) {
                    return 'multiple';
                }
            }
            
            if (combined.includes('대량') || combined.includes('여러') || 
                combined.includes('다수') || combined.includes('일괄')) {
                return 'multiple';
            }
            
            if (combined.includes('본체') || combined.includes('풀세트') || 
                combined.includes('full set') || combined.includes('풀셋') ||
                combined.includes('풀박스') || combined.includes('완제품')) {
                return 'set';
            }
            
            if ((combined.includes('컴퓨터') || combined.includes('pc')) && 
                (combined.includes('세트') || combined.includes('일체'))) {
                return 'set';
            }
            
            return 'single';
        };
        
        window.sortArticles = function(articles) {
            const sorted = [...articles];
            
            switch(currentSort) {
                case 'price-asc':
                    sorted.sort((a, b) => parseFloat(a.price) - parseFloat(b.price));
                    break;
                case 'price-desc':
                    sorted.sort((a, b) => parseFloat(b.price) - parseFloat(a.price));
                    break;
                case 'date-desc':
                    sorted.sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
                    break;
                case 'date-asc':
                    sorted.sort((a, b) => new Date(a.createdAt) - new Date(b.createdAt));
                    break;
            }
            
            return sorted;
        };
        
        // ===================================
        // 결과 표시 함수
        // ===================================
        window.displayResults = function() {
            if (Object.keys(collectedData).length === 0) {
                alert('데이터를 수집하지 못했습니다');
                return;
            }
            
            document.getElementById('resultSection').style.display = 'block';
            document.getElementById('sortSelect').value = currentSort;
            
            const keywordDisplay = document.getElementById('currentSearchDisplay');
            if (keywordDisplay) {
                keywordDisplay.textContent = currentSearchKeyword || '없음';
            }
            
            displayFilteredResults();
        };
        
        window.displayFilteredResults = function() {
            document.getElementById('excludedKeywordCount').textContent = excludedStats.keywords;
            document.getElementById('abnormalPriceCount').textContent = excludedStats.abnormalPrices;
            document.getElementById('totalExcludedCount').textContent = 
                excludedStats.keywords + excludedStats.abnormalPrices;
            
            let totalItems = filteredArticles.length;
            let regionStats = [];
            let ongoingTotal = 0;
            let closedTotal = 0;
            
            const validPrices = filteredArticles
                .map(a => parseFloat(a.price))
                .sort((a, b) => a - b);
            
            const lowestItem = filteredArticles
                .sort((a, b) => parseFloat(a.price) - parseFloat(b.price))[0];
            
            const regionGroups = {};
            filteredArticles.forEach(article => {
                if (!regionGroups[article.regionId]) {
                    regionGroups[article.regionId] = {
                        name: article.regionName,
                        articles: []
                    };
                }
                regionGroups[article.regionId].articles.push(article);
            });
            
            for (const [regionId, regionData] of Object.entries(regionGroups)) {
                const articles = regionData.articles;
                const prices = articles
                    .map(a => parseFloat(a.price))
                    .sort((a, b) => a - b);
                
                const ongoingCount = articles.filter(a => a.status === 'Ongoing').length;
                const closedCount = articles.filter(a => a.status === 'Closed').length;
                
                ongoingTotal += ongoingCount;
                closedTotal += closedCount;
                
                if (prices.length > 0) {
                    const stats = {
                        regionId: regionId,
                        regionName: regionData.name,
                        totalCount: articles.length,
                        ongoingCount: ongoingCount,
                        closedCount: closedCount,
                        prices: prices,
                        minPrice: prices[0],
                        maxPrice: prices[prices.length - 1],
                        avgPrice: prices.reduce((a, b) => a + b, 0) / prices.length,
                        medianPrice: prices.length > 0 ? prices[Math.floor(prices.length / 2)] : 0,
                        q1Price: prices.length >= 4 ? prices[Math.floor(prices.length * 0.25)] : prices[0],
                        q3Price: prices.length >= 4 ? prices[Math.floor(prices.length * 0.75)] : prices[prices.length - 1]
                    };
                    
                    regionStats.push(stats);
                }
            }
            
            document.getElementById('totalRegions').textContent = regionStats.length;
            document.getElementById('totalItems').textContent = totalItems.toLocaleString();
            document.getElementById('ongoingCount').textContent = ongoingTotal.toLocaleString();
            document.getElementById('closedCount').textContent = closedTotal.toLocaleString();
            
            const avgPrice = validPrices.length > 0 ? validPrices.reduce((a, b) => a + b, 0) / validPrices.length : 0;
            document.getElementById('avgPrice').textContent = avgPrice > 0 ? 
                '₩' + Math.round(avgPrice).toLocaleString() : '-';
            
            const medianPrice = validPrices.length > 0 ? validPrices[Math.floor(validPrices.length / 2)] : 0;
            document.getElementById('medianPrice').textContent = medianPrice > 0 ? 
                '₩' + Math.round(medianPrice).toLocaleString() : '-';
            
            if (lowestItem) {
                document.getElementById('lowestPrice').textContent = '₩' + parseFloat(lowestItem.price).toLocaleString();
                document.getElementById('lowestInfo').innerHTML = 
                    `${lowestItem.regionName}<br>${lowestItem.title.substring(0, 15)}...`;
            } else {
                document.getElementById('lowestPrice').textContent = '-';
                document.getElementById('lowestInfo').innerHTML = '';
            }
            
            if (validPrices.length > 0) {
                const minPrice = Math.min(...validPrices);
                const maxPrice = Math.max(...validPrices);
                document.getElementById('priceRange').textContent = 
                    `₩${minPrice.toLocaleString()} ~ ₩${maxPrice.toLocaleString()}`;
            } else {
                document.getElementById('priceRange').textContent = '-';
            }
            
            updateChart(regionStats);
            updateDistributionChart(validPrices);
            updateTable(regionStats);
            displayRecentItems();
        };
        
        // ===================================
        // 차트 함수
        // ===================================
        window.updateChart = function(regionStats) {
            const ctx = document.getElementById('priceChart').getContext('2d');
            
            if (priceChart) {
                priceChart.destroy();
            }
            
            regionStats.sort((a, b) => a.avgPrice - b.avgPrice);
            
            priceChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: regionStats.map(r => r.regionName),
                    datasets: [{
                        label: '평균 가격',
                        data: regionStats.map(r => Math.round(r.avgPrice)),
                        backgroundColor: 'rgba(255, 111, 15, 0.8)',
                        borderColor: 'rgba(255, 111, 15, 1)',
                        borderWidth: 2,
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            cornerRadius: 8,
                            titleFont: { size: 14, weight: 'bold' },
                            bodyFont: { size: 13 },
                            callbacks: {
                                label: function(context) {
                                    const region = regionStats[context.dataIndex];
                                    return [
                                        `평균: ₩${Math.round(region.avgPrice).toLocaleString()}`,
                                        `최저: ₩${region.minPrice.toLocaleString()}`,
                                        `최고: ₩${region.maxPrice.toLocaleString()}`,
                                        `매물: ${region.totalCount}개`
                                    ];
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: false,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            },
                            ticks: {
                                font: { size: 12 },
                                callback: function(value) {
                                    return '₩' + value.toLocaleString();
                                }
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: { size: 12 }
                            }
                        }
                    }
                }
            });
        };
        
        window.updateDistributionChart = function(prices) {
            const ctx = document.getElementById('distributionChart').getContext('2d');
            
            if (distributionChart) {
                distributionChart.destroy();
            }
            
            const ranges = [
                { label: '~20만', min: 0, max: 200000 },
                { label: '20~30만', min: 200000, max: 300000 },
                { label: '30~40만', min: 300000, max: 400000 },
                { label: '40~50만', min: 400000, max: 500000 },
                { label: '50~60만', min: 500000, max: 600000 },
                { label: '60만~', min: 600000, max: Infinity }
            ];
            
            const distribution = ranges.map(range => {
                return prices.filter(p => p >= range.min && p < range.max).length;
            });
            
            distributionChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ranges.map(r => r.label),
                    datasets: [{
                        data: distribution,
                        backgroundColor: [
                            'rgba(255, 107, 107, 0.8)',
                            'rgba(78, 205, 196, 0.8)',
                            'rgba(69, 183, 209, 0.8)',
                            'rgba(150, 206, 180, 0.8)',
                            'rgba(254, 202, 87, 0.8)',
                            'rgba(155, 89, 182, 0.8)'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                            labels: {
                                padding: 15,
                                font: { size: 12 }
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(0, 0, 0, 0.8)',
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                                    return `${context.label}: ${context.parsed}개 (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        };
        
        window.updateTable = function(regionStats) {
            const tbody = document.getElementById('dataTableBody');
            tbody.innerHTML = '';
            
            regionStats.sort((a, b) => a.avgPrice - b.avgPrice);
            
            regionStats.forEach(stat => {
                const row = `
                    <tr>
                        <td><strong>${stat.regionName}</strong></td>
                        <td>${stat.totalCount}</td>
                        <td class="status-ongoing">${stat.ongoingCount}</td>
                        <td class="status-closed">${stat.closedCount}</td>
                        <td>₩${stat.minPrice.toLocaleString()}</td>
                        <td>₩${stat.q1Price.toLocaleString()}</td>
                        <td>₩${stat.medianPrice.toLocaleString()}</td>
                        <td>₩${stat.q3Price.toLocaleString()}</td>
                        <td>₩${stat.maxPrice.toLocaleString()}</td>
                        <td><strong>₩${Math.round(stat.avgPrice).toLocaleString()}</strong></td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });
        };
        
        // 페이지네이션 관련 변수
        window.currentPage = 1;
        window.itemsPerPageValue = 50;
        
        window.displayRecentItems = function() {
            updateItemsDisplay();
        };
        
        window.updateItemsDisplay = function() {
            const titleFilter = document.getElementById('titleFilter').value.toLowerCase();
            const viewMode = document.getElementById('viewMode').value;
            const itemsPerPageSelect = document.getElementById('itemsPerPage');
            itemsPerPageValue = itemsPerPageSelect.value === 'all' ? 999999 : parseInt(itemsPerPageSelect.value);
            
            // 제목 필터링
            let itemsToDisplay = filteredArticles;
            if (titleFilter) {
                itemsToDisplay = filteredArticles.filter(item => 
                    item.title.toLowerCase().includes(titleFilter)
                );
            }
            
            // 정렬
            const sortedItems = sortArticles(itemsToDisplay);
            
            // 페이지네이션 계산
            const totalItems = sortedItems.length;
            const totalPages = Math.ceil(totalItems / itemsPerPageValue);
            
            if (currentPage > totalPages) currentPage = 1;
            
            const startIndex = (currentPage - 1) * itemsPerPageValue;
            const endIndex = Math.min(startIndex + itemsPerPageValue, totalItems);
            const pageItems = sortedItems.slice(startIndex, endIndex);
            
            // 컨테이너 준비
            const container = document.getElementById('recentItems');
            
            // 통계 정보
            let statsHtml = `
                <div class="alert alert-info mb-3">
                    <strong>총 ${totalItems}개 매물</strong> 
                    ${titleFilter ? `(제목에 "${titleFilter}" 포함)` : ''} 
                    | 현재 ${startIndex + 1}-${endIndex}번째 표시
                </div>
            `;
            
            // 뷰 모드에 따른 표시
            let itemsHtml = '';
            if (viewMode === 'grid') {
                itemsHtml = '<div class="row g-3">';
                pageItems.forEach((item, index) => {
                    itemsHtml += createItemCard(item, -1);
                });
                itemsHtml += '</div>';
            } else {
                // 리스트 형태
                itemsHtml = '<div class="table-responsive"><table class="table table-hover">';
                itemsHtml += `
                    <thead>
                        <tr>
                            <th>이미지</th>
                            <th>제목</th>
                            <th>가격</th>
                            <th>상태</th>
                            <th>유형</th>
                            <th>지역</th>
                            <th>작성자</th>
                            <th>등록일</th>
                            <th>상세</th>
                        </tr>
                    </thead>
                    <tbody>
                `;
                
                pageItems.forEach(item => {
                    const date = new Date(item.createdAt);
                    const dateStr = `${date.getMonth() + 1}/${date.getDate()} ${date.getHours()}:${date.getMinutes().toString().padStart(2, '0')}`;
                    
                    const typeBadge = {
                        'single': '<span class="badge bg-primary">단품</span>',
                        'set': '<span class="badge bg-info">세트/본체</span>',
                        'multiple': '<span class="badge bg-warning">대량</span>'
                    };
                    
                    itemsHtml += `
                        <tr>
                            <td>
                                ${item.thumbnail ? 
                                    `<img src="${item.thumbnail}" style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">` : 
                                    '<div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 4px;"></div>'}
                            </td>
                            <td>${item.title}</td>
                            <td class="text-nowrap"><strong>₩${parseFloat(item.price).toLocaleString()}</strong></td>
                            <td>
                                <span class="badge ${item.status === 'Ongoing' ? 'bg-success' : 'bg-secondary'}">
                                    ${item.status === 'Ongoing' ? '판매중' : '거래완료'}
                                </span>
                            </td>
                            <td>${typeBadge[item.itemType] || ''}</td>
                            <td>${item.regionName}</td>
                            <td>${item.user.nickname}</td>
                            <td class="text-nowrap">${dateStr}</td>
                            <td>
                                <a href="${item.href}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-box-arrow-up-right"></i>
                                </a>
                            </td>
                        </tr>
                    `;
                });
                
                itemsHtml += '</tbody></table></div>';
            }
            
            container.innerHTML = statsHtml + itemsHtml;
            
            // 페이지네이션 생성
            createPagination(totalPages);
        };
        
        window.createPagination = function(totalPages) {
            if (totalPages <= 1) {
                document.getElementById('topPagination').innerHTML = '';
                document.getElementById('bottomPagination').innerHTML = '';
                return;
            }
            
            let paginationHtml = '<ul class="pagination justify-content-center">';
            
            // 이전 버튼
            paginationHtml += `
                <li class="page-item ${currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${currentPage - 1}); return false;">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>
            `;
            
            // 페이지 번호
            const maxVisible = 10;
            let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
            let endPage = Math.min(totalPages, startPage + maxVisible - 1);
            
            if (endPage - startPage < maxVisible - 1) {
                startPage = Math.max(1, endPage - maxVisible + 1);
            }
            
            if (startPage > 1) {
                paginationHtml += `
                    <li class="page-item">
                        <a class="page-link" href="#" onclick="changePage(1); return false;">1</a>
                    </li>
                `;
                if (startPage > 2) {
                    paginationHtml += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
            }
            
            for (let i = startPage; i <= endPage; i++) {
                paginationHtml += `
                    <li class="page-item ${i === currentPage ? 'active' : ''}">
                        <a class="page-link" href="#" onclick="changePage(${i}); return false;">${i}</a>
                    </li>
                `;
            }
            
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    paginationHtml += '<li class="page-item disabled"><span class="page-link">...</span></li>';
                }
                paginationHtml += `
                    <li class="page-item">
                        <a class="page-link" href="#" onclick="changePage(${totalPages}); return false;">${totalPages}</a>
                    </li>
                `;
            }
            
            // 다음 버튼
            paginationHtml += `
                <li class="page-item ${currentPage === totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" onclick="changePage(${currentPage + 1}); return false;">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            `;
            
            paginationHtml += '</ul>';
            
            document.getElementById('topPagination').innerHTML = paginationHtml;
            document.getElementById('bottomPagination').innerHTML = paginationHtml;
        };
        
        window.changePage = function(page) {
            currentPage = page;
            updateItemsDisplay();
            // 스크롤을 매물 목록으로
            document.getElementById('recentItems').scrollIntoView({ behavior: 'smooth', block: 'start' });
        };
        
        window.createItemCard = function(item, index) {
            const date = new Date(item.createdAt);
            const dateStr = `${date.getMonth() + 1}/${date.getDate()} ${date.getHours()}:${date.getMinutes().toString().padStart(2, '0')}`;
            
            const typeBadge = {
                'single': '<span class="badge bg-primary">단품</span>',
                'set': '<span class="badge bg-info">세트/본체</span>',
                'multiple': '<span class="badge bg-warning">대량</span>'
            };
            
            return `
                <div class="col-md-6">
                    <div class="item-card ${index >= 0 && index < 3 ? 'highlight' : ''}">
                        <div class="d-flex">
                            ${item.thumbnail ? `<img src="${item.thumbnail}" class="item-thumbnail" alt="">` : ''}
                            <div class="flex-grow-1">
                                <h6 class="item-title">${item.title}</h6>
                                <div class="mb-2">
                                    <span class="item-price">₩${parseFloat(item.price).toLocaleString()}</span>
                                    <span class="badge ${item.status === 'Ongoing' ? 'bg-success' : 'bg-secondary'} ms-2">
                                        ${item.status === 'Ongoing' ? '판매중' : '거래완료'}
                                    </span>
                                    ${typeBadge[item.itemType] || ''}
                                </div>
                                <div class="item-meta">
                                    <i class="bi bi-geo-alt"></i> ${item.regionName} · 
                                    <i class="bi bi-person"></i> ${item.user.nickname} · 
                                    <i class="bi bi-clock"></i> ${dateStr}
                                </div>
                                <div class="mt-2">
                                    <a href="${item.href}" target="_blank" class="btn btn-sm btn-secondary">
                                        상세보기 <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        };
        
        // ===================================
        // 초기화
        // ===================================
        document.addEventListener('DOMContentLoaded', function() {
            const excludeInput = document.getElementById('newExcludeKeyword');
            if (excludeInput) {
                excludeInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        addExcludeKeyword();
                    }
                });
            }
            
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.input-group') && !e.target.closest('.search-results')) {
                    hideSearchResults();
                }
            });
            
            loadRegions(1);
        });
        
    })();
    </script>
</body>
</html>