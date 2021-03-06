<?php
require __DIR__ . '/vendor/autoload.php';

use Carbon\Carbon;
use function Functional\average;
use function Functional\filter;
use function Functional\map;
use function Functional\partial_right;

function append(array $target, $key, array $source)
{
    if (array_key_exists($key, $target) === false) {
        $target[$key] = $source;
    } else {
        $target[$key] += $source;
    }
    return $target;
}

function deviation(array $array)
{
    return sqrt(average(map($array, function ($value) use ($array) {
        return ($value - average($array)) ** 2;
    })));
}

function aggregate(array $source, Closure $aggregateKey): array
{
    $aggregate = [];
    foreach ($source as $sourceKey => $sourceValue) {
        $aggregatedKey = $aggregateKey($sourceKey, $sourceValue);
        $aggregate = append($aggregate, $aggregatedKey, [$sourceKey => $sourceValue]);
    }
    return $aggregate;
}

function days(array $events)
{
    return aggregate($events, function (string $eventIdentifier, \ICal\Event $event) {
        return $event->cstart->toDateString();
    });
}

function weeks(array $events)
{
    return aggregate(days($events), function ($dayIdentifier, $day) {
        return Carbon::createFromFormat('Y-m-d', $dayIdentifier)->weekOfYear;
    });
}

function answer(string $answer): Closure
{
    return function (Closure $console) use ($answer) : void {
        $console($answer, false);
    };
}

function answerYes(): Closure
{
    return answer('Ja!');
}

function duration(array $events)
{
    $duration = 0;
    foreach ($events as $chainEvent) {
        $duration += $chainEvent->cstart->diffInMinutes($chainEvent->cend);
    }
    return $duration;
};


function events(string $iCalURL, Closure $eventFilter)
{
    static $events = [];
    if (array_key_exists($iCalURL, $events) === false) {
        $icalReader = new \ICal\ICal($iCalURL);
        $events[$iCalURL] = filter($icalReader->events(), $eventFilter);
        usort($events[$iCalURL], function (\ICal\Event $eventA, \ICal\Event $eventB) {
            return $eventA->cstart->lt($eventB->cstart) ? 0 : 1;
        });
    }
    return $events[$iCalURL];
}

function console(): Closure
{
    $prompt = function (string $line): Closure {
        $genericDefault = function (string $default = null) {
            return function (string $answer) use ($default) {
                if (empty($answer)) {
                    return $default;
                } else {
                    return $answer;
                }
            };
        };

        return function (Closure $default = null) use ($genericDefault, $line) {
            if ($default === null) {
                $default = $genericDefault(null);
            }
            $defaultValueFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . md5($line);
            if (file_exists($defaultValueFile)) {
                $default = $genericDefault(file_get_contents($defaultValueFile));
            }
            print ($default('') ? ' [' . $default('') . ']' : '') . ': ';
            while (true) {
                $answer = $default(Seld\CliPrompt\CliPrompt::prompt());
                if ($answer !== null) {
                    file_put_contents($defaultValueFile, $answer);
                    break;
                }
                print '↳ ';
            }
            return function (Closure $transform) use ($answer) {
                return $transform($answer);
            };
        };
    };
    return function (string $line, bool $new_line = true) use ($prompt) : Closure {
        print ($new_line ? PHP_EOL : '') . $line;
        return $prompt($line);
    };
}


function ifcount(array $array, Closure $zero, Closure $more)
{
    return patterns(when(0, function () use ($zero, $array) {
            return $zero;
        }))(function () use ($more, $array) {
        return $more($array);
    })(count($array));
}

function when($pattern, Closure $then)
{
    if (is_string($pattern) && substr($pattern, 0, 1) === '/' && substr($pattern, -1) === '/') {
        $match = function($value) use ($pattern) : bool {
            return preg_match($pattern, $value) === 1;
        };
    } else {
        $match = function($value) use ($pattern) : bool {
            return $pattern === $value;
        };
    }
    return function ($value, Closure $next) use ($match, $then) {
        if ($match($value)) {
            return $then($value);
        }
        return $next($value);
    };
}

function patterns(Closure $pattern): Closure
{
    $patterns = func_get_args();
    return function (Closure $else) use ($patterns) {
        $patterns[] = function($value, Closure $next) use ($else) {
            return $else($value);
        };
        return function($value) use ($patterns) {
            $current = array_shift($patterns);
            return $current($value, $next = function($value) use (&$patterns, &$next) {
                $current = array_shift($patterns);
                return $current($value, $next);
            });
        };

    };
}

function indent(Closure $console)
{
    return function (string $line, bool $new_line = true) use ($console) : Closure {
        return $console(($new_line ? "\t" : '') . $line, $new_line);
    };
}

$rollen = ["Docent" => require __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'docent.php', "Blokcoördinator" => require __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'rve.php', "Vakcoördinator" => require __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'moco.php',];

\Functional\each($rollen, partial_right(function (Closure $rol, string $rolIdentifier, array $rollen, Closure $console) {
    $console('Als ' . $rolIdentifier);

    $rol($console, $console('Vanaf datum')(
        patterns(
            when('', function (string $answer) {
                return (new Carbon())->toDateString();
            })
        )(function (string $answer) {
            return $answer;
        })
    ));
}, console()));

print PHP_EOL;