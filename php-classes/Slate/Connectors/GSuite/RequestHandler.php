<?php

namespace Slate\Connectors\GSuite;

use Slate\Connectors\GSuite\API;
use Slate\Connectors\GSuite\Commands\Calendar\CreateEvent;

class RequestHandler extends \RequestHandler
{
    public static function handleRequest($action = null)
    {
        switch ($action = $action ?: static::shiftPath()) {
            case 'create-event':
                return static::handleCreateEventRequest();
                break;

            case '':
            case false:
            default:
                return static::throwInvalidRequestError();
        }
    }

    public static function handleCreateEventRequest()
    {
        $GLOBALS['Session']->requireAuthentication();

        $requestData = $_REQUEST;
        $user = API::getDomainEmail($GLOBALS['Session']->Person);

        // only admin can create on behalf of other users
        if ($GLOBALS['Session']->hasAccountLevel('Administrator')) {
            if (!empty($requestData['user'])) {
                $user = $requestData['user'];
                unset($requestData['user']);
            }
        }

        $attendees = static::getRequestedAttendees();

        $Section = \Slate\RecordsRequestHandler::getRequestedSection('section');
        if ($Section) {
            // append students & teachers as attendants
            $attendees = array_merge(
                $attendees,
                array_filter(array_map(function($Student) {
                    return $Student->PrimaryEmail;
                }, $Section->ActiveStudents)),
                array_filter(array_map(function($Teacher) {
                    return $Teacher->PrimaryEmail;
                }, $Section->ActiveTeachers))
            );
        }

        $extraParams = [
            'conferenceDataVersion' => true
        ];

        if (!empty($requestData['create-hangout'])) {
            $extraParams['conferenceData'] = [
                'createRequest' => [
                    'requestId' => time()
                ]
            ];
            unset($requestData['create-hangout']);
        }

        $command = new CreateEvent(
            $user,
            $requestData['calendarId'] ?: 'primary',
            $requestData['summary'],
            strtotime($requestData['startDateTime']),
            strtotime($requestData['endDateTime']),
            $attendees,
            $extraParams
        );

        $request = $command->buildRequest();
        $response = API::execute($request);

        return static::respondJson(
            'google-calendar-create-event',
            [
                'data' => $response,
                'success' => $response && !isset($response['error'])
            ]
        );

    }

    public static function getRequestedAttendees($fieldName = 'attendees')
    {
        $attendees = [];

        if (!empty($_REQUEST[$fieldName])) {
            if (is_string($_REQUEST[$fieldName])) {
                $attendees = explode(',', $_REQUEST[$fieldName]);
            } elseif (is_array($_REQUEST[$fieldName])) {
                $attendees = $_REQUEST[$fieldName];
            }
            unset($_REQUEST[$fieldName]);
        }

        return $attendees;
    }
}