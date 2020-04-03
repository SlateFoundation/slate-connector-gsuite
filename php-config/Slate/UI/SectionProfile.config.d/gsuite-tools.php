<?php

Slate\UI\SectionProfile::$sources[] = function (Slate\Courses\Section $Section) {
    $links = [];

    if (!empty($GLOBALS['Session']) && $GLOBALS['Session']->hasAccountLevel('Staff')) {
        $sectionStudentEmails = array_filter(array_map(function($Student) {
            return $Student->PrimaryEmail;
        }, $Section->ActiveStudents));
        $links['Create Section Event'] = [
            '_href' => '#create-google-calendar-event',
            '_attribs' => sprintf('data-event-students="section:%s"', $Section->Code)
        ];
    }

    return [
        'GSuite Tools' => $links
    ];
};