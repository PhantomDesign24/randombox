/*
 * 파일명: install.sql
 * 위치: /plugin/randombox/
 * 기능: 랜덤박스 시스템 데이터베이스 테이블 생성
 * 작성일: 2025-01-04
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
  `rb_start_date` datetime DEFAULT NULL COMMENT '판매 시작일',
  `rb_end_date` datetime DEFAULT NULL COMMENT '판매 종료일',
  `rb_limit_qty` int(11) NOT NULL DEFAULT '0' COMMENT '일일 구매 제한(0:무제한)',
  `rb_total_qty` int(11) NOT NULL DEFAULT '0' COMMENT '전체 판매 수량 제한(0:무제한)',
  `rb_sold_qty` int(11) NOT NULL DEFAULT '0' COMMENT '판매된 수량',
  `rb_order` int(11) NOT NULL DEFAULT '0' COMMENT '정렬순서',
  `rb_created_at` datetime NOT NULL,
  `rb_updated_at` datetime NOT NULL,
  PRIMARY KEY (`rb_id`),
  KEY `idx_status_order` (`rb_status`,`rb_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/* 랜덤박스 아이템 테이블 */
CREATE TABLE IF NOT EXISTS `g5_randombox_items` (
  `rbi_id` int(11) NOT NULL AUTO_INCREMENT,
  `rb_id` int(11) NOT NULL DEFAULT '0' COMMENT '박스 ID',
  `rbi_name` varchar(255) NOT NULL DEFAULT '' COMMENT '아이템명',
  `rbi_desc` text COMMENT '아이템 설명',
  `rbi_image` varchar(255) NOT NULL DEFAULT '' COMMENT '아이템 이미지',
  `rbi_grade` varchar(20) NOT NULL DEFAULT 'normal' COMMENT '등급(normal,rare,epic,legendary)',
  `rbi_probability` decimal(10,6) NOT NULL DEFAULT '0.000000' COMMENT '확률(%)',
  `rbi_value` int(11) NOT NULL DEFAULT '0' COMMENT '아이템 가치(포인트)',
  `rbi_limit_qty` int(11) NOT NULL DEFAULT '0' COMMENT '최대 배출 수량(0:무제한)',
  `rbi_issued_qty` int(11) NOT NULL DEFAULT '0' COMMENT '배출된 수량',
  `rbi_status` tinyint(4) NOT NULL DEFAULT '1' COMMENT '상태(0:비활성,1:활성)',
  `rbi_order` int(11) NOT NULL DEFAULT '0' COMMENT '정렬순서',
  `rbi_created_at` datetime NOT NULL,
  `rbi_updated_at` datetime NOT NULL,
  PRIMARY KEY (`rbi_id`),
  KEY `idx_box_status` (`rb_id`,`rbi_status`),
  KEY `idx_grade` (`rbi_grade`)
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