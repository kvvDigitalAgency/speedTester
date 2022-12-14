<?php

/**
 *
 */
class speedTester
{
    /**
     * @access private
     * @var array Список добавленных файлов
     */
    private $files = [];
    /**
     * @access private
     * @var array Список установленных параметров для передачи в функции
     */
    private $params = [];
    /**
     * @access private
     * @var array Список добавленных функций
     */
    private $functions = [];
    /**
     * @access private
     * @var int Количество итераций вызова каждой функции
     */
    private $iterations = 10;
    /**
     * @access private
     * @var speedTester Созданный единожды объект класса
     */
    private static $obj;

    /** Функция, реализующая паттерн singleton
     *
     * Делает возможным вызов кода из нескольких мест и областей видимостей,
     * не теряя при этом все подготовленные в другом месте данные
     *
     * @access public
     * @return speedTester
     */
    public static function create(): speedTester
    {
        // это задача на внимательность, но мне понравилось
        return self::$obj?:self::$obj = new self();
    }

    /**
     * Добавляются функции, которые нужно протестировать
     *
     * @access public
     * @param array $functionNames Список Функций
     * @return bool
     */
    public function addFunctions(array $functionNames): bool
    {
        return !!$this->functions = array_merge($this->functions, $functionNames);
    }

    /**
     * Добавляются файлы, которые нужно подключить
     *
     * @access public
     * @param array $fileNames Список файлов
     * @return bool
     * @throws speedTesterException
     */
    public function addFiles(array $fileNames): bool
    {
        foreach ($fileNames as $name) if(!file_exists($name)) throw new speedTesterException('Нет файла', $name, PHP_EOL);
        return !!$this->files = array_merge($this->files, $fileNames);
    }

    /**
     * Устанавливаются параметры, с которыми функция будет вызываться
     *
     * @access public
     * @param array $params Список параметров
     * @return bool
     */
    public function setParams(array $params): bool
    {
        return !!$this->params = $params;
    }

    /**
     * Устанавливается количество вызовов каждой функции
     *
     * Чем больше значение - тем более точное время выполнения будет рассчитано,
     * но тестирование будет занимать больше времени.
     * Малое значение позволит быстро оценить и принять срочное решение.
     *
     * @access public
     * @example "2 функции по 10 итераций занимают ~ 40 сек."
     * @param int $count Количество итераций
     * @return bool
     */
    public function setCountIterations(int $count): bool
    {
        return !!$this->iterations = $count;
    }

    /**
     * Расчеты производительности функций
     *
     * Производится замер времени выполнения каждой функции, рассчитывается коэффициент,
     * прирост скорости, результат сортируется. Если включен консольный режим,
     * то производятся расчеты для вывода информации и ее вывод.
     *
     * @access public
     * @param bool $consoleMode Консольный режим
     * @return array
     * @throws speedTesterException
     */
    public function testFunctions(bool $consoleMode = false): array
    {
        foreach($this->files as $file) include_once $file;

        $params = $this->params;
        $result = [];
        $process = -($percentPerIter = 100 / ($this->iterations * count($this->functions)));

        foreach($this->functions as $i => $function) {
            if((is_string($function) && !function_exists($function)) || (is_array($function) && !method_exists(...$function))) throw new speedTesterException('Нет функции', implode('->',(array)$function), PHP_EOL);
            $result[$i] = ['function'=>implode('->',(array)$function),'iterPerSec'=>0];
            for ($j=0; $j < $this->iterations; $j++) {
                if($consoleMode) echo ' Процесс: ', $process += $percentPerIter, "%\r";
                for ($t = time(); $t == time(););
                for ($t = time(); time() == $t; $result[$i]['iterPerSec']++) call_user_func_array($function, $params);
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

        array_unshift($result, $columns = [
            'function' => 'Функция',
            'iterPerSec' => 'Итераций в сек',
            'time' => 'Время, сек',
            'coefficient' => 'Коэффициент',
            'speedup' => 'Прирост скорости, +%',
        ]);

        if($consoleMode) {
            $i = 0;
            $colLengths = [];
            foreach($result[0] as $k => $v) {
                foreach(array_merge([$v], array_column($result, $k)) as $V)
                    if(mb_strlen($V) > ($colLengths[$i] ?? 0)) $colLengths[$i] = mb_strlen($V);
                $i++;
            }

            echo $line = str_repeat('-', array_sum($colLengths) + 1 + 3 * count($colLengths)) . PHP_EOL;

            $line = PHP_EOL . $line;
            $flagSuccess = true;

            foreach($result as $row) {
                $res = [];
                foreach ($cols = array_values(array_filter(
                    array_replace($columns, $row),
                    function($k) use($columns) {return isset($columns[$k]);},
                    ARRAY_FILTER_USE_KEY
                )) as $k => $col) $res[] = strlen($col) + $colLengths[$k] - mb_strlen($col);
                printf('| %-' . implode('s | %-', $res) . 's |' . $line, ...$cols);
                if(isset($row['answer']) && $row['answer'] != $slower['answer']) $flagSuccess = false;
            }

            if($flagSuccess) echo 'Ответ одинаковый:' , PHP_EOL , var_export($slower['answer'], true), PHP_EOL;
            else {
                echo 'Ответ разный:', PHP_EOL;
                foreach ($result as $row) if(isset($row['answer']))
                    echo PHP_EOL, 'Функция ', $row['function'], ':', PHP_EOL, var_export($row['answer'], true), PHP_EOL;
            }

        }
        array_shift($result);
        return $result;
    }

    /**
     * Закрытый конструктор
     *
     * @access private
     * @return void
     */
    private function __construct()
    {
    }

    /**
     * Закрытая сериализация
     *
     * @access private
     * @return void
     */
    private function __sleep()
    {
    }

    /**
     * Закрытая десериализация
     *
     * @access private
     * @return void
     */
    private function __wakeup()
    {
    }
}