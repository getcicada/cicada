<?php declare(strict_types=1);

namespace Cicada\Core\System\Channel;

use Cicada\Frontend\Member\Exception\MemberNotFoundByIdException;
use Cicada\Core\Checkout\Payment\PaymentException;
use Cicada\Core\Framework\HttpException;
use Cicada\Core\Framework\Log\Package;
use Cicada\Core\Framework\CicadaHttpException;
use Symfony\Component\HttpFoundation\Response;

#[Package('frontend')]
class ChannelException extends HttpException
{
    final public const SALES_CHANNEL_DOES_NOT_EXISTS_EXCEPTION = 'SYSTEM__SALES_CHANNEL_DOES_NOT_EXISTS';

    final public const LANGUAGE_INVALID_EXCEPTION = 'SYSTEM__LANGUAGE_INVALID_EXCEPTION';

    final public const COUNTRY_DOES_NOT_EXISTS_EXCEPTION = 'SYSTEM__COUNTRY_DOES_NOT_EXISTS_EXCEPTION';

    final public const CURRENCY_DOES_NOT_EXISTS_EXCEPTION = 'SYSTEM__CURRENCY_DOES_NOT_EXISTS_EXCEPTION';

    final public const COUNTRY_STATE_DOES_NOT_EXISTS_EXCEPTION = 'SYSTEM__COUNTRY_STATE_DOES_NOT_EXISTS_EXCEPTION';

    final public const SALES_CHANNEL_LANGUAGE_NOT_AVAILABLE_EXCEPTION = 'SYSTEM__SALES_CHANNEL_LANGUAGE_NOT_AVAILABLE_EXCEPTION';

    final public const NO_CONTEXT_DATA_EXCEPTION = 'SYSTEM__NO_CONTEXT_DATA_EXCEPTION';

    final public const LANGUAGE_NOT_FOUND = 'SYSTEM__LANGUAGE_NOT_FOUND';

    final public const SALES_CHANNEL_DOMAIN_IN_USE = 'SYSTEM__SALES_CHANNEL_DOMAIN_IN_USE';

    public const INVALID_TYPE = 'FRAMEWORK__INVALID_TYPE';

    public static function channelNotFound(string $channelId): self
    {
        return new self(
            Response::HTTP_NOT_FOUND,
            self::SALES_CHANNEL_DOES_NOT_EXISTS_EXCEPTION,
            'Sales channel with id "{{ channelId }}" not found or not valid!.',
            ['channelId' => $channelId]
        );
    }

    public static function currencyNotFound(string $currencyId): self
    {
        return new self(
            Response::HTTP_NOT_FOUND,
            self::CURRENCY_DOES_NOT_EXISTS_EXCEPTION,
            self::$couldNotFindMessage,
            ['entity' => 'currency', 'field' => 'id', 'value' => $currencyId]
        );
    }

    public static function countryStateNotFound(string $countryStateId): self
    {
        return new self(
            Response::HTTP_NOT_FOUND,
            self::COUNTRY_STATE_DOES_NOT_EXISTS_EXCEPTION,
            self::$couldNotFindMessage,
            ['entity' => 'country state', 'field' => 'id', 'value' => $countryStateId]
        );
    }

    public static function memberNotFoundByIdException(string $memberId): CicadaHttpException
    {
        return new MemberNotFoundByIdException($memberId);
    }

    public static function countryNotFound(string $countryId): self
    {
        return new self(
            Response::HTTP_NOT_FOUND,
            self::COUNTRY_DOES_NOT_EXISTS_EXCEPTION,
            self::$couldNotFindMessage,
            ['entity' => 'country', 'field' => 'id', 'value' => $countryId]
        );
    }

    public static function noContextData(string $channelId): self
    {
        return new self(
            Response::HTTP_PRECONDITION_FAILED,
            self::NO_CONTEXT_DATA_EXCEPTION,
            'No context data found for Channel "{{ channelId }}"',
            ['channelId' => $channelId]
        );
    }

    public static function invalidLanguageId(): CicadaHttpException
    {
        return new self(
            Response::HTTP_PRECONDITION_FAILED,
            self::LANGUAGE_INVALID_EXCEPTION,
            'Provided languageId is not a valid uuid',
        );
    }

    public static function languageNotFound(string $languageId): CicadaHttpException
    {
        return new self(
            Response::HTTP_PRECONDITION_FAILED,
            self::LANGUAGE_NOT_FOUND,
            self::$couldNotFindMessage,
            ['entity' => 'language', 'field' => 'id', 'value' => $languageId]
        );
    }

    /**
     * @param array<string> $availableLanguages
     */
    public static function providedLanguageNotAvailable(string $languageId, array $availableLanguages): self
    {
        return new self(
            Response::HTTP_PRECONDITION_FAILED,
            self::SALES_CHANNEL_LANGUAGE_NOT_AVAILABLE_EXCEPTION,
            \sprintf('Provided language "%s" is not in list of available languages: %s', $languageId, implode(', ', $availableLanguages)),
        );
    }

    public static function unknownPaymentMethod(string $paymentMethodId): CicadaHttpException
    {
        return PaymentException::unknownPaymentMethodById($paymentMethodId);
    }

    public static function channelDomainInUse(?\Throwable $previous = null): CicadaHttpException
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::SALES_CHANNEL_DOMAIN_IN_USE,
            'The sales channel domain cannot be deleted because it is still referenced in product exports.',
            [],
            $previous
        );
    }

    public static function invalidType(string $message): self
    {
        return new self(
            Response::HTTP_INTERNAL_SERVER_ERROR,
            self::INVALID_TYPE,
            $message
        );
    }
}
