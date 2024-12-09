<?php declare(strict_types=1);

namespace Cicada\Core\Framework;

use Cicada\Core\Framework\Log\Package;

#[Package('core')]
abstract class HttpException extends CicadaHttpException
{
    protected static string $couldNotFindMessage = 'Could not find {{ entity }} with {{ field }} "{{ value }}"';

    public function __construct(
        protected int $statusCode,
        protected string $errorCode,
        string $message,
        array $parameters = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $parameters, $previous);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function is(string ...$code): bool
    {
        return \in_array($this->errorCode, $code, true);
    }
}