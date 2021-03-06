<?php

declare(strict_types=1);

namespace App\JoindIn;

class EventDataFactory
{
    public function create(array $input): EventData
    {
        return new EventData(
            $this->extractIdFromUri($input['uri']),
            $input['name'],
            new \DateTime($input['start_date'])
        );
    }

    private function extractIdFromUri(string $uri): int
    {
        if (preg_match('|https://api.joind.in/v2.1/events/(?<id>[\d]*)$|', $uri, $matches)) {
            return (int) $matches['id'];
        }
        throw new \Exception('Unparsable '.$uri);
    }
}
