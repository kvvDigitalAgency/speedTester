<?php

class speedTester 
{
    private $files = [];
    private $params = [];
    private $functions = [];
    private $iterations = 10;
    private $colLengths = [];
    private $columns = [
        'function' => 'Функция',
        'iterPerSec' => 'Итераций в сек',
        'time' => 'Время, сек',
        'coefficient' => 'Коэф',
        'speedup' => 'Прирост скорости, +%',
    ];
    private static $obj;

    public static function create() 
    {
        // это задача на внимательность, но мне понравилось
        return self::$obj?:self::$obj = new self();
    }

    public function addFunction(string $functionName) 
    {
        $this->functions = array_merge($this->functions, (array)$functionName);
    }
    
    public function addFile($fileName) 
    {
        foreach ($fileName as $name) if(!file_exists($name)) return false;
        return !!$this->files = array_merge($this->files, (array)$fileName);
    }
    
    public function setParams(array $params) 
    {
        $this->params = $params;
    }
    
    public function setCountIterations($count) 
    {
        $this->iterations = $count;
    }

    public function testFunctions($consoleMode = false) 
    {
        foreach($this->files as $file) include_once $file;
        
        foreach($this->functions as $function) if(!function_exists($function)) {
            if($consoleMode) echo 'Нет функции', $function, PHP_EOL;
            return false;
        }
        
        $params = $this->params;
        $result = [];
        $process = -($percentPerIter = 100 / ($this->iterations * count($this->functions)));
        
        foreach($this->functions as $i => $function) {
            $result[$i] = ['function'=>$function,'iterPerSec'=>0];
            for ($j=0; $j < $this->iterations; $j++) {
                if($consoleMode) echo ' Процесс: ', $process += $percentPerIter, "%\r";
                for ($t = time(); $t == time(););
                for (
                    $result[$i]['iterPerSec'], $t = time(); 
                    time() == $t; 
                    $result[$i]['iterPerSec']++
                ) call_user_func_array($function, $params);
            }
            $result[$i] += [
                'time' => 1 / ($result[$i]['iterPerSec'] = round($result[$i]['iterPerSec'] / $this->iterations, 6)),
                'answer' => call_user_func_array($function, $params)
            ];
        }
        
        usort($result, function($a, $b) {return $b['iterPerSec'] - $a['iterPerSec'];});
        
        $slower = end($result);
        
        foreach($result as &$item) {
            $item['coefficient'] = round($slower['iterPerSec'] / $item['iterPerSec'], 2);
            $item['speedup'] = round($item['iterPerSec'] / $slower['iterPerSec'] * 100 - 100, 2);
        }
        
        if(!$consoleMode) return $result;
        else {
            array_unshift($result, $this->columns);
            $i = 0;
            foreach($result[0] as $k => $v) {
                foreach(array_merge([$v], array_column($result, $k)) as $V) 
                    if(mb_strlen($V) > (isset($this->colLengths[$i])?$this->colLengths[$i]:0)) $this->colLengths[$i] = mb_strlen($V);
                $i++;
            }
            
            echo $line = str_repeat('-', array_sum($this->colLengths) + 1 + 3 * count($this->colLengths)) . PHP_EOL;
           
            $line = PHP_EOL . $line;
            $flagSuccess = true;
            $columns = $this->columns;
            
            foreach($result as $row) {
                $this->drawString(array_values(array_filter(
                    array_replace($this->columns, $row), 
                    function($k) use($columns) {
                        return isset($columns[$k]);
                    },
                    ARRAY_FILTER_USE_KEY
                )));
                echo $line;
                if(isset($row['answer']) && $row['answer'] != $slower['answer']) $flagSuccess = false;
            }
            
            if($flagSuccess) echo 'Ответ одинаковый:' , PHP_EOL , var_export($slower['answer']), PHP_EOL;
            else {
                echo 'Ответ разный:', PHP_EOL;
                foreach ($result as $row) if(isset($row['answer'])) echo PHP_EOL, 'Функция ', $row['function'], ':', PHP_EOL, var_export($row['answer']), PHP_EOL;
            }
            
            return true;
        }
    }
    
    private function drawString($cols) {
        $res = [];
        foreach ($cols as $k => $col) $res[] = strlen($col) + $this->colLengths[$k] - mb_strlen($col);
        printf('| %-' . implode('s | %-', $res) . 's |', ...$cols);
    }

    private function __construct() {}
    private function __sleep() {}
    private function __wakeup() {}
}
