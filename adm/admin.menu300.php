<?php
$menu['menu300'] = array(
    array('300000', '게시판관리', '' . G5_ADMIN_URL . '/board_list.php', 'board'),
    array('300100', '게시판관리', '' . G5_ADMIN_URL . '/board_list.php', 'bbs_board'),
    array('300200', '게시판그룹관리', '' . G5_ADMIN_URL . '/boardgroup_list.php', 'bbs_group'),
    array('300300', '인기검색어관리', '' . G5_ADMIN_URL . '/popular_list.php', 'bbs_poplist', 1),
    array('300400', '인기검색어순위', '' . G5_ADMIN_URL . '/popular_rank.php', 'bbs_poprank', 1),
    array('300500', '1:1문의설정', '' . G5_ADMIN_URL . '/qa_config.php', 'qa'),
    array('300600', '내용관리', G5_ADMIN_URL . '/contentlist.php', 'scf_contents', 1),
    array('300700', 'FAQ관리', G5_ADMIN_URL . '/faqmasterlist.php', 'scf_faq', 1),
    array('300820', '글,댓글 현황', G5_ADMIN_URL . '/write_count.php', 'scf_write_count'),
);
/* 랜덤박스 메뉴 항목 추가 */
$menu['menu300'][] = array('300900', '랜덤박스', G5_ADMIN_URL . '/randombox_admin/plugin.php', 'randombox');
$menu['menu300'][] = array('300910', '랜덤박스 설정', G5_ADMIN_URL . '/randombox_admin/config.php', 'randombox_config');
$menu['menu300'][] = array('300915', '등급 관리', G5_ADMIN_URL . '/randombox_admin/grade_list.php', 'randombox_grade');  // 새로 추가
$menu['menu300'][] = array('300920', '박스 관리', G5_ADMIN_URL . '/randombox_admin/box_list.php', 'randombox_box');
$menu['menu300'][] = array('300930', '아이템 관리', G5_ADMIN_URL . '/randombox_admin/item_list.php', 'randombox_item');
$menu['menu300'][] = array('300940', '통계 관리', G5_ADMIN_URL . '/randombox_admin/statistics.php', 'randombox_stats');