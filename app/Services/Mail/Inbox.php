<?php

namespace App\Services\Mail;

interface Inbox
{
    public function configured(): bool;

    /**
     * Invoke the handler for each unread inbound message.
     *
     * The handler returns true when it acted on the message, and only those
     * are marked read. Anything else stays unread so it is still visible to a
     * human: this mailbox receives ordinary mail too, and silently swallowing
     * it is how a shared mailbox becomes unusable.
     *
     * @param  callable(IncomingMessage): bool  $handler
     */
    public function eachUnseen(callable $handler): void;
}
