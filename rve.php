<?php
(require __DIR__ . DIRECTORY_SEPARATOR . 'check.php')([
    "Is het rooster voor alle studentgroepen goed en niet teveel versnipperd over de dagen?" => function(Closure $events) : Closure {
        $roostergroepEvents = map(explode(',', prompt('Welke roostergroepen? (comma-gescheiden)')), function(string $roostergroep) use ($events) {
            return $events('https://rooster.avans.nl/gcal/G' . $roostergroep);
        });

        // check them!

        return answerYes();
    },
    "Staan alle vakoverstijgende activiteiten (bijv. kickoff, inzage) goed in het rooster?" => function(Closure $events) : Closure {
        return answerYes();
    },
    "Is het blokrapportage-overleg geroosterd met de juiste docenten?" => function(Closure $events) : Closure {
        return answerYes();
    },
]);