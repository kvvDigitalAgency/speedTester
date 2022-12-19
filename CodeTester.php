<?php

/**
 *
 */
final class CodeTester
{
    /**
     * Колонка с названием функции/файла вставляется в самое начало в другом месте
     *
     * @access private
     * @var array Названия колонок
     */
    private static $columns = [
        'iterPerSec' => 'Итераций в сек',
        'time' => 'Время, сек',
        'coefficient' => 'Коэффициент',
        'speedup' => 'Прирост скорости, +%',
    ];

    /**
     * @access private
     * @var string Интерпретатор
     */
    private static $interpreter = 'php';

    /**
     * @access private
     * @var array Список добавленных файлов
     */
    private static $fileList = [];

    /**
     * @access private
     * @var array Список установленных параметров для передачи в функции
     */
    private static $paramList = [];

    /**
     * @access private
     * @var array Список добавленных функций
     */
    private static $functionList = [];

    /**
     * @access private
     * @var bool Режим вывода в консоль
     */
    private static $consoleMode = false;

    /**
     * @access private
     * @var int Количество итераций вызова каждой функции
     */
    private static $iterations = 10;

    /**
     * Добавляются функции, которые нужно протестировать
     *
     * @access public
     * @param array $functionList Список Функций
     * @return bool
     */
    public static function addFunctionList(array $functionList): bool
    {
        return !!self::$functionList = array_merge(self::$functionList, $functionList);
    }

    /**
     * Добавляются файлы, которые нужно подключить
     *
     * @access public
     * @param array $fileList Список файлов
     * @return bool
     * @throws CodeTesterException
     */
    public static function addFileList(array $fileList): bool
    {
        foreach ($fileList as $name) if(!file_exists($name)) throw new CodeTesterException('Нет файла', $name, PHP_EOL);
        return !!self::$fileList = array_merge(self::$fileList, $fileList);
    }

    /**
     * Устанавливает режим вывода в консоль
     *
     * @access public
     * @param bool $consoleMode
     * @return bool
     */
    public static function setConsoleMode(bool $consoleMode): bool
    {
        return !!self::$consoleMode = $consoleMode;
    }

    /**
     * Устанавливаются параметры, с которыми функция будет вызываться
     *
     * @access public
     * @param array $paramList Список параметров
     * @return bool
     */
    public static function setParamList(array $paramList): bool
    {
        return !!self::$paramList = $paramList;
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
    public static function setCountIterations(int $count): bool
    {
        return $count > 0 && !!self::$iterations = $count;
    }

    /**
     * Устанавливается интерпретатор для запуска тестирования файлов
     *
     * @access  public
     *
     * @param string $interpreter
     *
     * @return bool
     * @example "2 функции по 10 итераций занимают ~ 40 сек."
     */
    public static function setInterpreter(string $interpreter): bool
    {
        return !!self::$interpreter = $interpreter;
    }

    /**
     * Подготавливаются данные для замеров тестов файлов
     *
     * Подготавливаются массив файлов и колонки
     *
     * @access public
     * @return array
     * @throws CodeTesterException
     */
    public static function testFiles(): array
    {
        return self::test(self::$fileList, ['file'=>'Файл'] + self::$columns, false);
    }

    /**
     * Подготавливаются данные для замеров тестов функций
     *
     * Подготавливаются массив функций и колонки
     *
     * @access public
     * @return array
     * @throws CodeTesterException
     */
    public static function testFunctions(): array
    {
        return self::test(self::$functionList, ['function'=>'Функция'] + self::$columns);
    }

    /**
     * Расчеты производительности функций или файлов
     *
     * Производится замер времени выполнения каждой функции/файла, рассчитывается коэффициент,
     * прирост скорости, результат сортируется. Если включен консольный режим,
     * то производятся расчеты для вывода информации и ее вывод.
     *
     * @access public
     * @param array $list Список файлов или функций
     * @param array $columns Колонки таблицы
     * @param bool $functions Тестируются функции или нет
     * @return array
     * @throws CodeTesterException
     */
    private static function test(array $list, array $columns, bool $functions = true): array
    {
        if($functions) {
            $params = self::$paramList;
            foreach (self::$fileList as $file) include_once $file;
        }

        $result = [];
        $process = -($percentPerIter = 100 / (self::$iterations * count($list)));

        foreach($list as $i => $item) {
            if($functions) {
                $result[$i] = ['function'=>is_array($item)?(is_string($item[0])?$item[0]:get_class($item[0])) . '->' . $item[1]:$item,'iterPerSec'=>0];
                if((is_string($item) && !function_exists($item)) || (is_array($item) && !method_exists(...$item))) throw new CodeTesterException('Нет функции' . $result[$i]['function'] . PHP_EOL);
            } else {
                $result[$i] = ['file' => $item, 'iterPerSec'=>0];
                $cmd = self::$interpreter . ' ' . $item;
            }
            for ($j=0; $j < self::$iterations; $j++) {
                if(self::$consoleMode) echo "\r", isset($str)?str_repeat(' ', mb_strlen($str)):'', "\r", $str = 'Процесс: ' . ($process += $percentPerIter) . "%";
                for ($t = time(); $t == time(););
                $t = time();
                if($functions) for (; time() == $t; $result[$i]['iterPerSec']++) call_user_func_array($item, $params);
                else for (; time() == $t; $result[$i]['iterPerSec']++) shell_exec($cmd);
            }
            $result[$i] += [
                'time' => 1 / ($result[$i]['iterPerSec'] = round($result[$i]['iterPerSec'] / self::$iterations, 6)),
                'answer' => $functions?call_user_func_array($item, $params):shell_exec($cmd)
            ];
        }

        usort($result, function($a, $b) {return $b['iterPerSec'] - $a['iterPerSec'];});

        $slower = end($result);

        foreach($result as &$item) {
            $item['coefficient'] = round($slower['iterPerSec'] / $item['iterPerSec'], 2);
            $item['speedup'] = round($item['iterPerSec'] / $slower['iterPerSec'] * 100 - 100, 2);
        }

        array_unshift($result, $columns);

        if(self::$consoleMode) {
            $i = 0;
            $colLengths = [];
            foreach($result[0] as $k => $v) {
                foreach(array_merge([$v], array_column($result, $k)) as $V)
                    if(mb_strlen($V) > ($colLengths[$i] ?? 0)) $colLengths[$i] = mb_strlen($V);
                $i++;
            }

            echo "\r", str_repeat(' ', mb_strlen($str??1)), "\rГотово!", $line = PHP_EOL . str_repeat('-', array_sum($colLengths) + 1 + 3 * count($colLengths)) . PHP_EOL;

            $flagSuccess = true;

            foreach($result as $row) {
                $res = [];
                foreach ($cols = array_values(array_filter(
                    array_replace($columns, $row),
                    function($k) use($columns) {return isset($columns[$k]);},
                    ARRAY_FILTER_USE_KEY
                )) as $k => $col) $res[] = strlen($col) + $colLengths[$k] - mb_strlen($col);
                printf('| %-' . implode('s | %-', $res) . 's |' . $line, ...$cols);
                if(isset($row['answer']) && $row['answer'] !== $slower['answer']) $flagSuccess = false;
            }

            if($flagSuccess) echo 'Ответ одинаковый:' , PHP_EOL , var_export($slower['answer'], true), PHP_EOL;
            else {
                echo 'Ответ разный:', PHP_EOL;
                foreach ($result as $row) if(isset($row['answer']))
                    echo PHP_EOL, $functions?('Функция ' . $row['function']): ('Файл ' . $row['file']), ':', PHP_EOL, var_export($row['answer'], true), PHP_EOL;
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