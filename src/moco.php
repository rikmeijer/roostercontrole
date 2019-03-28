<?php return function(Closure $console) {
    (require __DIR__ . DIRECTORY_SEPARATOR . 'check.php')([
        "Staan alle lessen van je vak, bij alle groepen, goed in het rooster?" => function (Closure $events): Closure {
        return answer('Onbekend');
    }, "Staan gastcolleges op de juiste plekken?" => function (Closure $events): Closure {
        return answer('Onbekend');
    }], indent($console));
};