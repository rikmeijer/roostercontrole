<?php
require __DIR__ . '/vendor/autoload.php';

function map(array $array, Closure $mapper) {
    return array_filter(array_map($mapper, $array));
}
function average(array $array) : float {
    return array_sum($array) / count($array);
}
function append(array $target, $key, array $source) {
    if (array_key_exists($key, $target) === false) {
        $target[$key] = $source;
    } else {
        $target[$key] += $source;
    }
    return $target;
}
function deviation(array $array) {
    return sqrt(average(map($array, function($value) use ($array) {
        return ($value - average($array)) ** 2;
    })));
}

function aggregate(array $source, Closure $aggregateKey) : array {
    $aggregate = [];
    foreach ($source as $sourceKey => $sourceValue) {
        $aggregatedKey = $aggregateKey($sourceKey, $sourceValue);
        $aggregate = append($aggregate, $aggregatedKey, [$sourceKey => $sourceValue]);
    }
    return $aggregate;
}

function days(array $events) {
    return aggregate($events, function(string $eventIdentifier, \ICal\Event $event) {
        return $event->cstart->toDateString();
    });
}
function weeks(array $events) {
    return aggregate(days($events), function($dayIdentifier, $day){
        return Carbon\Carbon::createFromFormat('Y-m-d', $dayIdentifier)->weekOfYear;
    });
}

function prompt(string $question, $default = null) {
    $defaultValueFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . md5($question);
    if (file_exists($defaultValueFile)) {
        $default = file_get_contents($defaultValueFile);
    }
    echo PHP_EOL . $question . ($default ? ' [' . $default . ']' : '') . ': ';
    $answer = Seld\CliPrompt\CliPrompt::prompt();
    if (empty($answer) === false) {
        file_put_contents($defaultValueFile, $answer);
        return $answer;
    } elseif ($default !== null) {
        return $default;
    }
    return prompt($question, $default);
}
function answer(string $answer) : Closure {
    return function(Closure $cconsole) use ($answer) : void { $cconsole($answer, false); };
}
function answerYes() : Closure {
    return answer('Ja!');
}

function duration(array $events) {
    $duration = 0;
    foreach ($events as $chainEvent) {
        $duration += $chainEvent->cstart->diffInMinutes($chainEvent->cend);
    }
    return $duration;
};


function console() : Closure {
    $prompt = function(string $line) : Closure {
        $genericDefault = function(string $default = null) {
            return function(string $answer) use ($default) {
                if (empty($answer)) {
                    return $default;
                } else {
                    return $answer;
                }
            };
        };

        return function(Closure $default = null) use ($genericDefault, $line) {
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
            return function(Closure $transform) use ($answer) {
                return $transform($answer);
            };
        };
    };
    return function(string $line, bool $new_line = true) use ($prompt) : Closure {
        print ($new_line ? PHP_EOL : '') . $line;
        return $prompt($line);
    };
}
function ifcount(array $array, Closure $zero, Closure $more) {
    if (count($array) === 0) {
        return $zero;
    }
    return $more($array);
}

function indent(Closure $console) {
    return function(string $line, bool $new_line = true) use ($console) : Closure {
        return $console(($new_line ? "\t" : '') . $line, $new_line);
    };
}

$rollen = [
    'docent' => "Docent",
    'rve' => "Blokcoördinator",
    'moco' => "Vakcoördinator",
];

array_walk($rollen, function($rol, $rolIdentifier, Closure $console) {
    $console('Als ' . $rol);
    (require __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . $rolIdentifier . '.php')($console);
}, console());

print PHP_EOL;