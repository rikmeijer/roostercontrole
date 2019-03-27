<?php
(require __DIR__ . DIRECTORY_SEPARATOR . 'check.php')('https://rooster.avans.nl/gcal/Dhameijer', [
    "Is het rooster voor alle studentgroepen goed en niet teveel versnipperd over de dagen?" => function(array $events) : Closure {
        $roostergroepen = explode(',', prompt('Welke roostergroepen? (comma-gescheiden)'));


        return answerYes();
    },
    "Staan alle vakoverstijgende activiteiten (bijv. kickoff, inzage) goed in het rooster?" => function(array $events) : Closure {
        return answerYes();
    },
    "Is het blokrapportage-overleg geroosterd met de juiste docenten?" => function(array $events) : Closure {
        return answerYes();
    },
]);