<?php return function(Closure $console) {
    (require __DIR__ . DIRECTORY_SEPARATOR . 'check.php')([
        "Is het rooster voor alle studentgroepen goed en niet teveel versnipperd over de dagen?" => function(Closure $events) : Closure {
            $roostergroepIdentifiers = explode(',', prompt('Welke roostergroepen? (comma-gescheiden)'));
            $roostergroepen = array_combine($roostergroepIdentifiers, map($roostergroepIdentifiers, function(string $roostergroep) use ($events) {
                return $events('https://rooster.avans.nl/gcal/G' . $roostergroep);
            }));

            $roostergroepenWeken = [];

            foreach ($roostergroepen as $roostergroepIdentifier => $roostergroepDagen) {
                $weeks = array_filter(map(weeks($roostergroepDagen), function(array $weekdays) {
                    if (count($weekdays) < 3) {
                        return 0;
                    }
                    $counts = map($weekdays, Closure::fromCallable('count'));
                    return (average($counts) < 2 || deviation($counts) > 0.75) ? 1 : 0;
                }));
                if (count($weeks) > 0) {
                    $roostergroepenWeken[$roostergroepIdentifier] = function() use ($weeks) {
                        return implode(', ', array_keys($weeks));
                    };
                }
            }

            if (count($roostergroepenWeken) === 0) {
                return answerYes();
            }

            $write = function(Closure $console) use ($roostergroepenWeken) {
                foreach ($roostergroepenWeken as $roostergroepIdentifier => $roostergroepenWeek) {
                    $console($roostergroepIdentifier . ': kalenderweken ' . $roostergroepenWeek());
                }
            };

            return function (Closure $console) use ($write) {
                $console('Nee: ', false);
                $write(indent($console));
            };
        },
        "Staan alle vakoverstijgende activiteiten (bijv. kickoff, inzage) goed in het rooster?" => function(Closure $events) : Closure {
            return answer('Onbekend');
        },
        "Is het blokrapportage-overleg geroosterd met de juiste docenten?" => function(Closure $events) : Closure {
            return answer('Onbekend');
        },
    ], indent($console));
};