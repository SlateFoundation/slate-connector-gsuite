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
        $user = $GLOBALS['Session']->Person->PrimaryEmail;

        if ($GLOBALS['Session']->hasAccountLevel('Administrator')) {
            if (!empty($requestData['user'])) {
                $user = $requestData['user'];
                unset($requestData['user']);
            }
        }

        $attendees = [];

        if (!empty($requestData['attendees'])) {
            if (is_string($requestData['attendees'])) {
                $attendees = explode(',', $requestData['attendees']);
            } elseif (is_array($requestData['attendees'])) {
                $attendees = $requestData['attendees'];
            }
            unset($requestData['attendees']);
        }

        $students = \Slate\RecordsRequestHandler::getRequestedStudents('students');
        if (!empty($students)) {
            $attendees = array_merge(
                $attendees,
                array_filter(array_map(function($student) {
                    return $student->PrimaryEmail;
                }, $students))
            );
            unset($requestData['students']);
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
}