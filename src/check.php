<?php
use function \Functional\filter;
return function(array $checks, Closure $console) {
    $fEvents = function(string $iCalURL) use ($console) : array {
        static $events = [];
        if (array_key_exists($iCalURL, $events) === false) {
            $icalReader = new \ICal\ICal($iCalURL);
            $events[$iCalURL] = filter($icalReader->events(), (function(\Carbon\Carbon $startDateTime) use ($iCalURL, $console) {
                $startDateTime = $console('(' . basename($iCalURL) . ') Vanaf datum')(function (string $answer) use ($startDateTime) {
                    if (empty($answer)) {
                        return $startDateTime->toDateString();
                    } else {
                        return $answer;
                    }
                })(function(string $answer) {
                    return \Carbon\Carbon::createFromFormat(\Carbon\Carbon::DEFAULT_TO_STRING_FORMAT, $answer . " 00:00:00");
                });
                return function (\ICal\Event $event) use ($startDateTime) {

                    $event->cstart = \Carbon\Carbon::createFromFormat(\ICal\ICal::DATE_TIME_FORMAT, $event->dtstart_tz);
                    $event->work = $event->blocking = true;

                    if ($event->cstart->lessThanOrEqualTo($startDateTime)) {
                        return false;
                    } elseif (preg_match('/(Blokkade IN|Roosterblokkade|Colloquium)/', $event->summary) === 1) {
                        $event->blocking = false;
                    } elseif (preg_match('/(Pasen|Hemelvaart|Roostervrij|Pinksteren|Goede vrijdag|Roostervrij)/', $event->summary) === 1) {
                        $event->blocking = false;
                        $event->work = false;
                    }
                    $event->cend = \Carbon\Carbon::createFromFormat(\ICal\ICal::DATE_TIME_FORMAT, $event->dtend_tz);
                    return true;
                };
            })(new \Carbon\Carbon()));
            usort($events[$iCalURL], function(\ICal\Event $eventA, \ICal\Event $eventB) {
                return $eventA->cstart->lt($eventB->cstart) ? 0 : 1;
            });
        }
        return $events[$iCalURL];
    };

    foreach ($checks as $checkIdentifier => $check) {
        $result = $check($fEvents);
        $console($checkIdentifier . ' ');
        $result(indent($console));
    }
};
