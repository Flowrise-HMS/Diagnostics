<?php

namespace Modules\Diagnostics\Exceptions;

use RuntimeException;

class EmptyDiagnosticResultException extends RuntimeException
{
    public static function make(): self
    {
        return new self('Enter at least one result value, findings text, or attach a report file.');
    }
}
