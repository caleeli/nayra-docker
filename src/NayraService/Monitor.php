<?php

namespace ProcessMaker\NayraService;

class Monitor
{
    public static function metrics(array $metrics)
    {
        global $log_filename;
        $time_window = 10;
        $to = time();
        $from = round(($to - 19 * $time_window) / $time_window) * $time_window;
        // LOAD LAST LOG LINES
        $labels = [];
        $activated = [];
        $completed = [];
        $execution_time = [];
        $requests_activated = [];
        $requests_completed = [];
        $requests_execution_time = [];
        $task_xtime = [];
        for ($t=$from; $t<=$to; $t+=$time_window) {
            $tl = date('Y-m-d H:i:s', $t);
            $labels[$tl] = date('H:i:s', $t);
            $activated[$tl] = 0;
            $completed[$tl] = 0;
            $execution_time[$tl] = [];
            $requests_activated[$tl] = 0;
            $requests_completed[$tl] = 0;
            $requests_execution_time[$tl] = [];
        }
        $log_file = fopen($log_filename, 'r');
        if ($log_file) {
            while (($line = fgets($log_file)) !== false) {
                $columns = explode(' ', trim($line));
                if (count($columns) === 8) {
                    list($date, $time, $timestamp, $xtime, $tokenId, $requestId, $elementId, $event) = $columns;
                    $t = floor($timestamp / $time_window) * $time_window;
                    $tl = date('Y-m-d H:i:s', $t);
                    if ($timestamp >= $from) {
                        if ($event === 'ACTIVITY_ACTIVATED') {
                            $activated[$tl] = ($activated[$tl] ?? 0) + 1;
                        } elseif ($event === 'ACTIVITY_COMPLETED') {
                            $completed[$tl] = ($completed[$tl] ?? 0) + 1;
                            $execution_time[$tl][] = $xtime;
                            $task_xtime[$elementId][] = $xtime;
                        }
                    }
                } elseif (count($columns) === 7) {
                    list($date, $time, $timestamp, $xtime, $requestId, $elementId, $event) = $columns;
                    $t = floor($timestamp / $time_window) * $time_window;
                    $tl = date('Y-m-d H:i:s', $t);
                    if ($timestamp >= $from) {
                        if ($event === 'PROCESS_INSTANCE_CREATED') {
                            $requests_activated[$tl] = ($requests_activated[$tl] ?? 0) + 1;
                        } elseif ($event === 'PROCESS_INSTANCE_COMPLETED') {
                            $requests_completed[$tl] = ($requests_completed[$tl] ?? 0) + 1;
                            $requests_execution_time[$tl][] = $xtime;
                        }
                    }
                }
            }
            fclose($log_file);
        }
        $labels = array_values($labels);
        $activated = array_values($activated);
        $completed = array_values($completed);
        $execution_time = array_values($execution_time);
        // AVG of execution time
        foreach ($execution_time as $i => $times) {
            $c = count($times);
            $execution_time[$i] = $c ===0 ? 0 : array_sum($times) / $c;
        }
        $requests_activated = array_values($requests_activated);
        $requests_completed = array_values($requests_completed);
        $requests_execution_time = array_values($requests_execution_time);
        // AVG of execution time
        foreach ($requests_execution_time as $i => $times) {
            $c = count($times);
            $requests_execution_time[$i] = $c ===0 ? 0 : array_sum($times) / $c;
        }
        // Task execution times
        $task_ids = array_keys($task_xtime);
        $task_xtimes = array_values($task_xtime);
        // avg of task execution times
        foreach ($task_xtimes as $i => $times) {
            $c = count($times);
            $task_xtimes[$i] = $c ===0 ? 0 : array_sum($times) / $c;
        }
        return compact(...$metrics);
    }
}
