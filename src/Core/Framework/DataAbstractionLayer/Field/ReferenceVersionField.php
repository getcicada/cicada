<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Field;

use Cicada\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\ReferenceVersionFieldSerializer;
use Cicada\Core\Framework\DataAbstractionLayer\Version\VersionDefinition;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class ReferenceVersionField extends FkField
{
    /**
     * @deprecated tag:v6.7.0 - Will be natively typed
     *
     * @var string
     */
    protected $versionReferenceClass;

    /**
     * @deprecated tag:v6.7.0 - Will be natively typed
     *
     * @var EntityDefinition
     */
    protected $versionReferenceDefinition;

    /**
     * @deprecated tag:v6.7.0 - Will be natively typed
     *
     * @var string
     */
    protected $storageName;

    public function __construct(
        string $definition,
        ?string $storageName = null
    ) {
        $entity = $definition;
        if (\is_subclass_of($definition, EntityDefinition::class)) {
            $entity = (new $definition())->getEntityName();
        }

        $storageName ??= $entity . '_version_id';

        $propertyName = explode('_', $storageName);
        $propertyName = array_map('ucfirst', $propertyName);
        $propertyName = lcfirst(implode('', $propertyName));

        parent::__construct($storageName, $propertyName, VersionDefinition::class);

        $this->versionReferenceClass = $definition;
        $this->storageName = $storageName;
    }

    public function getVersionReferenceDefinition(): EntityDefinition
    {
        if ($this->versionReferenceDefinition === null) {
            $this->compileLazy();
        }

        return $this->versionReferenceDefinition;
    }

    public function getVersionReferenceClass(): string
    {
        if ($this->versionReferenceClass === null) {
            $this->compileLazy();
        }

        return $this->versionReferenceClass;
    }

    protected function getSerializerClass(): string
    {
        return ReferenceVersionFieldSerializer::class;
    }

    protected function compileLazy(): void
    {
        parent::compileLazy();

        \assert($this->registry !== null, 'registry could not be null, because the `compile` method must be called first');
        $this->versionReferenceDefinition = $this->registry->getByClassOrEntityName($this->versionReferenceClass);
        $this->versionReferenceClass = $this->versionReferenceDefinition->getClass();
    }
}