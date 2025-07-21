<?php
/*
 * 파일명: box_form_extended.php
 * 위치: /adm/randombox_admin/
 * 기능: 박스 등록/수정 폼 확장 - 분배 방식 설정
 * 작성일: 2025-07-07
 */

// 기존 box_form.php에 추가할 섹션
?>

<!-- ===================================
 * 분배 방식 설정 (추가 섹션)
 * =================================== -->
 
<h2 class="h2_frm" style="margin-top:30px;">분배 방식 설정</h2>

<div class="tbl_frm01 tbl_wrap">
    <table>
    <colgroup>
        <col class="grid_4">
        <col>
    </colgroup>
    <tbody>
    <tr>
        <th scope="row">분배 방식</th>
        <td>
            <label>
                <input type="radio" name="rb_distribution_type" value="probability" 
                       <?php echo (!$box['rb_distribution_type'] || $box['rb_distribution_type'] == 'probability') ? 'checked' : ''; ?>
                       onclick="changeDistributionType('probability')">
                <strong>확률 기반</strong> - 일반적인 랜덤박스 방식
            </label>
            <p class="frm_info" style="margin-left:25px;">
                설정한 확률에 따라 아이템이 랜덤하게 당첨됩니다.<br>
                정확한 개수를 보장하지 않지만 자연스러운 분포를 만듭니다.
            </p>
            
            <label style="margin-top:10px;display:block;">
                <input type="radio" name="rb_distribution_type" value="guaranteed" 
                       <?php echo ($box['rb_distribution_type'] == 'guaranteed') ? 'checked' : ''; ?>
                       onclick="changeDistributionType('guaranteed')">
                <strong>보장된 분배</strong> - 정확한 개수 보장
            </label>
            <p class="frm_info" style="margin-left:25px;">
                설정한 개수만큼 정확하게 당첨되도록 보장합니다.<br>
                예: 1000개 중 쿠폰 10개 설정 시 반드시 10개만 당첨
            </p>
        </td>
    </tr>
    
    <!-- 보장된 분배 설정 -->
    <tbody id="guaranteed_settings" style="<?php echo ($box['rb_distribution_type'] != 'guaranteed') ? 'display:none;' : ''; ?>">
    <tr>
        <th scope="row">보장 아이템 설정</th>
        <td>
            <div class="guaranteed_item_list">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>아이템</th>
                            <th>보장 개수</th>
                            <th>삭제</th>
                        </tr>
                    </thead>
                    <tbody id="guaranteed_items">
                        <?php 
                        // 기존 보장 설정 표시
                        if ($box['rb_distribution_type'] == 'guaranteed') {
                            $guaranteed = get_box_guaranteed_settings($rb_id);
                            foreach ($guaranteed as $g_item) {
                        ?>
                        <tr>
                            <td>
                                <select name="guaranteed_item_id[]" class="frm_input">
                                    <?php
                                    $items = get_randombox_items($rb_id);
                                    foreach ($items as $item) {
                                        $selected = ($item['rbi_id'] == $g_item['rbi_id']) ? 'selected' : '';
                                        echo '<option value="'.$item['rbi_id'].'" '.$selected.'>'.$item['rbi_name'].'</option>';
                                    }
                                    ?>
                                </select>
                            </td>
                            <td>
                                <input type="number" name="guaranteed_count[]" value="<?php echo $g_item['count']; ?>" 
                                       class="frm_input" size="10" min="1">
                            </td>
                            <td>
                                <button type="button" class="btn btn_01" onclick="removeGuaranteedItem(this)">삭제</button>
                            </td>
                        </tr>
                        <?php 
                            }
                        }
                        ?>
                    </tbody>
                </table>
                <button type="button" class="btn btn_02" onclick="addGuaranteedItem()">+ 보장 아이템 추가</button>
            </div>
            <p class="frm_info">
                보장 개수의 합은 전체 판매 수량보다 작아야 합니다.<br>
                나머지는 다른 아이템(주로 포인트)으로 자동 채워집니다.
            </p>
        </td>
    </tr>
    <tr>
        <th scope="row">분배 순서</th>
        <td>
            <label>
                <input type="checkbox" name="rb_shuffle_distribution" value="1" 
                       <?php echo $box['rb_shuffle_distribution'] ? 'checked' : ''; ?>>
                무작위 섞기 (권장)
            </label>
            <p class="frm_info">
                체크하면 보장된 아이템들이 무작위 순서로 배치됩니다.<br>
                체크 해제 시 앞부분에 특정 아이템이 몰릴 수 있습니다.
            </p>
        </td>
    </tr>
    </tbody>
    
    <!-- 포인트 설정 -->
    <tr>
        <th scope="row">포인트 지급 방식</th>
        <td>
            <label>
                <input type="radio" name="rb_point_type" value="fixed" 
                       <?php echo (!$box['rb_point_type'] || $box['rb_point_type'] == 'fixed') ? 'checked' : ''; ?>
                       onclick="changePointType('fixed')">
                고정 포인트
            </label>
            
            <label style="margin-left:20px;">
                <input type="radio" name="rb_point_type" value="random" 
                       <?php echo ($box['rb_point_type'] == 'random') ? 'checked' : ''; ?>
                       onclick="changePointType('random')">
                랜덤 포인트
            </label>
            
            <div id="point_random_settings" style="margin-top:10px; <?php echo ($box['rb_point_type'] != 'random') ? 'display:none;' : ''; ?>">
                <label>
                    배수 설정:
                    최소 <input type="number" name="rb_point_min_multiplier" value="<?php echo $box['rb_point_min_multiplier'] ?: 1; ?>" 
                           class="frm_input" size="5" min="0.1" max="100" step="0.1"> 배
                    ~
                    최대 <input type="number" name="rb_point_max_multiplier" value="<?php echo $box['rb_point_max_multiplier'] ?: 10; ?>" 
                           class="frm_input" size="5" min="0.1" max="100" step="0.1"> 배
                </label>
                <p class="frm_info">
                    박스 가격의 배수로 포인트가 지급됩니다.<br>
                    예: 1,000원 박스에서 1~10배 설정 시 1,000~10,000 포인트 랜덤 지급
                </p>
            </div>
        </td>
    </tr>
    
    <!-- 고급 설정 -->
    <tr>
        <th scope="row">고급 설정</th>
        <td>
            <label>
                <input type="checkbox" name="rb_show_remaining" value="1" 
                       <?php echo $box['rb_show_remaining'] ? 'checked' : ''; ?>>
                남은 수량 표시
            </label>
            <p class="frm_info">사용자에게 남은 판매 수량을 표시합니다.</p>
            
            <label style="margin-top:10px;display:block;">
                <input type="checkbox" name="rb_show_guaranteed_count" value="1" 
                       <?php echo $box['rb_show_guaranteed_count'] ? 'checked' : ''; ?>>
                남은 특별 아이템 개수 표시 (보장된 분배 시)
            </label>
            <p class="frm_info">예: "남은 쿠폰: 7개"</p>
            
            <label style="margin-top:10px;display:block;">
                <input type="checkbox" name="rb_early_bird_bonus" value="1" 
                       <?php echo $box['rb_early_bird_bonus'] ? 'checked' : ''; ?>>
                얼리버드 보너스
            </label>
            <div id="early_bird_settings" style="margin-left:25px; <?php echo (!$box['rb_early_bird_bonus']) ? 'display:none;' : ''; ?>">
                선착 <input type="number" name="rb_early_bird_count" value="<?php echo $box['rb_early_bird_count'] ?: 100; ?>" 
                       class="frm_input" size="8" min="1"> 명에게
                <input type="number" name="rb_early_bird_bonus_rate" value="<?php echo $box['rb_early_bird_bonus_rate'] ?: 20; ?>" 
                       class="frm_input" size="5" min="1" max="100"> % 추가 포인트
            </div>
        </td>
    </tr>
    </tbody>
    </table>
