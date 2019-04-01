<?php

use function Functional\filter;
use function Functional\map;

return function (Closure $console) {
    (require __DIR__ . DIRECTORY_SEPARATOR . 'check.php')(['Klopt het rooster met je inzet en je harde blokkades?' => function (Closure $events): Closure {
        return ifcount(filter($events('https://rooster.avans.nl/gcal/Dhameijer'), function (\ICal\Event $event): bool {
            return $event->blocking && ($event->cstart->isFriday() || $event->cend->isFriday());
        }), answerYes(), function (array $badEvents) {
            return function (Closure $console) use ($badEvents) : void {
                $console('Nee: ', false);
                foreach ($badEvents as $badEvent) {
                    $console("\t- " . $badEvent->cstart->toDateString() . ': ' . $badEvent->summary);
                }
            };
        });
    }, 'Zijn er dubbelboekingen die problemen opleveren?' => function (Closure $events): Closure {
        return ifcount(filter($events('https://rooster.avans.nl/gcal/Dhameijer'), function (\ICal\Event $event) use ($events) : bool {
            $event->collisions = [];
            if ($event->blocking) {
                foreach ($events('https://rooster.avans.nl/gcal/Dhameijer') as $pastEvent) {
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
        }), answer('Nee!'), function (array $collidingEvents) {
            return function (Closure $console) use ($collidingEvents) : void {
                $console('Ja', false);
                foreach ($collidingEvents as $collidingEvent) {
                    $console("- [" . $collidingEvent->cstart->toDateString() . '] ' . ($collidingEvent->summary));
                    foreach ($collidingEvent->collisions as $matchedEvent) {
                        $console("\t- " . ($matchedEvent->summary));

                    }
                }
            };
        });
    }, 'Staan eventuele incidentele blokkades goed in je rooster?' => function (Closure $events): Closure {
        return answer('Onbekend');
    }, 'Is er een redelijke verdeling van geroosterde uren?' => function (Closure $events): Closure {
        return answer('Onbekend');
    }, 'Zijn alle dagen te doen?' => function (Closure $events): Closure {
        return ifcount(array_filter(map(days($events('https://rooster.avans.nl/gcal/Dhameijer')), function (array $dayEvents): ?array {
            if (count($dayEvents) < 4) {
                return null;
            } elseif (reset($dayEvents)->cstart->diffInMinutes(end($dayEvents)->cend) < 6 * 60) {
                return null;
            }
            return $dayEvents;
        })), answerYes(), function (array $hardDays) {
            return function (Closure $console) use ($hardDays) : void {
                $console('Nee: ');
                foreach ($hardDays as $dayIdentifier => $hardDay) {
                    $console("- " . $dayIdentifier . ': ' . duration($hardDay) . ' min aaneengesloten');
                    foreach ($hardDay as $hardEvent) {
                        $console("\t- [" . $hardEvent->cstart->toTimeString() . " - " . $hardEvent->cend->toTimeString() . "] " . ($hardEvent->summary));
                    }
                }
            };
        });
    }, 'Genoeg ruimte in je rooster zit om stage- en afstudeerbezoeken te organiseren?' => function (Closure $events) use ($console): Closure {
        $events = $events('https://rooster.avans.nl/gcal/Dhameijer');
        $preferredKalenderweekAfstudeerbezoek = $console('Kalenderweek afstudeerbezoek')()(function (string $answer) {
            return (int)$answer;
        });

        $range = [$preferredKalenderweekAfstudeerbezoek - 1, $preferredKalenderweekAfstudeerbezoek, $preferredKalenderweekAfstudeerbezoek + 1];

        $possibleKalenderweken = filter($range, function (int $kalenderweekAfstudeerbezoek) use ($events) {
            $kalenderweekAfstudeerbezoekEvents = filter($events, function (\ICal\Event $event) use ($kalenderweekAfstudeerbezoek) {
                return $event->cstart->weekOfYear === $kalenderweekAfstudeerbezoek;
            });

            $days = days($kalenderweekAfstudeerbezoekEvents);

            foreach ($days as $dayEvents) {
                if (duration($dayEvents) < 4 * 60) {
                    return true;
                }
            }
            return false;
        });

        if (count($possibleKalenderweken) > 0) {
            return answer('Ja (KW ' . implode(', ', $possibleKalenderweken) . ')');
        }
        return answer('Nee, zie KW ' . implode(', ', $range));
    }], indent($console));
};