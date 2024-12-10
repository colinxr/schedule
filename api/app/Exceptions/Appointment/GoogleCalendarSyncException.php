<?php

namespace App\Exceptions\Appointment;

use Exception;

class GoogleCalendarSyncException extends Exception
{
    public function __construct(
        string $message = 'Failed to sync with Google Calendar',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function failedToCreate(): self
    {
        return new self('Failed to create Google Calendar event');
    }

    public static function failedToUpdate(): self
    {
        return new self('Failed to update Google Calendar event');
    }

    public static function failedToDelete(): self
    {
        return new self('Failed to delete Google Calendar event');
    }
}
