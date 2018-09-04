<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Localization\Loc as Loc;
use Bitrix\Main\Application;
use EbolaReminder\TaskTimeTracking;
use Bitrix\Tasks\Integration\Extranet\User;

class EbolaReminder extends CBitrixComponent
{

    /**
     * @var int $time_interval
     */
    protected $time_interval = null;
    /**
     * check ajax request
     * @var bool $isAjax
     */
    protected $isAjax = false;

    /**
     * @var bool
     */
    private $bIsFriday = false;

    /**
     * include lang files
     */
    public function onIncludeComponentLang()
    {
        $this->includeComponentLang(basename(__FILE__));
        Loc::loadMessages(__FILE__);
    }

    public function __construct($component = null)
    {
        parent::__construct($component);

    }

    /**
     * prepare input params
     * @param array $params
     * @return array
     */
    public function onPrepareComponentParams($params)
    {
        global $APPLICATION;
        global $USER;

        if (User::isExtranet($USER->GetID())) {
            return;
        }

        $result = $params;

        if ($this->isAjaxRequest()) {
            $this->isAjax = true;
        }

        return $result;
    }

    /**
     *  component main logic
     */
    public function executeComponent()
    {
        global $USER;
        try {
            $this->checkModules();
        } catch (\Exception $ex) {
            echo $ex->getMessage();
            exit;
        }
        if ($this->isAjaxRequest()) {
            $this->sendResponse($_REQUEST);
            require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");
            exit;
        }

        $minutes = Option::get('ebola.tametracking', 'EBOLA_TIMETRACKINGSHIFT_MINUTES');
        $this->arResult['TIME_INTERVAL'] = ($minutes) ? $minutes : 15;

        $this->runWithoutAjax();
        $this->includeComponentTemplate();
    }


    /**
     * set cache Addon
     * @param array $arCacheKey
     */
    protected function setCacheAddon($arCacheKey)
    {
        $this->cacheAddon = $arCacheKey;
    }

    /**
     * cache arResult keys
     */
    protected function putDataToCache()
    {
        if (is_array($this->cacheKeys) && sizeof($this->cacheKeys) > 0) {
            $this->SetResultCacheKeys($this->cacheKeys);
        }
    }

    /**
     * abort cache process
     */
    protected function abortDataCache()
    {
        $this->AbortResultCache();
    }

    /**
     * check needed modules
     * @throws LoaderException
     */
    protected function checkModules()
    {
        if (!Loader::includeModule('ebola.timetracking')) {
            throw new LoaderException('Module ebola.timetracking not installed ');
        }
    }

    /**
     * @return bool
     * @throws Main\SystemException
     */
    protected function isAjaxRequest()
    {
        global $APPLICATION;
        $isAjax = false;
        $server = Application::getInstance()->getContext()->getServer();
        $ajax = $server->get('HTTP_X_REQUESTED_WITH');
        $content_type = $server->get('CONTENT_TYPE');

        if ($ajax && strtolower($ajax) === 'xmlhttprequest'
            && strpos($content_type, 'multipart/form-data') === false) {
            $isAjax = true;
            define("PUBLIC_AJAX_MODE", true);
            $APPLICATION->RestartBuffer();
        }

        return $isAjax;
    }

    public function runWithoutAjax()
    {
        global $USER;
        $timeLog = false;
        $user_id = $USER->GetID();

        try {
            $obTasks = new TaskTimeTracking();

            $isApsent = $obTasks->isUserAbsent($user_id);
            $isStatus = $obTasks->getStatusControl($user_id);

            if ($isStatus && !$isApsent) {
                $timeLog = $obTasks->getTimeLog($user_id);
                $this->arResult['IS_TRACKING'] = 'Y';
            } else {
                $this->arResult['IS_TRACKING'] = 'N';
            }
            if ($timeLog === false) {
                $this->arResult['IS_TRACKING'] = 'N';
            }
        } catch (\Exception $ex) {
            echo $ex->getMessage();
        }

        $this->arResult['HOURS'] = $timeLog;
        $this->arResult['IS_FRIDAY'] = $obTasks->isFriday();
        $this->arResult['IS_HOLIDAY'] = $obTasks->getIsHoliday();
        $this->arResult['CUR_WEEK_DAY'] = $obTasks->getCurWeekDay();
    }

    /**
     * @param $_REQUEST $REQUEST
     */
    protected function sendResponse($REQUEST)
    {
        global $USER;

        if ($REQUEST['tracking_data'] == 'Y') {

            $timeLog = null;
            $user_id = $USER->GetID();
            try {
                $obTasks = new TaskTimeTracking();

                $isApsent = $obTasks->isUserAbsent($user_id);
                $isStatus = $obTasks->getStatusControl($user_id);

                if ($isStatus && !$isApsent) {
                    $timeLog = $obTasks->getTimeLog($user_id);
                }
            } catch (\Exception $ex) {
                echo $ex->getMessage();
            }
            echo json_encode(['hours' => $timeLog], JSON_ERROR_UTF8);
        }
    }

    /**
     * some actions before cache
     */
    protected function executeProlog()
    {
    }

    /**
     * some actions after component work
     */
    protected function executeEpilog()
    {
    }


}
