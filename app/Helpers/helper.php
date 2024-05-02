<?php
if (!function_exists('getIntervalTime')) {
    function getIntervalTime($startTime = null, $endTime = null, bool $isStrict = false)
    {
        if (is_null($startTime) || is_null($endTime)) return "00:00:00";

        $startTime = DateTime::createFromFormat('H:i:s', $startTime);
        $endTime = DateTime::createFromFormat('H:i:s', $endTime);
        $interval = $startTime->diff($endTime);

        // if ($isStrict) {
        //     if (($endTime <= $endTime) && ($interval->i == 0 && $interval->h == 0 && $interval->d == 0 && $interval->m == 0 && $interval->y == 0)) {
        //         return "00:00:00";
        //     } else {
        //         return $interval->format('%H:%I:%S');
        //     }
        // }

        if ($isStrict) {
            if ($endTime <= $startTime) {
                return "00:00:00";
            }

            if (($endTime > $startTime) && ($interval->i < 1 && $interval->h < 1 && $interval->d < 1 && $interval->m < 1 && $interval->y < 1)) {
                // dump('tai');
                // dump($interval);
                return "00:00:00";
            }
        }

        return $interval->format('%H:%I:%S');
    }
}