</div>

<script>
// 분배 방식 변경
function changeDistributionType(type) {
    if (type == 'guaranteed') {
        $('#guaranteed_settings').show();
        // 확률 입력 비활성화
        $('input[name^="rbi_probability"]').prop('readonly', true).css('background', '#f0f0f0');
    } else {
        $('#guaranteed_settings').hide();
        // 확률 입력 활성화
        $('input[name^="rbi_probability"]').prop('readonly', false).css('background', '');
    }
}

// 포인트 타입 변경
function changePointType(type) {
    if (type == 'random') {
        $('#point_random_settings').show();
    } else {
        $('#point_random_settings').hide();
    }
}

// 보장 아이템 추가
function addGuaranteedItem() {
    var html = '<tr>' +
        '<td><select name="guaranteed_item_id[]" class="frm_input">' +
        '<?php 
        $items = get_randombox_items($rb_id);
        foreach ($items as $item) {
            echo '<option value="'.$item['rbi_id'].'">'.$item['rbi_name'].'</option>';
        }
        ?>' +
        '</select></td>' +
        '<td><input type="number" name="guaranteed_count[]" value="1" class="frm_input" size="10" min="1"></td>' +
        '<td><button type="button" class="btn btn_01" onclick="removeGuaranteedItem(this)">삭제</button></td>' +
        '</tr>';
    
    $('#guaranteed_items').append(html);
}

