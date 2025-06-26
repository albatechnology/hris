<?php

use Illuminate\Support\Collection;

if (!function_exists('getIntervalTime')) {
    function getIntervalTime($startTime = null, $endTime = null, bool $isStrict = false)
    {
        if (is_null($startTime) || is_null($endTime)) return "00:00:00";

        $startTime = DateTime::createFromFormat('H:i:s', $startTime);
        $endTime = DateTime::createFromFormat('H:i:s', $endTime);
        $interval = $startTime->diff($endTime);

        if ($isStrict) {
            if ($endTime <= $startTime) {
                return "00:00:00";
            }

            if (($endTime > $startTime) && ($interval->i < 1 && $interval->h < 1 && $interval->d < 1 && $interval->m < 1 && $interval->y < 1)) {
                return "00:00:00";
            }
        }

        return $interval->format('%H:%I:%S');
    }
}

if (!function_exists('sumTimes')) {
    function sumTimes(array|Collection $times = [], bool $formatText = false)
    {
        $totalSeconds = 0;
        foreach ($times as $time) {
            if (!empty($time) || !is_null($time)) {
                list($hours, $minutes, $seconds) = explode(':', $time);
                $totalSeconds += ($hours * 3600) + ($minutes * 60) + $seconds;
            }
        }

        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        // $seconds = $totalSeconds % 60;

        $result = '';
        if ($formatText) {
            if ((int)$hours > 0) {
                $result .= (int)$hours . 'h ';
            }
            if ((int)$minutes > 0) {
                $result .= (int)$minutes . 'm ';
            }
            // if ((int)$seconds > 0) {
            //     $result .= (int)$seconds . 's';
            // }
        } else {
            $result = sprintf("%02d:%02d:00", $hours, $minutes);
            // $result = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds);
        }

        return $result;
    }
}

if (!function_exists('limitToNineHours')) {
    function limitToNineHours(string $time): string
    {
        [$hour, $minute] = explode(':', $time);

        if ((int)$hour >= 9) {
            return '09:00';
        }

        return sprintf('%02d:%02d', $hour, $minute);
    }
}
