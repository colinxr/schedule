<?php

namespace App\Exceptions\Appointment;

use Exception;

class AppointmentCreationException extends Exception
{
    public function __construct(
        string $message = 'Failed to create appointment',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function invalidArtist(): self
    {
        return new self('Only artists can create appointments');
    }

    public static function invalidDates(): self
    {
        return new self('Invalid appointment dates');
    }

    public static function invalidConversation(): self
    {
        return new self('Invalid conversation for appointment');
    }
}
