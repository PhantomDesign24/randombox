/*
 * 파일명: install.sql
 * 위치: /plugin/randombox/
 * 기능: 랜덤박스 시스템 데이터베이스 테이블 생성
 * 작성일: 2025-01-04
 * 수정일: 2025-07-17
 */

-- ===================================
-- 랜덤박스 메인 테이블
-- ===================================

/* 랜덤박스 정보 테이블 */
CREATE TABLE IF NOT EXISTS `g5_randombox` (
  `rb_id` int(11) NOT NULL AUTO_INCREMENT,
  `rb_name` varchar(255) NOT NULL DEFAULT '' COMMENT '박스명',
  `rb_desc` text COMMENT '박스 설명',
  `rb_price` int(11) NOT NULL DEFAULT '0' COMMENT '가격(포인트)',
  `rb_image` varchar(255) NOT NULL DEFAULT '' COMMENT '박스 이미지',
  `rb_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '상태(0:비활성,1:활성)',
  `rb_type` varchar(20) NOT NULL DEFAULT 'normal' COMMENT '박스 타입(normal,event,premium)',
  `rb_distribution_type` varchar(20) NOT NULL DEFAULT 'probability' COMMENT '분배방식(probability:확률,guaranteed:보장)',
  `rb_point_type` varchar(20) NOT NULL DEFAULT 'fixed' COMMENT '포인트타입(fixed:고정,random:랜덤)',
  `rb_point_min_multiplier` decimal(5,2) NOT NULL DEFAULT '1.00' COMMENT '최소배수',
  `rb_point_max_multiplier` decimal(5,2) NOT NULL DEFAULT '10.00' COMMENT '최대배수',
  `rb_start_date` datetime DEFAULT NULL COMMENT '판매 시작일',
  `rb_end_date` datetime DEFAULT NULL COMMENT '판매 종료일',
  `rb_limit_qty` int(11) NOT NULL DEFAULT '0' COMMENT '일일 구매 제한(0:무제한)',
  `rb_total_qty` int(11) NOT NULL DEFAULT '0' COMMENT '전체 판매 수량 제한(0:무제한)',
  `rb_sold_qty` int(11) NOT NULL DEFAULT '0' COMMENT '판매된 수량',
  `rb_show_remaining` tinyint(1) NOT NULL DEFAULT '0' COMMENT '남은수량표시',
  `rb_show_guaranteed_count` tinyint(1) NOT NULL DEFAULT '0' COMMENT '보장아이템개수표시',
  `rb_early_bird_bonus` tinyint(1) NOT NULL DEFAULT '0' COMMENT '얼리버드보너스',
  `rb_early_bird_count` int(11) NOT NULL DEFAULT '0' COMMENT '얼리버드인원',
  `rb_early_bird_bonus_rate` int(11) NOT NULL DEFAULT '0' COMMENT '보너스율(%)',
  `rb_shuffle_distribution` tinyint(1) NOT NULL DEFAULT '1' COMMENT '분배섞기',
  `rb_order` int(11) NOT NULL DEFAULT '0' COMMENT '정렬순서',
  `rb_created_at` datetime NOT NULL,
  `rb_updated_at` datetime NOT NULL,
  PRIMARY KEY (`rb_id`),
  KEY `idx_status_order` (`rb_status`,`rb_order`),
  KEY `idx_distribution` (`rb_distribution_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* 랜덤박스 아이템 테이블 */
CREATE TABLE IF NOT EXISTS `g5_randombox_items` (
  `rbi_id` int(11) NOT NULL AUTO_INCREMENT,
  `rb_id` int(11) NOT NULL DEFAULT '0' COMMENT '박스 ID',
  `rbi_name` varchar(255) NOT NULL DEFAULT '' COMMENT '아이템명',
  `rbi_desc` text COMMENT '아이템 설명',
  `rbi_image` varchar(255) NOT NULL DEFAULT '' COMMENT '아이템 이미지',
  `rbi_grade` varchar(20) NOT NULL DEFAULT 'normal' COMMENT '등급(normal,rare,epic,legendary)',
  `rbi_item_type` varchar(20) NOT NULL DEFAULT 'point' COMMENT '아이템 타입(point:포인트, coupon:교환권)',
  `rct_id` int(11) DEFAULT NULL COMMENT '교환권 타입 ID (교환권인 경우)',
  `rbi_probability` decimal(10,6) NOT NULL DEFAULT '0.000000' COMMENT '확률(%)',
  `rbi_value` int(11) NOT NULL DEFAULT '0' COMMENT '아이템 가치(포인트)',
  `rbi_point_random` tinyint(1) NOT NULL DEFAULT '0' COMMENT '랜덤 포인트 여부',
  `rbi_point_min` int(11) NOT NULL DEFAULT '0' COMMENT '최소 포인트',
  `rbi_point_max` int(11) NOT NULL DEFAULT '0' COMMENT '최대 포인트',
  `rbi_limit_qty` int(11) NOT NULL DEFAULT '0' COMMENT '최대 배출 수량(0:무제한)',
  `rbi_issued_qty` int(11) NOT NULL DEFAULT '0' COMMENT '배출된 수량',
  `rbi_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '상태(0:비활성,1:활성)',
  `rbi_order` int(11) NOT NULL DEFAULT '0' COMMENT '정렬순서',
  `rbi_created_at` datetime NOT NULL,
  `rbi_updated_at` datetime NOT NULL,
  PRIMARY KEY (`rbi_id`),
  KEY `idx_box_status` (`rb_id`,`rbi_status`),
  KEY `idx_grade` (`rbi_grade`),
  KEY `idx_item_type` (`rbi_item_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* 구매/획득 기록 테이블 */
CREATE TABLE IF NOT EXISTS `g5_randombox_history` (
  `rbh_id` int(11) NOT NULL AUTO_INCREMENT,
  `mb_id` varchar(50) NOT NULL DEFAULT '' COMMENT '회원 ID',
  `rb_id` int(11) NOT NULL DEFAULT '0' COMMENT '박스 ID',
  `rb_name` varchar(255) NOT NULL DEFAULT '' COMMENT '박스명(기록용)',
  `rb_price` int(11) NOT NULL DEFAULT '0' COMMENT '구매 가격(기록용)',
  `rbi_id` int(11) NOT NULL DEFAULT '0' COMMENT '획득 아이템 ID',
  `rbi_name` varchar(255) NOT NULL DEFAULT '' COMMENT '아이템명(기록용)',
  `rbi_grade` varchar(20) NOT NULL DEFAULT '' COMMENT '아이템 등급(기록용)',
  `rbi_value` int(11) NOT NULL DEFAULT '0' COMMENT '아이템 가치(기록용)',
  `rbh_item_type` varchar(20) NOT NULL DEFAULT 'point' COMMENT '획득 아이템 타입',
  `rmc_id` int(11) DEFAULT NULL COMMENT '발급된 교환권 ID',
  `rbh_status` varchar(20) NOT NULL DEFAULT 'completed' COMMENT '상태(pending,completed,gift)',
  `rbh_ip` varchar(50) NOT NULL DEFAULT '' COMMENT '구매 IP',
  `rbh_created_at` datetime NOT NULL,
  PRIMARY KEY (`rbh_id`),
  KEY `idx_member` (`mb_id`,`rbh_created_at`),
  KEY `idx_box` (`rb_id`),
  KEY `idx_item` (`rbi_id`),
  KEY `idx_created` (`rbh_created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* 시스템 설정 테이블 */
CREATE TABLE IF NOT EXISTS `g5_randombox_config` (
  `cfg_name` varchar(50) NOT NULL DEFAULT '' COMMENT '설정명',
  `cfg_value` text COMMENT '설정값',
  `cfg_desc` varchar(255) NOT NULL DEFAULT '' COMMENT '설정 설명',
  PRIMARY KEY (`cfg_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* 천장 시스템 테이블 */
CREATE TABLE IF NOT EXISTS `g5_randombox_ceiling` (
  `rbc_id` int(11) NOT NULL AUTO_INCREMENT,
  `mb_id` varchar(50) NOT NULL DEFAULT '' COMMENT '회원 ID',
  `rb_id` int(11) NOT NULL DEFAULT '0' COMMENT '박스 ID',
  `rbc_count` int(11) NOT NULL DEFAULT '0' COMMENT '현재 뽑기 횟수',
  `rbc_last_rare` int(11) NOT NULL DEFAULT '0' COMMENT '마지막 레어 획득 시 카운트',
  `rbc_updated_at` datetime NOT NULL,
  PRIMARY KEY (`rbc_id`),
  UNIQUE KEY `idx_member_box` (`mb_id`,`rb_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* 선물하기 테이블 */
CREATE TABLE IF NOT EXISTS `g5_randombox_gift` (
  `rbg_id` int(11) NOT NULL AUTO_INCREMENT,
  `send_mb_id` varchar(50) NOT NULL DEFAULT '' COMMENT '보낸 회원 ID',
  `recv_mb_id` varchar(50) NOT NULL DEFAULT '' COMMENT '받는 회원 ID',
  `rb_id` int(11) NOT NULL DEFAULT '0' COMMENT '박스 ID',
  `rbg_quantity` int(11) NOT NULL DEFAULT '1' COMMENT '선물 수량',
  `rbg_message` text COMMENT '선물 메시지',
  `rbg_status` varchar(20) NOT NULL DEFAULT 'pending' COMMENT '상태(pending,accepted,rejected)',
  `rbg_created_at` datetime NOT NULL,
  `rbg_accepted_at` datetime DEFAULT NULL,
  PRIMARY KEY (`rbg_id`),
  KEY `idx_receiver` (`recv_mb_id`,`rbg_status`),
  KEY `idx_sender` (`send_mb_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ===================================
-- 교환권 시스템 테이블
-- ===================================

/* 교환권 타입 정의 테이블 */
CREATE TABLE IF NOT EXISTS `g5_randombox_coupon_types` (
  `rct_id` int(11) NOT NULL AUTO_INCREMENT,
  `rct_name` varchar(255) NOT NULL DEFAULT '' COMMENT '교환권 타입명',
  `rct_desc` text COMMENT '교환권 설명',
  `rct_type` varchar(50) NOT NULL DEFAULT 'exchange' COMMENT '타입(exchange:교환용, gifticon:기프티콘)',
  `rct_image` varchar(255) NOT NULL DEFAULT '' COMMENT '교환권 이미지',
  `rct_value` int(11) NOT NULL DEFAULT '0' COMMENT '교환권 가치(포인트)',
  `rct_exchange_item` varchar(255) NOT NULL DEFAULT '' COMMENT '교환 가능 상품명',
  `rct_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '상태(0:비활성,1:활성)',
  `rct_created_at` datetime NOT NULL,
  `rct_updated_at` datetime NOT NULL,
  PRIMARY KEY (`rct_id`),
  KEY `idx_type_status` (`rct_type`,`rct_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* 교환권 코드 풀 테이블 (기프티콘용) */
CREATE TABLE IF NOT EXISTS `g5_randombox_coupon_codes` (
  `rcc_id` int(11) NOT NULL AUTO_INCREMENT,
  `rct_id` int(11) NOT NULL DEFAULT '0' COMMENT '교환권 타입 ID',
  `rcc_code` varchar(100) NOT NULL DEFAULT '' COMMENT '교환권 코드',
  `rcc_pin` varchar(100) DEFAULT NULL COMMENT 'PIN 번호 (있는 경우)',
  `rcc_expire_date` date DEFAULT NULL COMMENT '유효기간',
  `rcc_status` varchar(20) NOT NULL DEFAULT 'available' COMMENT '상태(available:사용가능, used:사용됨, expired:만료)',
  `rcc_used_by` varchar(50) DEFAULT NULL COMMENT '사용한 회원 ID',
  `rcc_used_at` datetime DEFAULT NULL COMMENT '사용 일시',
  `rcc_created_at` datetime NOT NULL,
  PRIMARY KEY (`rcc_id`),
  UNIQUE KEY `idx_code` (`rcc_code`),
  KEY `idx_type_status` (`rct_id`,`rcc_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* 회원 보유 교환권 테이블 */
CREATE TABLE IF NOT EXISTS `g5_randombox_member_coupons` (
  `rmc_id` int(11) NOT NULL AUTO_INCREMENT,
  `mb_id` varchar(50) NOT NULL DEFAULT '' COMMENT '회원 ID',
  `rct_id` int(11) NOT NULL DEFAULT '0' COMMENT '교환권 타입 ID',
  `rcc_id` int(11) DEFAULT NULL COMMENT '교환권 코드 ID (기프티콘인 경우)',
  `rbh_id` int(11) NOT NULL DEFAULT '0' COMMENT '획득한 히스토리 ID',
  `rmc_status` varchar(20) NOT NULL DEFAULT 'active' COMMENT '상태(active:보유중, used:사용완료, expired:만료)',
  `rmc_used_at` datetime DEFAULT NULL COMMENT '사용 일시',
  `rmc_expire_date` date DEFAULT NULL COMMENT '유효기간',
  `rmc_created_at` datetime NOT NULL,
  PRIMARY KEY (`rmc_id`),
  KEY `idx_member_status` (`mb_id`,`rmc_status`),
  KEY `idx_coupon_type` (`rct_id`),
  KEY `idx_history` (`rbh_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* 교환권 사용 기록 테이블 */
CREATE TABLE IF NOT EXISTS `g5_randombox_coupon_use_log` (
  `rcl_id` int(11) NOT NULL AUTO_INCREMENT,
  `mb_id` varchar(50) NOT NULL DEFAULT '' COMMENT '회원 ID',
  `rmc_id` int(11) NOT NULL DEFAULT '0' COMMENT '회원 보유 교환권 ID',
  `rct_id` int(11) NOT NULL DEFAULT '0' COMMENT '교환권 타입 ID',
  `rcl_type` varchar(50) NOT NULL DEFAULT '' COMMENT '사용 타입(exchange:교환, view:조회)',
  `rcl_memo` text COMMENT '사용 메모',
  `rcl_ip` varchar(50) NOT NULL DEFAULT '' COMMENT 'IP 주소',
  `rcl_created_at` datetime NOT NULL,
  PRIMARY KEY (`rcl_id`),
  KEY `idx_member` (`mb_id`,`rcl_created_at`),
  KEY `idx_coupon` (`rmc_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* 보장된 분배 시스템 테이블 */
CREATE TABLE IF NOT EXISTS `g5_randombox_guaranteed` (
  `rbg_id` int(11) NOT NULL AUTO_INCREMENT,
  `rb_id` int(11) NOT NULL COMMENT '박스 ID',
  `rbg_total_count` int(11) NOT NULL DEFAULT '0' COMMENT '전체 개수',
  `rbg_distributed` text COMMENT '분배 리스트 (JSON)',
  `rbg_current_index` int(11) NOT NULL DEFAULT '0' COMMENT '현재 인덱스',
  `rbg_created_at` datetime NOT NULL,
  PRIMARY KEY (`rbg_id`),
  UNIQUE KEY `idx_box` (`rb_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- ===================================
-- 기본 설정 데이터 삽입
-- ===================================

/* 시스템 기본 설정값 */
INSERT INTO `g5_randombox_config` (`cfg_name`, `cfg_value`, `cfg_desc`) VALUES
('system_enable', '1', '전체 시스템 활성화 여부'),
('show_probability', '1', '확률 공개 여부'),
('enable_ceiling', '1', '천장 시스템 사용 여부'),
('ceiling_count', '100', '천장 카운트 (레어 이상 보장)'),
('enable_gift', '1', '선물하기 기능 사용 여부'),
('enable_history', '1', '구매내역 공개 여부'),
('enable_realtime', '1', '실시간 당첨 현황 표시 여부'),
('daily_free_count', '0', '일일 무료 뽑기 횟수'),
('min_level', '1', '최소 이용 가능 레벨'),
('maintenance_mode', '0', '점검 모드'),
('maintenance_msg', '시스템 점검 중입니다.', '점검 메시지');

-- ===================================
-- 샘플 데이터 (선택사항)
-- ===================================

/* 샘플 교환권 타입 */
INSERT INTO `g5_randombox_coupon_types` (`rct_name`, `rct_desc`, `rct_type`, `rct_value`, `rct_exchange_item`, `rct_status`, `rct_created_at`, `rct_updated_at`) VALUES
('스타벅스 아메리카노', '스타벅스 아메리카노 Tall 사이즈 교환권', 'gifticon', 4500, '스타벅스 아메리카노 Tall', 1, NOW(), NOW()),
('배달의민족 1만원권', '배달의민족 1만원 할인 쿠폰', 'gifticon', 10000, '배달의민족 1만원 할인', 1, NOW(), NOW()),
('쿠팡 로켓배송 1개월권', '쿠팡 로켓와우 멤버십 1개월 이용권', 'exchange', 2990, '쿠팡 로켓와우 1개월', 1, NOW(), NOW()),
('넷플릭스 1개월권', '넷플릭스 베이직 1개월 이용권', 'exchange', 9500, '넷플릭스 베이직 1개월', 1, NOW(), NOW()),
('GS25 모바일상품권 5천원', 'GS25 편의점 5천원 모바일상품권', 'gifticon', 5000, 'GS25 5천원권', 1, NOW(), NOW());

/* 샘플 박스 - 일반 확률 박스 */
INSERT INTO `g5_randombox` (`rb_name`, `rb_desc`, `rb_price`, `rb_status`, `rb_type`, `rb_distribution_type`, `rb_point_type`, `rb_created_at`, `rb_updated_at`) VALUES
('일반 포인트 박스', '다양한 포인트를 획득할 수 있는 기본 박스', 1000, 1, 'normal', 'probability', 'fixed', NOW(), NOW()),
('프리미엄 랜덤 박스', '고급 아이템과 대량 포인트 획득 기회!', 5000, 1, 'premium', 'probability', 'fixed', NOW(), NOW()),
('신규회원 환영 박스', '신규회원을 위한 특별 혜택 박스', 500, 1, 'event', 'probability', 'random', NOW(), NOW());

/* 샘플 박스 - 배민쿠폰 이벤트 (보장된 분배) */
INSERT INTO `g5_randombox` (`rb_name`, `rb_desc`, `rb_price`, `rb_status`, `rb_type`, `rb_distribution_type`, `rb_point_type`, `rb_point_min_multiplier`, `rb_point_max_multiplier`, `rb_total_qty`, `rb_show_remaining`, `rb_show_guaranteed_count`, `rb_created_at`, `rb_updated_at`) VALUES
('배민 1만원 쿠폰 이벤트', '선착순 1000명! 10명에게 배민 1만원 쿠폰 증정!', 1000, 1, 'event', 'guaranteed', 'random', 1.00, 10.00, 1000, 1, 1, NOW(), NOW());

/* 샘플 아이템 - 일반 포인트 박스용 */
INSERT INTO `g5_randombox_items` (`rb_id`, `rbi_name`, `rbi_desc`, `rbi_grade`, `rbi_item_type`, `rbi_probability`, `rbi_value`, `rbi_status`, `rbi_order`, `rbi_created_at`, `rbi_updated_at`) VALUES
(1, '100 포인트', '소량의 포인트를 획득합니다', 'normal', 'point', 50.000000, 100, 1, 1, NOW(), NOW()),
(1, '500 포인트', '적당한 포인트를 획득합니다', 'normal', 'point', 30.000000, 500, 1, 2, NOW(), NOW()),
(1, '1,000 포인트', '많은 포인트를 획득합니다!', 'rare', 'point', 15.000000, 1000, 1, 3, NOW(), NOW()),
(1, '5,000 포인트', '대량의 포인트를 획득합니다!!', 'epic', 'point', 4.000000, 5000, 1, 4, NOW(), NOW()),
(1, '10,000 포인트', '초대박! 만 포인트 획득!!!', 'legendary', 'point', 1.000000, 10000, 1, 5, NOW(), NOW());

/* 샘플 아이템 - 프리미엄 랜덤 박스용 */
INSERT INTO `g5_randombox_items` (`rb_id`, `rbi_name`, `rbi_desc`, `rbi_grade`, `rbi_item_type`, `rct_id`, `rbi_probability`, `rbi_value`, `rbi_limit_qty`, `rbi_status`, `rbi_order`, `rbi_created_at`, `rbi_updated_at`) VALUES
(2, '5,000 포인트', '기본 보상 포인트', 'normal', 'point', NULL, 60.000000, 5000, 0, 1, 1, NOW(), NOW()),
(2, '10,000 포인트', '럭키 포인트!', 'rare', 'point', NULL, 25.000000, 10000, 0, 1, 2, NOW(), NOW()),
(2, '스타벅스 아메리카노', '스타벅스 커피 교환권', 'epic', 'coupon', 1, 10.000000, 0, 50, 1, 3, NOW(), NOW()),
(2, 'GS25 5천원권', 'GS25 모바일상품권', 'epic', 'coupon', 5, 4.000000, 0, 20, 1, 4, NOW(), NOW()),
(2, '배민 1만원권', '배달의민족 할인쿠폰', 'legendary', 'coupon', 2, 1.000000, 0, 5, 1, 5, NOW(), NOW());

/* 등급 정의 테이블 */
CREATE TABLE IF NOT EXISTS `randombox_grades` (
  `rbg_id` int(11) NOT NULL AUTO_INCREMENT,
  `rbg_key` varchar(50) NOT NULL COMMENT '등급 키 (normal, rare, epic, legendary)',
  `rbg_name` varchar(100) NOT NULL COMMENT '등급명',
  `rbg_color` varchar(7) NOT NULL DEFAULT '#666666' COMMENT '등급 색상',
  `rbg_icon` varchar(100) DEFAULT NULL COMMENT '등급 아이콘',
  `rbg_image` varchar(255) DEFAULT NULL COMMENT '등급 이미지',
  `rbg_level` int(11) NOT NULL DEFAULT '1' COMMENT '등급 레벨',
  `rbg_order` int(11) NOT NULL DEFAULT '0' COMMENT '정렬 순서',
  `rbg_created_at` datetime NOT NULL,
  `rbg_updated_at` datetime NOT NULL,
  PRIMARY KEY (`rbg_id`),
  UNIQUE KEY `idx_key` (`rbg_key`),
  KEY `idx_level` (`rbg_level`),
  KEY `idx_order` (`rbg_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* 기본 등급 데이터 삽입 */
INSERT INTO `randombox_grades` (`rbg_key`, `rbg_name`, `rbg_color`, `rbg_level`, `rbg_order`, `rbg_created_at`, `rbg_updated_at`) VALUES
('normal', '일반', '#666666', 1, 1, NOW(), NOW()),
('rare', '레어', '#0969da', 2, 2, NOW(), NOW()),
('epic', '에픽', '#6f42c1', 3, 3, NOW(), NOW()),
('legendary', '레전더리', '#cf222e', 4, 4, NOW(), NOW());

/* 샘플 아이템 - 신규회원 환영 박스용 (랜덤 포인트) */
INSERT INTO `g5_randombox_items` (`rb_id`, `rbi_name`, `rbi_desc`, `rbi_grade`, `rbi_item_type`, `rbi_probability`, `rbi_value`, `rbi_point_random`, `rbi_point_min`, `rbi_point_max`, `rbi_status`, `rbi_order`, `rbi_created_at`, `rbi_updated_at`) VALUES
(3, '환영 포인트 (1~5배)', '박스 가격의 1~5배 랜덤 포인트', 'normal', 'point', 70.000000, 0, 1, 500, 2500, 1, 1, NOW(), NOW()),
(3, '럭키 포인트 (5~10배)', '박스 가격의 5~10배 랜덤 포인트', 'rare', 'point', 25.000000, 0, 1, 2500, 5000, 1, 2, NOW(), NOW()),
(3, '대박 포인트 (10~20배)', '박스 가격의 10~20배 랜덤 포인트!', 'epic', 'point', 5.000000, 0, 1, 5000, 10000, 1, 3, NOW(), NOW());

/* 샘플 아이템 - 배민쿠폰 이벤트용 (보장된 분배) */
INSERT INTO `g5_randombox_items` (`rb_id`, `rbi_name`, `rbi_desc`, `rbi_grade`, `rbi_item_type`, `rct_id`, `rbi_probability`, `rbi_value`, `rbi_limit_qty`, `rbi_status`, `rbi_order`, `rbi_created_at`, `rbi_updated_at`) VALUES
(4, '배민 1만원 쿠폰', '배달의민족 1만원 할인 쿠폰', 'legendary', 'coupon', 2, 1.000000, 0, 10, 1, 0, NOW(), NOW()),
(4, '포인트 (1~10배)', '구매 금액의 1~10배 포인트', 'normal', 'point', NULL, 99.000000, 0, 0, 1, 1, NOW(), NOW());

/* 샘플 기프티콘 코드 (예시) */
INSERT INTO `g5_randombox_coupon_codes` (`rct_id`, `rcc_code`, `rcc_pin`, `rcc_expire_date`, `rcc_status`, `rcc_created_at`) VALUES
(1, 'STAR-2025-0001', '1234', '2025-12-31', 'available', NOW()),
(1, 'STAR-2025-0002', '5678', '2025-12-31', 'available', NOW()),
(2, 'BAEMIN-2025-0001', NULL, '2025-12-31', 'available', NOW()),
(2, 'BAEMIN-2025-0002', NULL, '2025-12-31', 'available', NOW()),
(5, 'GS25-2025-0001', '1111', '2025-12-31', 'available', NOW());