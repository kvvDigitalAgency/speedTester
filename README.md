SpeedTester
=============================

Информация о проекте
---
SpeedTester - инструмент для тестирования и сравнения скорости работы функций, методов классов и файлов. Планируется реализовать замер потребляемой памяти исполняемого ресурса.
Также инструмент умеет красиво и понятно выводить информацию в консоль при надобности в виде таблицы с отсортированными строками.

Инструкция для использования:
---------

1. Создайте объект на основе класса speedTester:
   ```
   // Пример создания объекта:
   
   $speedTester = speedTester::create();
   ```

---

2. Добавьте тестируемые ресурсы:
   * Если нужно протестировать функции:
     ```
     $speedTester->addFunctions(['func1', 'func2',...]);
     ```
   * Если нужно протестировать методы объектов/классов:
     ```
     $speedTester->addFunctions([[$testObj, 'method'], ['className', 'staticMethod',...]);
     ```
   * Для предыдущих 2 пунктов: если функции/классы находятся в других файлах, добавьте их:
     ```
     $speedTester->addFiles(['file1.php', 'pathToFile/file2.php',...]);
     ```
     Если требуется вызывать функции с параметрами, можно добавить их:
     ```
     $speedTester->setParams([param1, param2,...]);
     ```
     Также можно тестировать функции и методы классов совместно, просто добавляя их вместе
   * Если нужно протестировать файлы, добавляем их по примеру выше. Пока что функционала совместного тестирования функций и файлов нет из-за больших помех при тестах последнего. В будущем проблема будет решена. Если вам нужно указать путь к интерпретатору, используем следующее:
     ```
     $speedTester->setInterpreter('/usr/local/opt/php@7.1/bin/php');
     ```

---

3. Опционально: установка количества проверок на 1 ресурс:

   По умолчанию стоит 10 итераций, но это количество можно изменить:
   ```
   $speedTester->setCountIterations(1);
   ```
   Чем больше значение - тем более точное время выполнения будет рассчитано, но тестирование будет занимать больше времени.
   Малое значение позволит быстро оценить и принять срочное решение.
   2 функции по 10 итераций занимают примерно 40 сек.

---

4. Запустить тестирование:
   * Для запуска тестирования функций и методов классов:
   ```
   $speedTester->testFunctions(bool $consoleMode = false);
   ```
   * Для запуска тестирования функций и методов классов:
   ```
   $speedTester->testFiles(bool $consoleMode = false);
   ```
   * В обоих случаях, если требуется вывод результатов тестирования в консоль, то в метод передается параметр $consoleMode равный true, иначе можно ничего не передавать. Функция возвращает массив данных вида:
   ```
    array (
        0 => array (
            'function' => 'className->nsf',
            'iterPerSec' => 1838157.333333,
            'time' => 5.440230723812804E-7,
            'answer' => true,
            'coefficient' => 0.0,
            'speedup' => 17232624.46,
        ),
        ...
        n => array (
            'function' => 'func',
            'iterPerSec' => 10.666667,
            'time' => 0.09374999707031259,
            'answer' => true,
            'coefficient' => 1.0,
            'speedup' => 0.0,
        ),
    )
   ```
   * консольный вывод примерно такой:
   ```
   Готово!                  
   ---------------------------------------------------------------------------------------
   | Файл      | Итераций в сек | Время, сек        | Коэффициент | Прирост скорости, +% |
   ---------------------------------------------------------------------------------------
   | file1.php | 26.333333      | 0.037974684024996 | 0.99        | 1.28                 |
   ---------------------------------------------------------------------------------------
   | file2.php | 26             | 0.038461538461538 | 1           | 0                    |
   ---------------------------------------------------------------------------------------
   ```