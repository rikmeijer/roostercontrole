<?php
return function(array $checks, Closure $console) {
    $fEvents = (function(Closure $startDateTime) {
        return function(string $iCalURL) use ($startDateTime) : array {
            return events($iCalURL, function (\ICal\Event $event) use ($startDateTime) {
                $event->cstart = \Carbon\Carbon::createFromFormat(\ICal\ICal::DATE_TIME_FORMAT, $event->dtstart_tz);
                $event->work = $event->blocking = true;

                if ($event->cstart->lessThanOrEqualTo($startDateTime(function(string $answer) {
                    return \Carbon\Carbon::createFromFormat(\Carbon\Carbon::DEFAULT_TO_STRING_FORMAT, $answer . " 00:00:00");
                }))) {
                    return false;
                } elseif (preg_match('/((Teamoverleg|Blokkade) IN|Roosterblokkade|Colloquium)/', $event->summary) === 1) {
                    $event->blocking = false;
                } elseif (preg_match('/(Jarigen lunch|Pasen|Hemelvaart|Roostervrij|Pinksteren|Goede vrijdag|Roostervrij)/', $event->summary) === 1) {
                    $event->blocking = false;
                    $event->work = false;
                }
                $event->cend = \Carbon\Carbon::createFromFormat(\ICal\ICal::DATE_TIME_FORMAT, $event->dtend_tz);
                return true;
            });
        };
    })($console('Vanaf datum')(function (string $answer) {
        if (empty($answer)) {
            return (new \Carbon\Carbon())->toDateString();
        } else {
            return $answer;
        }
    }));

    foreach ($checks as $checkIdentifier => $check) {
        $result = $check($fEvents);
        $console($checkIdentifier . ' ');
        $result(indent($console));
    }
};
