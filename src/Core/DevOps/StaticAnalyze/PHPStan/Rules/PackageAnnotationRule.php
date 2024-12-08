<?php declare(strict_types=1);

namespace Cicada\Core\DevOps\StaticAnalyze\PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPUnit\Framework\TestCase;
use Cicada\Core\Framework\Log\Package;

/**
 * @implements Rule<InClassNode>
 *
 * @internal
 */
#[Package('core')]
class PackageAnnotationRule implements Rule
{
    /**
     * @internal
     */
    private const PRODUCT_AREA_MAPPING = [
        'services-settings' => [
            '/Cicada\\\\Core\\\\Framework\\\\(Update)\\\\/',
            '/Cicada\\\\Core\\\\Framework\\\\Store\\\\/',
        ],
        'core' => [
            '/Cicada\\\\Core\\\\Framework\\\\(Adapter|Api|App|Changelog|DataAbstractionLayer|Demodata|DependencyInjection)\\\\/',
            '/Cicada\\\\Core\\\\Framework\\\\(Increment|Log|MessageQueue|Migration|Parameter|Plugin|RateLimiter|Script|Routing|Struct|Util|Uuid|Validation|Webhook)\\\\/',
            '/Cicada\\\\Core\\\\DevOps\\\\/',
            '/Cicada\\\\Core\\\\Installer\\\\/',
            '/Cicada\\\\Core\\\\Maintenance\\\\/',
            '/Cicada\\\\Core\\\\Migration\\\\/',
            '/Cicada\\\\Core\\\\Profiling\\\\/',
            '/Cicada\\\\Elasticsearch\\\\/',
            '/Cicada\\\\Docs\\\\/',
            '/Cicada\\\\Core\\\\System\\\\(Annotation|CustomEntity|DependencyInjection|SystemConfig)\\\\/',
            '/Cicada\\\\.*\\\\(DataAbstractionLayer)\\\\/',
        ],
        'administration' => [
            '/Cicada\\\\Administration\\\\/',
        ],
        'data-services' => [
            '/Cicada\\\\Core\\\\System\\\\UsageData\\\\/',
        ],
    ];

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @param InClassNode $node
     *
     * @return array<array-key, RuleError|string>
     */
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->getClassReflection()->isAnonymous()) {
            return [];
        }

        if ($this->isTestClass($node)) {
            return [];
        }

        $area = $this->getProductArea($node);

        if ($this->hasPackageAnnotation($node)) {
            return [];
        }

        return [
            RuleErrorBuilder::message(\sprintf('This class is missing the "#[Package(...)]" attribute (recommendation: %s)', $area ?? 'unknown'))
                ->identifier('shopware.missingPackageAttribute')
                ->build(),
        ];
    }

    private function getProductArea(InClassNode $node): ?string
    {
        $namespace = $node->getClassReflection()->getName();

        foreach (self::PRODUCT_AREA_MAPPING as $area => $regexes) {
            foreach ($regexes as $regex) {
                if (preg_match($regex, $namespace)) {
                    return $area;
                }
            }
        }

        return null;
    }

    private function hasPackageAnnotation(InClassNode $class): bool
    {
        foreach ($class->getOriginalNode()->attrGroups as $group) {
            $name = $group->attrs[0]->name;

            if ($name->toString() === Package::class) {
                return true;
            }
        }

        return false;
    }

    private function isTestClass(InClassNode $node): bool
    {
        $namespace = $node->getClassReflection()->getName();

        if (\str_contains($namespace, '\\Tests\\') || \str_contains($namespace, '\\Test\\')) {
            return true;
        }

        $file = (string) $node->getClassReflection()->getFileName();
        if (\str_contains($file, '/tests/') || \str_contains($file, '/Tests/') || \str_contains($file, '/Test/')) {
            return true;
        }

        if ($node->getClassReflection()->getParentClass() === null) {
            return false;
        }

        return $node->getClassReflection()->getParentClass()->getName() === TestCase::class;
    }
}
