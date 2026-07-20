<?php

return [
    'recurring_events' => (bool) env('FEATURE_RECURRING_EVENTS', false),
    'event_social_chat' => (bool) env('FEATURE_EVENT_SOCIAL_CHAT', false),
    'event_push_notifications' => (bool) env('FEATURE_EVENT_PUSH_NOTIFICATIONS', false),
];
