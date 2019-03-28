<?php

function duration(array $events) {
    $duration = 0;
    foreach ($events as $chainEvent) {
        $duration += $chainEvent->cstart->diffInMinutes($chainEvent->cend);
    }
    return $duration;
};

(require __DIR__ . DIRECTORY_SEPARATOR . 'check.php')([
    'Klopt het rooster met je inzet en je harde blokkades?' => function(Closure $events) : Closure {
        $badEvents = array_filter($events('https://rooster.avans.nl/gcal/Dhameijer'), function(\ICal\Event $event) : bool {
            return $event->blocking && ($event->cstart->isFriday() || $event->cend->isFriday());
        });
        if (count($badEvents) === 0) {
            return answerYes();
        }
        return function() use ($badEvents) : void {
            print 'Nee: ';
            foreach ($badEvents as $badEvent) {
                print PHP_EOL . "\t- " . $badEvent->cstart->toDateString() . ': ' . $badEvent->summary;
            }
        };
    },
    'Zijn er dubbelboekingen die problemen opleveren?' => function(Closure $events) : Closure {
        $events = $events('https://rooster.avans.nl/gcal/Dhameijer');
        $collidingEvents = array_filter($events, function(\ICal\Event $event) use ($events) : bool {

            $event->collisions = [];
            if ($event->blocking) {
                foreach ($events as $pastEvent) {
                    if ($pastEvent->blocking === false) {
                        continue;
                    } elseif ($event->uid === $pastEvent->uid) {
                        continue;
                    }
                    if ($event->cstart->lessThanOrEqualTo($pastEvent->cstart) && $event->cstart->greaterThanOrEqualTo($pastEvent->cstart)) {
                        $event->collisions[] = $pastEvent;
                    } elseif ($event->cend->lessThanOrEqualTo($pastEvent->cend) && $event->cend->greaterThanOrEqualTo($pastEvent->cend)) {
                        $event->collisions[] = $pastEvent;
                    }
                }
            }
            return count($event->collisions) > 0;
        });
        if (count($collidingEvents) === 0) {
            return answer('Nee!');
        }

        return function() use ($collidingEvents) : void {
            print 'Ja: ';
            foreach ($collidingEvents as $collidingEvent) {
                print PHP_EOL . "\t- [" . $collidingEvent->cstart->toDateString() . '] ' . ($collidingEvent->summary);;
                foreach ($collidingEvent->collisions as $matchedEvent) {
                    print PHP_EOL . "\t\t- " . ($matchedEvent->summary);

                }
            }
        };
    },
    'Staan eventuele incidentele blokkades goed in je rooster?' => function(Closure $events) : Closure {
        return answer('Onbekend');
    },
    'Is er een redelijke verdeling van geroosterde uren?' => function(Closure $events) : Closure {
        return answer('Onbekend');
    },
    'Zijn alle dagen te doen?' => function(Closure $events) : Closure {
        $hardDays = array_filter(map(days($events('https://rooster.avans.nl/gcal/Dhameijer')), function(array $dayEvents) : ?array {
            if (count($dayEvents) < 4) {
                return null;
            } elseif (reset($dayEvents)->cstart->diffInMinutes(end($dayEvents)->cend) < 6*60) {
                return null;
            }
            return $dayEvents;
        }));


        if (count($hardDays) === 0) {
            return answerYes();
        }

        return function() use ($hardDays) : void {
            print 'Nee: ';
            foreach ($hardDays as $dayIdentifier => $hardDay) {
                print PHP_EOL  . "\t- " . $dayIdentifier . ': ' . duration($hardDay) . ' min aaneengesloten';
                foreach ($hardDay as $hardEvent) {
                    print PHP_EOL . "\t\t- [" . $hardEvent->cstart->toTimeString() . " - " . $hardEvent->cend->toTimeString() ."] " . ($hardEvent->summary);
                }
            }
        };
    },
    'Genoeg ruimte in je rooster zit om stage- en afstudeerbezoeken te organiseren?' => function(Closure $events) : Closure {
        $events = $events('https://rooster.avans.nl/gcal/Dhameijer');
        $preferredKalenderweekAfstudeerbezoek = (int)prompt('Kalenderweek afstudeerbezoek');

        $range = [$preferredKalenderweekAfstudeerbezoek - 1, $preferredKalenderweekAfstudeerbezoek, $preferredKalenderweekAfstudeerbezoek+1];

        $possibleKalenderweken = array_filter($range, function(int $kalenderweekAfstudeerbezoek) use ($events){
            $kalenderweekAfstudeerbezoekEvents = array_filter($events, function(\ICal\Event $event) use ($kalenderweekAfstudeerbezoek) {
                return $event->cstart->weekOfYear === $kalenderweekAfstudeerbezoek;
            });

            $days = days($kalenderweekAfstudeerbezoekEvents);

            foreach ($days as $dayEvents) {
                if (duration($dayEvents) < 4*60) {
                    return true;
                }
            }
            return false;
        });

        if (count($possibleKalenderweken) > 0) {
            return answer('Ja (KW ' . implode(', ', $possibleKalenderweken) . ')');
        }
        return answer('Nee, zie KW ' . implode(', ', $range));
    }
]);