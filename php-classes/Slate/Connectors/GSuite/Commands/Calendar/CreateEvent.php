<?php

namespace Slate\Connectors\GSuite\Commands\Calendar;

use Emergence\Connectors\ICommand;
use Emergence\Http\Message\Uri;
use Emergence\People\ContactPoint\Email;
use Slate\Connectors\GSuite\API;

class CreateEvent implements ICommand
{
    private $user;
    private $calendarId;
    private $summary;
    private $attendees;
    private $startDateTime;
    private $endDateTime;
    private $optionalParams;

    public function __construct(
        $user,
        $calendarId = 'primary',
        $summary,
        $startDateTime,
        $endDateTime,
        array $attendees = [],
        array $optionalParams = []
    )
    {
        $this->user = $user;
        $this->calendarId = $calendarId;
        $this->summary = $summary;
        $this->startDateTime = $startDateTime;
        $this->endDateTime = $endDateTime;
        $this->attendees = $attendees;
        $this->optionalParams = $optionalParams;
    }

    public function describe()
    {
        return [
            'CREATE Calendar Event {eventName} on behalf of {user} with attendees: {attendees}',
            [
                'user' => $this->user,
                'eventName' => $this->summary,
                'attendees' => join(
                    ', ',
                    array_map(function($attendee) {
                        return $attendee['email'];
                    }, $this->attendees))
            ]
        ];
    }

    public function buildRequest()
    {
        $headers = [
            'Authorization' => 'Bearer '. API::getAccessToken('https://www.googleapis.com/auth/calendar', (string)$this->user)
        ];

        $params = array_merge($this->optionalParams, [
            'summary' => $this->summary,
            'start' => [
                'dateTime' => $this->getFormattedStartDateTime()
            ],
            'end' => [
                'dateTime' => $this->getFormattedEndDateTime()
            ],
            'attendees' => $this->getFormattedAttendees()
        ]);

        $uri = new Uri(sprintf('/calendar/v3/calendars/%s/events', $this->calendarId));

        if (isset($params['conferenceDataVersion'])) {
            $uri = Uri::withQueryValue($uri, 'conferenceDataVersion', 1);
        }

        return API::buildRequest(
            'POST',
            $uri,
            $params,
            $headers
        );
    }

    public function getFormattedAttendees()
    {
        $formattedAttendees = [];
        foreach ($this->attendees as $attendee) {
            if (is_string($attendee)) {
                $formattedAttendees[] = [
                    'email' => $attendee
                ];
            } elseif (is_object($attendee) && is_a($attendee, Email::class)) {
                $formattedAttendees[] = [
                    'email' => (string)$attendee
                ];
            } elseif (is_array($attendee)) {
                if (!isset($attendee['email'])) {
                    throw new \Exception('Invalid event attendee');
                }
                $formattedAttendees[] = $attendee;
            }
        }

        return $formattedAttendees;
    }

    public function getFormattedStartDateTime()
    {
        return date('c', $this->startDateTime);
    }

    public function getFormattedEndDateTime()
    {
        return date('c', $this->endDateTime);
    }
}