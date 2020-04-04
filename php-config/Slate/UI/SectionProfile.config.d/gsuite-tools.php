<?php

Slate\UI\SectionProfile::$sources[] = function (Slate\Courses\Section $Section) {
    $links = [];

    if (!empty($GLOBALS['Session']) && $GLOBALS['Session']->hasAccountLevel('Staff')) {
        $sectionStudentEmails = array_filter(array_map(function($Student) {
            return $Student->PrimaryEmail;
        }, $Section->ActiveStudents));
        $links['Create Section Event'] = [
            '_href' => '#create-google-calendar-event',
            '_attribs' => sprintf(
                'data-title="%s" data-description="%s" data-event-students="section:%s" data-event-create-hangout="1"',
                sprintf('Create %s Hangout', $Section->Title),
                'Create a Google Calendar Event with all Active Students in this section automatically added as attendees.',
                $Section->Code
            )
        ];
    }

    return [
        'GSuite Tools' => $links
    ];
};