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

    /*
    | How long a letter may sit in Sending before it is treated as stuck and
    | can be released back to Ready by hand. A worker killed mid-send (a deploy
    | restart, or queue:work recycling on --max-time) leaves no failed job
    | behind, so nothing else would ever free it.
    */
    'stuck_after_minutes' => (int) env('OUTREACH_STUCK_AFTER_MINUTES', 5),

    /*
    | Days after a letter goes out before the company surfaces as a follow-up.
    | Set to 0 to leave follow-ups entirely manual.
    */
    'follow_up_after_days' => (int) env('OUTREACH_FOLLOW_UP_AFTER_DAYS', 14),

];