// 보장 아이템 삭제
function removeGuaranteedItem(btn) {
    $(btn).closest('tr').remove();
}

// 얼리버드 보너스 토글
$('input[name="rb_early_bird_bonus"]').on('change', function() {
    if (this.checked) {
        $('#early_bird_settings').show();
    } else {
        $('#early_bird_settings').hide();
    }
});

// 폼 검증 추가
function fboxform_submit(f) {
    // 기존 검증...
    
    // 보장된 분배 검증
    if (f.rb_distribution_type.value == 'guaranteed') {
        var total_guaranteed = 0;
        $('input[name="guaranteed_count[]"]').each(function() {
            total_guaranteed += parseInt($(this).val()) || 0;
        });
        
        var total_qty = parseInt(f.rb_total_qty.value) || 0;
        if (total_guaranteed >= total_qty) {
            alert('보장 개수의 합이 전체 판매 수량보다 작아야 합니다.');
            return false;
        }
    }
    
    return true;
}
</script>

<!-- ===================================
 * 실시간 미리보기 (추가 섹션)
 * =================================== -->

<h2 class="h2_frm" style="margin-top:30px;">설정 미리보기</h2>

<div class="local_desc01 local_desc">
    <div id="settings_preview">
        <h4>현재 설정 요약</h4>
        <ul>
            <li>박스 가격: <strong><?php echo number_format($box['rb_price']); ?>P</strong></li>
            <li>전체 수량: <strong><?php echo number_format($box['rb_total_qty']); ?>개</strong></li>
            <li>분배 방식: <strong id="preview_distribution">확률 기반</strong></li>
            <li>포인트: <strong id="preview_point">고정</strong></li>
        </ul>
        
        <div id="preview_guaranteed" style="display:none;">
            <h5>보장된 아이템:</h5>
            <ul id="preview_guaranteed_list">
            </ul>
        </div>
    </div>
</div>

<script>
// 설정 변경 시 미리보기 업데이트
$('input[name="rb_distribution_type"], input[name="rb_point_type"]').on('change', updatePreview);
$('input[name="guaranteed_count[]"], select[name="guaranteed_item_id[]"]').on('change', updatePreview);

function updatePreview() {
    // 분배 방식
    var dist_type = $('input[name="rb_distribution_type"]:checked').val();
    $('#preview_distribution').text(dist_type == 'guaranteed' ? '보장된 분배' : '확률 기반');
    
    // 포인트 방식
    var point_type = $('input[name="rb_point_type"]:checked').val();
    if (point_type == 'random') {
        var min_mult = $('input[name="rb_point_min_multiplier"]').val() || 1;
        var max_mult = $('input[name="rb_point_max_multiplier"]').val() || 10;
        $('#preview_point').text('랜덤 (' + min_mult + '~' + max_mult + '배)');
    } else {
        $('#preview_point').text('고정');
    }
    
    // 보장된 아이템
    if (dist_type == 'guaranteed') {
        $('#preview_guaranteed').show();
        var list_html = '';
        $('select[name="guaranteed_item_id[]"]').each(function(idx) {
            var item_name = $(this).find('option:selected').text();
            var count = $('input[name="guaranteed_count[]"]').eq(idx).val();
            if (count > 0) {
                list_html += '<li>' + item_name + ': ' + count + '개</li>';
            }
        });
        $('#preview_guaranteed_list').html(list_html);
    } else {
        $('#preview_guaranteed').hide();
    }
}

// 초기 미리보기
$(document).ready(function() {
    updatePreview();
});
</script>