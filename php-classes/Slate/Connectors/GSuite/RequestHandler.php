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

        $command = new CreateEvent(
            $user,
            $requestData['calendarId'] ?: 'primary',
            $requestData['summary'],
            strtotime($requestData['startDateTime']),
            strtotime($requestData['endDateTime']),
            $requestData['attendees'],
            $requestData
        );

        $response = API::execute($command->buildRequest());

        return static::respondJson(
            'google-calendar-create-event',
            [
                'data' => $response,
                'success' => $response && !isset($response['error'])
            ]
        );

    }
}