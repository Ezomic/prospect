<?php

namespace App\Services\Mail;

interface Inbox
{
    public function configured(): bool;

    /**
     * Invoke the handler for each unprocessed inbound message, marking each as
     * processed afterwards.
     *
     * @param  callable(IncomingMessage): void  $handler
     */
    public function eachUnseen(callable $handler): void;
}
