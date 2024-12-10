<?php declare(strict_types=1);

namespace Cicada\Frontend\Framework\Captcha\BasicCaptcha;

use Cicada\Core\Framework\Log\Package;

#[Package('frontend')]
abstract class AbstractBasicCaptchaGenerator
{
    abstract public function generate(): BasicCaptchaImage;
}
