<?php
use function \Functional\map;
use function \Functional\average;

return function(Closure $console, Closure $startDateTme) {
    (require __DIR__ . DIRECTORY_SEPARATOR . 'check.php')($startDateTme)([
        "Is het rooster voor alle studentgroepen goed en niet teveel versnipperd over de dagen?" => function(Closure $events) use ($console) : Closure {
            $roostergroepen = $console('Welke roostergroepen? (comma-gescheiden)')()(function(string $answer) use ($events) {
                return array_combine(explode(',', $answer), map(explode(',', $answer), function(string $roostergroep) use ($events) {
                    return $events('https://rooster.avans.nl/gcal/G' . $roostergroep);
                }));
            });
            return ifcount(map($roostergroepen, function(array $roostergroepDagen) {
                return ifcount(map(weeks($roostergroepDagen), function(array $weekdays) {
                    if (count($weekdays) < 3) {
                        return 0;
                    }
                    $counts = map($weekdays, function(array $value, $index, $collection) {
                        return count($value);
                    });
                    return (average($counts) < 2 || deviation($counts) > 0.75) ? 1 : 0;
                }), function() {}, function(array $weeks) {
                    return function() use ($weeks) {
                        return implode(', ', array_keys($weeks));
                    };
                });
            }), answerYes(), function(array $roostergroepenWeken) {
                return function (Closure $console) use ($roostergroepenWeken) {
                    $console('Nee: ', false);
                    foreach ($roostergroepenWeken as $roostergroepIdentifier => $roostergroepenWeek) {
                        $console($roostergroepIdentifier . ': kalenderweken ' . $roostergroepenWeek());
                    }
                };
            });
        },
        "Staan alle vakoverstijgende activiteiten (bijv. kickoff, inzage) goed in het rooster?" => function(Closure $events) : Closure {
            return answer('Onbekend');
        },
        "Is het blokrapportage-overleg geroosterd met de juiste docenten?" => function(Closure $events) : Closure {
            return answer('Onbekend');
        },
    ], indent($console));
};