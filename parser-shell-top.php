<?php

/**
 *  техническая статистика сервера
 * Class serverStatistics
 */
class serverStatistics
{
    // Техническая статистика
    const STAT_USING_MEMORY = 100;
    const STAT_FREE_SPACE = 101;
    const STAT_USING_CPU = 102; // в процентах

    const STAT_APACHE_USING_CPU = 200; // в процентах
    const STAT_APACHE_RCBP = 201; // requests currently being processed
    const STAT_APACHE_COUNT_404 = 202;
    const STAT_APACHE_COUNT_500 = 203;
    const STAT_APACHE_COUNT_TOTAL_ACCESSES = 204;

    const STAT_MYSQL_USING_MEMORY = 300;
    const STAT_MYSQL_CURRENTLY_BEING_QUERY = 301;
    const STAT_MYSQL_COUNT_SLOW_QUERY = 302;
    const STAT_MYSQL_USING_CPU = 303; // в процентах

    const STAT_MEMCACHE_USING_MEMORY = 400; // в процентах
    const STAT_MEMCACHE_GET_HITS = 401;
    const STAT_MEMCACHE_GET_MISSES = 402;
    const STAT_MEMCACHE_EVICTIONS = 403;

    const STAT_SPHINX_USING_MEMORY = 500; // в процентах

    static $statName = array(
        self::STAT_USING_MEMORY => 'Используется памяти в Мб',
        self::STAT_FREE_SPACE => 'Свободного места в Гб',
        self::STAT_USING_CPU => 'Используется CPU в %',
        self::STAT_APACHE_USING_CPU => 'Apache, используется CPU в %',
        self::STAT_APACHE_RCBP => 'Apache, количество текущих процессов',
        self::STAT_APACHE_COUNT_404 => 'Apache, количество ошибок 404',
        self::STAT_APACHE_COUNT_500 => 'Apache, количество ошибок 500-504',
        self::STAT_APACHE_COUNT_TOTAL_ACCESSES => 'Apache, количество обработанных запросов',
        self::STAT_MYSQL_USING_MEMORY => 'MySQL, используется памяти в Мб',
        self::STAT_MYSQL_CURRENTLY_BEING_QUERY => 'MySQL, количество выполняемых запросов в текущий момент',
        self::STAT_MYSQL_COUNT_SLOW_QUERY => 'MySQL, количество медленных запросов в логе',
        self::STAT_MYSQL_USING_CPU => 'MySQL, используется CPU в %',
        self::STAT_MEMCACHE_USING_MEMORY => 'Memcache, используется памяти в Мб',
        self::STAT_MEMCACHE_GET_HITS => 'Memcache, количество найденных значений',
        self::STAT_MEMCACHE_GET_MISSES => 'Memcache, количество ненайденных значений',
        self::STAT_MEMCACHE_EVICTIONS => 'Memcache, удалено раньше истечения срока жизни',
        self::STAT_SPHINX_USING_MEMORY => 'Sphinx, используется памяти в Мб',
    );

    const STAT_PROCESS_WCPU = 'WCPU'; // 9.08 в процентах
    const STAT_PROCESS_SIZE = 'SIZE'; // байт памяти выделено для программы
    const STAT_PROCESS_RES = 'RES'; // байт памяти используются программой
    const STAT_PROCESS_TIME = 'TIME'; // сколько минут программа использовала процессор
    const STAT_PROCESS_COMMAND = 'COMMAND'; // название выполняемого процесса(программы)
    const STAT_PROCESS_COUNT = 'COUNT'; // Количество процессов с одноименным названием

