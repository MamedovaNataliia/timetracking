<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

$image_dir = '/local/components/ebola/ebola.timetracking/templates/default/images';
?>
<? if ($arResult['IS_TRACKING'] == 'Y') {
    $_SESSION['need_tracking'] = 'Y';
    $minutes = $arResult['TIME_INTERVAL'];

    $content = '';
    if ($arResult['IS_FRIDAY']) {
        $content = GetMessage('MESSAGE_REMAIDER_FRIDAY');
    } elseif($arResult['IS_HOLIDAY']) {
        switch ($arResult['CUR_WEEK_DAY']){
            case 'Monday':
                $content = GetMessage('MESSAGE_REMAIDER_MONDAY');
                break;
            case 'Tuesday':
                $content = GetMessage('MESSAGE_REMAIDER_TUESDAY');
                break;
            case 'Wednesday':
                $content = GetMessage('MESSAGE_REMAIDER_WEDNESDAY');
                break;
            case 'Thursday':
                $content = GetMessage('MESSAGE_REMAIDER_THURSDAY');
                break;
            case 'Friday':
                $content = GetMessage('MESSAGE_REMAIDER_FRIDAY');
                break;
        }

    }else{
        $content = GetMessage('MESSAGE_REMAIDER_ALL_DAYS');
    }
    $image_src = '';
    $url ='';
    if ($arResult['HOURS'] == 0) {
        $image_src = $image_dir . '/null_hours.jpg';
    }
    if ($arResult['HOURS'] > 0 && $arResult['HOURS'] < 420) {
        $image_src = $image_dir . '/more_null_hours.jpg';
    }
    $arDate = getdate(time());
    if ($arDate['hours'] >= 10 && $arDate['hours'] <= 14) {
        $url = $image_src;
    }
    if ($arDate['hours'] >= 14 && $arDate['hours'] <= 18) {
        $url = substr_replace($image_src, '_d.jpg', -4);
    }
    if (($arDate['hours'] >= 18 && $arDate['hours'] <= 24) || ($arDate['hours'] >= 1 && $arDate['hours'] <= 9)) {
        $url = substr_replace($image_src, '_d18.jpg', -4);
    }
    ?>
    <script>
        var timeTracking = new TimeTracking();
        var minutes = <?=$arResult['TIME_INTERVAL']?>;
        var title = '<?=$content ?>';
        var url = '<?=$url?>';
        var close_mess = '<?=GetMessage('CLOSE') ?>';
        var content = '<img style="width: 290px;height: 290px;" src="' + url + '">';
        timeTracking.show_notify(title, content, close_mess);

    </script>
<? }elseif($_SESSION['need_tracking'] == 'Y' && $arResult['IS_TRACKING'] == 'N'){
    $_SESSION['need_tracking'] = 'N';
    ?>
    <script>
        var timeTracking = new TimeTracking();
        var minutes = <?=$arResult['TIME_INTERVAL']?>;
        var close_mess = '<?=GetMessage('CLOSE') ?>';
        var content = '<img style="width: 290px;height: 290px;" src="/local/components/ebola/ebola.timetracking/templates/default/images/fill_in.jpg">';
        timeTracking.show_notify('', content, close_mess);

    </script>

<?}?>
