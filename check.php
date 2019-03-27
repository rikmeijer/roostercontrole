<?php
require __DIR__ . '/vendor/autoload.php';

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
    return function() use ($answer) : void { print $answer; };
}
function answerYes() : Closure {
    return answer('Ja!');
}

return function(string $iCalURL, array $checks) {

    $icalReader = new \ICal\ICal($iCalURL);
    $events = array_filter($icalReader->events(), (function(\Carbon\Carbon $startDateTime) {
        $answer = prompt('Vanaf datum', $startDateTime->toDateString());
        $startDateTime = \Carbon\Carbon::createFromFormat(\Carbon\Carbon::DEFAULT_TO_STRING_FORMAT, $answer . " 00:00:00");
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

    usort($events, function(\ICal\Event $eventA, \ICal\Event $eventB) {
        return $eventA->cstart->lt($eventB->cstart) ? 0 : 1;
    });

    foreach ($checks as $checkIdentifier => $check) {
        $result = $check($events);
        print PHP_EOL . $checkIdentifier . ' ';
        $result();
    }


    exit(PHP_EOL . 'Done' . PHP_EOL);
};