    public static function parserTop($lines = '') {
        if (strlen($lines) == 0) {
            exec('top -d1 -n', $res);
        } else {
            $res = $lines;
        }
        // load averages за 1,5,15 последних минут
        $avg = explode('load averages:', $res[0]);
        $avg = explode('up', $avg[1]);
        $avg = explode(',', $avg[0]);
        $loadAveragePer1Min = trim($avg[0]);
        $loadAveragePer5Min = trim($avg[1]);
        $loadAveragePer15Min = trim($avg[2]);
        // выполняемых(running) и спящих(sleeping) процессов
        $process = explode('processes:', $res[1]);
        $process = explode(',', $process[1]);

        $runningProcess  = (int) $process[0];
        $sleepingProcess = (int) $process[1];
        //"Mem: 460M Active, 1486M Inact, 432M Wired, 908K Cache, 418M Buf, 1552M Free"
        // Информация о памяти
        $memory = explode('Mem: ', $res[3]);
        $memory = explode(',', $memory[1]);
        foreach($memory as $key => $value) {
            $memory[$key] = trim($memory[$key]);
        }

        $usingMemory = array();
        // типа памяти:
        /*
        Free - страницы, не содержащие данных, и которые могут быть использованы при некоторых условиях, когда страницы кэша могут не подойти. Свободные страницы могут повторно использоваться в состояниях прерывания или процессах.
        Buf - (наиболее часто) страницы, которых перемещены из числа неактивных в статус, в котором они содержат данные, но которые могут часто сразу же использоваться повторно (как с их старым содержимым, так и повторно с новым). Это может быть некоторое непосредственное перемещение из состояния active в состояние cache, если известно, что страница чиста (не модифицировалась), но такое перемещение определяется политикой, зависящей от выбора алгоритма разработчиком VM-системы.
        Wired  - страницы, зафиксированные в памяти, обычно для использования ядром, а также иногда для специального использования процессами.
        Inact - по статистике страницы недавно не использовались.
        Active - по статистике страницы недавно использовались.
        */
        foreach($memory as $value) {
            $array = explode(' ', $value);
            $value = $array[0];
            $typeMemory = $array[1];
            $usingMemory[$typeMemory] = functions::convertSizeFormatInCountByte($value);
        }

        //"Swap: 231M Total, 231M Free, 100M Used"
        // Информация о Swap
        $swap = explode('Swap: ', $res[4]);
        $swap = explode(',', $swap[1]);
        foreach($swap as $key => $value) {
            $swap[$key] = trim($swap[$key]);
        }

        $usingSwap = array();
        // типа свопа: Total, Free, Used
        foreach($swap as $value) {
            $array = explode(' ', $value);
            $value = $array[0];
            $typeSwap = $array[1];
            $usingSwap[$typeSwap] = functions::convertSizeFormatInCountByte($value);
        }

        // список процессов
        $listProcess = array();

        $dataList = explode(' ', $res[7]);
        $dataList = array_values(array_filter( $dataList, 'isNotEmpty'));
        for ($processNumber = 1; isset($res[$processNumber + 7]) ;$processNumber++) {
            $processInfo = $res[$processNumber + 7];
            if (strlen($processInfo) > 0) {
                $processInfo = explode(' ', $processInfo);
                $processInfo = array_values(array_filter( $processInfo, 'isNotEmpty'));
                $listProcess[] = $processInfo;
            }
        }

        //  получить аналитическую информацию
        // % WCPU, SIZE, RES, TIME для процессов httpd, mysqld, searchd, memcached, php
        $process = array( // Список контролируемых процессов
            'httpd',
            'mysqld',
            'searchd',
            'memcached',
            'php'
        );
        $processAnalytics = array();
        foreach($listProcess as $item) {
            $cpu = null; // Текущие использование процессора в %
            $size = null; // Выделено памяти
            $res = null; // Используется памяти
            $time = null; // Общее время использование CPU текущим процессом
            $command = null; // Название выполняемого процесса
            foreach($item as $id => $valueParameter) {
                $nameParameter = $dataList[$id];
                switch( $nameParameter ) {
                    case 'WCPU':
                        $cpu = $valueParameter;
                        break;
                    case 'SIZE':
                        $size = $valueParameter;
                        break;
                    case 'RES':
                        $res = $valueParameter;
                        break;
                    case 'TIME':
                        $time = $valueParameter;
                        break;
                    case 'COMMAND':
                        $command = $valueParameter;
                        break;
                }
            }
            if (in_array($command, $process)) {
                if (isset($cpu)) {
                    $value = (float) $cpu;
                    $processAnalytics[$command][self::STAT_PROCESS_WCPU] += $value;
                }
                if (isset($size)) {
                    $byte = functions::convertSizeFormatInCountByte($size);
                    $processAnalytics[$command][self::STAT_PROCESS_SIZE] += $byte;
                }
                if (isset($res)) {
                    $byte = functions::convertSizeFormatInCountByte($res);
                    $processAnalytics[$command][self::STAT_PROCESS_RES] += $byte;
                }
                if (isset($time)) {
                    // TODO получить количество минут
                }
                $processAnalytics[$command][self::STAT_PROCESS_COUNT]++;
            }
        }

        $output[self::STAT_USING_MEMORY] =     ($usingMemory['Active'] + $usingMemory['Wired'] + $usingMemory['Buf']) / (1024 * 1024);
        $output[self::STAT_APACHE_USING_CPU] = $processAnalytics['httpd'][self::STAT_PROCESS_WCPU];
        $output[self::STAT_APACHE_RCBP] = $processAnalytics['httpd'][self::STAT_PROCESS_COUNT];
        $output[self::STAT_MYSQL_USING_CPU] = $processAnalytics['mysqld'][self::STAT_PROCESS_WCPU];
        $output[self::STAT_MYSQL_USING_MEMORY] = $processAnalytics['mysqld'][self::STAT_PROCESS_RES] / (1024 * 1024);
        $output[self::STAT_MEMCACHE_USING_MEMORY] = $processAnalytics['memcached'][self::STAT_PROCESS_RES] / (1024 * 1024);
        $output[self::STAT_SPHINX_USING_MEMORY] = $processAnalytics['searchd'][self::STAT_PROCESS_RES] / (1024 * 1024);

        return $output;
    }
}
function isNotEmpty($value) {
    return (strlen($value)) != 0;
}

serverStatistics::parserTop();