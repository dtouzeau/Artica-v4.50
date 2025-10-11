<?php

class cpu_percent{
    private $timerange = 1;
    
    function __construct(){}

    private function percentage($denominator, $numerator, $round){
        $percentage = 100 * $denominator / $numerator;
        $percentage = round($percentage, $round);
        return $percentage;
    }
    public function percent_cpu(){
        $results=array();
        $timerange=$this->timerange;
        $procstat_t1 = $this->getprocstat();
        sleep($timerange);
        $procstat_t2 = $this->getprocstat();
        $nproc = $this->getnproc();
        for ($cpuid = 0; $cpuid < $nproc; ++$cpuid) {
            $diffstat[$cpuid] = $this->subtractarray($procstat_t1[$cpuid], $procstat_t2[$cpuid]);
            $diffstat[$cpuid]['total'] = array_sum($procstat_t2[$cpuid]) - array_sum($procstat_t1[$cpuid]);
            $pcpu[$cpuid] = $this->percentage(($diffstat[$cpuid]['total'] - $diffstat[$cpuid]['idle']), $diffstat[$cpuid]['total'], 2);
            $results[]=$pcpu[$cpuid];
        }

       return (array_sum($results) / count($results));


    }

    private function getnproc(){
        //Get the number of processing units available
        exec('nproc', $nproc);
        $nproc = $nproc[0];
        return $nproc;
    }
    private function getprocstat(){
        $nproc = $this->getnproc();
        exec('cat /proc/stat|grep "^cpu"|tail -n '.$nproc, $procstat);
        foreach ($procstat as $cpuid => $v) {
            preg_match('/cpu(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/', $v, $procstat_percpu);
            $tmp[$cpuid] = array(
                'user' => (int) $procstat_percpu[2],
                'nice' => (int) $procstat_percpu[3],
                'system' => (int) $procstat_percpu[4],
                'idle' => (int) $procstat_percpu[5],
                'iowait' => (int) $procstat_percpu[6],
                'irq' => (int) $procstat_percpu[7],
                'softirq' => (int) $procstat_percpu[8],
                'steal' => (int) $procstat_percpu[9],
                'guest' => (int) $procstat_percpu[10],
                'guest_nice' => (int) $procstat_percpu[11],
            );
        }
        return $tmp;
    }
    private function subtractarray($array1, $array2)
    {
        if (count($array1) != count($array2)) {
            die('something happened');
        }
        foreach ($array2 as $key => $value) {
            $difference[$key] = $array2[$key] - $array1[$key];
        }
        return $difference;
    }
    
    
}

