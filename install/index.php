<?php

use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\ModuleManager;
use \Bitrix\Main\EventManager;
use \Bitrix\Main\Loader;

Loc::loadMessages(__FILE__);

if (class_exists("ebola_timetracking")) {
    return;
}

class ebola_timetracking extends CModule
{
    public    $MODULE_ID = "ebola.timetracking";
    protected $MODULE_PATH;

    /**
     * ebola_timetracking constructor
     */
    public function __construct()
    {
        $this->MODULE_NAME = Loc::getMessage('EBOLA_TIME_TRACKING_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('EBOLA_TIME_TRACKING_MODULE_DESCRIPTION');

        $this->MODULE_VERSION = "1.2.2";
        $this->MODULE_VERSION_DATE = "2018-07-13";

        $this->PARTNER_NAME = "EBOLA COMMUNICATIONS";
        $this->PARTNER_URI = "http://ebola.agency/";
        $this->MODULE_PATH = $_SERVER["DOCUMENT_ROOT"]."/local/modules/ebola.timetracking/install/components/";
    }

    /**
     * @return bool
     */
    public function DoInstall()
    {
        if (!$this->IsInstalled()) {
            ModuleManager::registerModule($this->MODULE_ID);
            $this->InstallFiles();
        }
        return true;
    }

    /**
     * @return bool
     */
    public function DoUninstall()
    {
        if ($this->IsInstalled()) {
            $this->UnInstallFiles();
            ModuleManager::unRegisterModule($this->MODULE_ID);
        }
    }
    /**
     * @return bool
     */
    public function InstallFiles()
    {
        \CopyDirFiles($_SERVER["DOCUMENT_ROOT"] . "/local/modules/ebola.timetracking/install/components",
            $_SERVER["DOCUMENT_ROOT"] . "/local/components", true, true);
        return true;
    }

    /**
     * @return bool
     */
    function UnInstallFiles()
    {
        \DeleteDirFilesEx("local/components/ebola/");
        return true;
    }

}