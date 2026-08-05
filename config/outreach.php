<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Outreach sending guardrails
    |--------------------------------------------------------------------------
    |
    | Sending a letter puts real mail in a real company's inbox, so it is only
    | allowed in production unless deliberately opted into. The daily limit is
    | a backstop against a loop or a mis-click emptying the pipeline in one go.
    |
    */

    'allow_send' => (bool) env('OUTREACH_ALLOW_SEND', false),

    'daily_send_limit' => (int) env('OUTREACH_DAILY_SEND_LIMIT', 20),

];
