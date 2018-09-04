<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Context;

if (!$USER->IsAdmin()) {
    return;
}

$module_id = 'ebola.tametracking';
$module_prefix = 'EBOLA_TIMETRACKING';

Loc::loadMessages($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/options.php");
Loc::loadMessages(__FILE__);


$bLoadModule = false;
try {
    $bLoadModule = Loader::includeModule($module_id);
} catch (Exception $ex) {
}

$aTabs = array( array
    (
        "DIV"     => $module_prefix."_main_settings",
        "TAB"     => 'Основные настройки',
        "ICON"    => $module_prefix."_settings",
        "TITLE"   => 'Основные настройки модуля',
        "OPTIONS" => array(
           array (
                $module_prefix."SHIFT_MINUTES",
                'Время интервала показа уведомления в минутах(по умолчанию 15)',
                "",
               array("text", 15),
            ),
            array (
                $module_prefix."GROUP_ID",
                'ID группы без контроля рабочего времени',
                "",
                array("text", 15),
            ),
        ),
    ),
);

$tabControl = new CAdminTabControl($module_prefix."TabControl", $aTabs, true, true);
$request = Context::getCurrent()->getRequest();

if ($request->isPost() && (
        $request["save"] ||
        $request["apply"]
    ) && check_bitrix_sessid()) {
    if ($request["save"] || $request["apply"]) {
        foreach ($aTabs as $arCurTab) {
            if (!isset($arCurTab["OPTIONS"])) {
                continue;
            }
            foreach ($arCurTab["OPTIONS"] as $arOption) {
                $name = $arOption[0];
                $val = trim($request[$name], " \t\n\r");
                if ($arOption[2][0] == "checkbox" && $val != "Y") {
                    $val = "N";
                }
                Option::set($module_id, $name, "".$val);
            }
        }
    }

    ob_start();
    $save = $save.$apply;
    ob_end_clean();

    if ($request["back_url"]) {
        if ($request["apply"]) {
            LocalRedirect(
                $APPLICATION->GetCurPage()."?mid=".urlencode($module_id)."&lang=".urlencode(
                    LANGUAGE_ID
                )."&back_url=".urlencode($request["back_url"])."&".$tabControl->ActiveTabParam()
            );
        } else {
            LocalRedirect($request["back_url"]);
        }
    } else {
        LocalRedirect(
            $APPLICATION->GetCurPage()."?mid=".urlencode($module_id)."&lang=".urlencode(
                LANGUAGE_ID
            )."&".$tabControl->ActiveTabParam()
        );
    }
} ?>


<? //Visual part?>
<form method="POST" action="<?echo $APPLICATION->GetCurPage()?>?lang=<?echo LANGUAGE_ID?>&mid=<?=$module_id?>"
      name="<?=$module_prefix?>_settings" id="<?=$module_prefix?>_settings">
    <? echo bitrix_sessid_post();?>
    <?// start output our settings?>
    <?$tabControl->Begin();?>
    <?foreach ($aTabs as $tab):?>
        <?$tabControl->BeginNextTab();?>
        <?__AdmSettingsDrawList($module_id, $tab["OPTIONS"]);?>
    <?endforeach;?>
    <?$tabControl->Buttons(array("back_url" => $request["back_url"]));?>
    <?=bitrix_sessid_post();?>
</form>
<?$tabControl->End();?>


