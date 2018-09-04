<?php
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;

try {
    Loader::registerAutoloadClasses(
        "ebola.timetracking",
        array(
            "EbolaReminder\EventHandler"     => "lib/eventhandler.php",
            "EbolaReminder\TaskTimeTracking" => "lib/tasktimetracking.php",
            "EbolaReminder\Config"           => "lib/config.php",
        )
    );
} catch (LoaderException $ex) {
    ShowError($ex->getMessage());
}
?>