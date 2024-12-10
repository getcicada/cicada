<?php declare(strict_types=1);

namespace Cicada\Frontend\Framework\Twig\TokenParser;

use Cicada\Core\Framework\Adapter\Twig\Node\SwInclude;
use Cicada\Core\Framework\Log\Package;
use Twig\Node\Expression\ArrayExpression;
use Twig\Node\Expression\ConstantExpression;
use Twig\Parser;
use Twig\Token;
use Twig\TokenParser\AbstractTokenParser;

#[Package('frontend')]
final class ThumbnailTokenParser extends AbstractTokenParser
{
    /**
     * @var Parser
     *
     * @deprecated tag:v6.7.0 - Will be natively typed
     */
    protected $parser;

    public function parse(Token $token): SwInclude
    {
        $expr = $this->parser->getExpressionParser()->parseExpression();
        $stream = $this->parser->getStream();

        $className = $expr->getAttribute('value');
        $expr->setAttribute('value', '@Frontend/frontend/utilities/thumbnail.html.twig');

        $variables = new ArrayExpression([], $token->getLine());
        if ($stream->nextIf(Token::NAME_TYPE, 'with')) {
            /** @var ArrayExpression $variables */
            $variables = $this->parser->getExpressionParser()->parseExpression();
        }

        $stream->next();

        $variables->addElement(
            new ConstantExpression($className, $token->getLine()),
            new ConstantExpression('name', $token->getLine())
        );

        return new SwInclude($expr, $variables, false, false, $token->getLine());
    }

    public function getTag(): string
    {
        return 'sw_thumbnails';
    }
}