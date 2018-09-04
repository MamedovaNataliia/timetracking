<?php

namespace EbolaReminder;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Config\Option;

class TaskTimeTracking
{
    const ABSENCE_IBLOCK_ID = 3;
    /**
     * @var bool
     */
    protected $bControl;

    /**
     * @var bool
     */
    private $bIsFriday = false;

    /**
     * @var bool
     */
    private $bIsHoliday = false;
    /**
     * @var string
     */
    private $curWeekDay = null;

    /**
     * @param int $user_id
     * @return bool
     */
    public function getStatusControl($user_id)
    {
        if ($user_id) {
            $arGroups = \CUser::GetUserGroup($user_id);
            foreach ($arGroups as $group_id) {
                if ($group_id == Config::UNTRACKING_GROUP_ID) {
                    return false;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * @param int $user_id
     * @return bool
     */
    public function isUserAbsent($user_id)
    {
        if ($user_id) {
            $bFlag = false;

            $dt = $this->getTimeStampStart();

            $arSelect = [
                'DATE_ACTIVE_FROM',
                'DATE_ACTIVE_TO',
                'PROPERTY_ABSENCE_TYPE'
            ];
            $arAbsence = $this->getArrUserAbsent([$user_id], $dt, time(), $arSelect);

            if (is_array($arAbsence[$user_id]) && $arAbsence[$user_id]['PROPERTY_ABSENCE_TYPE_ENUM_ID'] != 376) {
                $bFlag = true;
            }
            return $bFlag;
        }
        return false;
    }

    /**
     * @param $user_id
     * @return array|bool
     */
    public function getUntrackingTasks($user_id)
    {
        if ($user_id) {
            $arResult = array();

            if (!Loader::includeModule('tasks')) {
                throw new LoaderException('Module tasks not installed');
            }

            $arrFilter = array(
                'SUBORDINATE_TASKS' => 'Y',
                '>=CLOSED_DATE'     => trim(\CDatabase::CharToDateFunction(ConvertTimeStamp(time() - 86400),
                        'FULL') . "\'")

            );

            $rsTasks = \CTasks::GetList(
                array(),
                $arrFilter
            );

            while ($arrTasks = $rsTasks->Fetch()) {

                $rsTasksElapsed = \CTaskElapsedTime::GetList(
                    array(),
                    array(
                        'USER_ID' => $user_id,
                        'TASK_ID' => $arrTasks['ID'],
                    )
                );
                if (!$rsTasksElapsed->Fetch()) {
                    $arResult[] = array(
                        'TASK_ID' => $arrTasks['ID'],
                        'TITLE'   => $arrTasks['TITLE'],
                        'LINK'    => "/company/personal/user/" . $user_id . "/tasks/task/view/" . $arrTasks['ID'] . "/",
                    );
                }
            }

            return $arResult;
        }
        return false;
    }

    /**
     * @param $user_id
     * @return int
     */
    public function getTimeLog($user_id)
    {
        global $DB;
        if ($user_id) {
            $timelog = null;

            $dataCurrentEnd = DateTime::createFromTimestamp(time(), 'SHORT');
            $dataCurrentEnd = $dataCurrentEnd->format("Y-m-d");

            $dataCurrent = DateTime::createFromTimestamp($this->getTimeStampStart(), 'SHORT');
            $dataCurrent = $dataCurrent->format("Y-m-d");

            $res = $DB->query('SELECT b_tasks_elapsed_time.MINUTES
							FROM b_tasks_elapsed_time 
							INNER JOIN b_tasks on b_tasks.ID = b_tasks_elapsed_time.TASK_ID 
							WHERE DATE_FORMAT(b_tasks_elapsed_time.CREATED_DATE,\'%Y-%m-%d\') = "' . $dataCurrent . '"
                           AND b_tasks_elapsed_time.USER_ID = ' . $user_id);
            while ($row = $res->fetch()) {
                $timelog += intval($row['MINUTES']);
            }

            if ($timelog >= 420) {
                return false;
            }

            return intval($timelog);
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isFriday()
    {
        return $this->bIsFriday;
    }

    /**
     * @return int $timeStampCurrent
     */
    private function getTimeStampStart()
    {
        $timeStampCurrent = time() - 86400;
        $arDate = getdate($timeStampCurrent);
        if ($arDate['weekday'] == 'Saturday') {
            $timeStampCurrent = $timeStampCurrent - 86400;
            $this->bIsFriday = true;
        } elseif ($arDate['weekday'] == 'Sunday') {
            $timeStampCurrent = $timeStampCurrent - (86400 * 2);
            $this->bIsFriday = true;
        }
        if ($this->isDateHolidays($timeStampCurrent, $arDate['year'])) {
            $this->bIsHoliday = true;

            $timeStampCurrent = $timeStampCurrent - 86400;
            $arDate = getdate($timeStampCurrent);
            $this->curWeekDay = $arDate['weekday'];

            if($arDate['weekday'] == 'Friday'){
                $this->bIsFriday = true;
            }else{
                $this->bIsFriday = false;
            }
        }
        return $timeStampCurrent;
    }

    /**
     * @return bool
     */
    public function getIsHoliday()
    {
        return $this->bIsHoliday;
    }

    /**
     * @return string
     */
    public function getCurWeekDay()
    {
        return $this->curWeekDay;
    }
    /**
     * @return array $arrHolidays
     */
    private function getCalendarHolidays()
    {
        if (!Loader::includeModule('calendar')) {
            throw new LoaderException('Module calendar not installed');
        }
        $arSettings = \CCalendar::GetSettings();
        $arrHolidays = explode(',', $arSettings['year_holidays']);
        return $arrHolidays;
    }

    /**
     * @param int $time
     * @param string $year
     * @return bool
     */
    private function isDateHolidays($time, $year)
    {
        $date = date('d.m.Y', $time);
        $arrHolidays = $this->getCalendarHolidays();
        foreach ($arrHolidays as &$holiday) {
            $holiday .= '.' . $year;
            if ($date == $holiday) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param int $user_id
     * @param int $date_start
     * @param int $date_to
     * @param array $arSelect
     * @return array|bool
     */
    private function getArrUserAbsent($user_id, $date_start, $date_to, $arSelect)
    {
        global $DB;
        if ($user_id) {
            $arFilter = array(
                'IBLOCK_ID' => self::ABSENCE_IBLOCK_ID,
                'ACTIVE'    => 'Y',
            );

            $format = $DB->DateFormatToPHP(\CLang::GetDateFormat("SHORT"));

            if ($date_to) {
                $arFilter['>=DATE_ACTIVE_TO'] = date($format, $date_start);
            }
            if ($date_start) {
                $arFilter['<=DATE_ACTIVE_FROM'] = date($format, $date_start);
            }

            if (is_array($user_id)) {
                $arFilter['=PROPERTY_USER'] = $user_id;
            }

            $dbRes = \CIBlockElement::GetList(
                array('DATE_ACTIVE_FROM' => 'ASC', 'DATE_ACTIVE_TO' => 'ASC'),
                $arFilter,
                false,
                false,
                $arSelect
            );

            $arResult = [];
            while ($arRes = $dbRes->Fetch()) {
                $arResult[$user_id[0]] = $arRes;
            }
            return $arResult;
        }
        return false;
    }
}