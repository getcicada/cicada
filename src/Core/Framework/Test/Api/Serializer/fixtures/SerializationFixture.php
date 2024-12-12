<?php declare(strict_types=1);

namespace Cicada\Core\Framework\Test\Api\Serializer\fixtures;

use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @internal
 */
abstract class SerializationFixture
{
    public const API_BASE_URL = 'http://localhost/api';
    public const SALES_CHANNEL_API_BASE_URL = 'http://localhost/store-api';
    public const API_VERSION = 1;

    /**
     * @return EntityCollection<Entity>|Entity
     */
    abstract public function getInput(): EntityCollection|Entity;

    /**
     * @return array<string, mixed>
     */
    public function getAdminJsonApiFixtures(): array
    {
        $fixtures = $this->getJsonApiFixtures(self::API_BASE_URL);

        return $this->removeProtectedAdminJsonApiData($fixtures);
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getChannelJsonApiFixtures(): array
    {
        $fixtures = $this->getJsonApiFixtures(self::SALES_CHANNEL_API_BASE_URL);

        return $this->removeProtectedChannelJsonApiData($fixtures);
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getAdminJsonFixtures(): array
    {
        $fixtures = $this->getJsonFixtures();

        return $this->removeProtectedAdminJsonData($fixtures);
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getChannelJsonFixtures(): array
    {
        $fixtures = $this->getJsonFixtures();

        return $this->removeProtectedChannelJsonData($fixtures);
    }

    /**
     * @return array<string, mixed>
     */
    abstract protected function getJsonApiFixtures(string $baseUrl): array;

    /**
     * @return array<string, mixed>
     */
    abstract protected function getJsonFixtures(): array;

    /**
     * @param array<string, mixed> $fixtures
     *
     * @return array<string, mixed>
     */
    protected function removeProtectedChannelJsonApiData(array $fixtures): array
    {
        return $fixtures;
    }

    /**
     * @param array<string, mixed> $fixtures
     *
     * @return array<string, mixed>
     */
    protected function removeProtectedAdminJsonApiData(array $fixtures): array
    {
        return $fixtures;
    }

    /**
     * @param array<int|string, mixed> $fixtures
     *
     * @return array<int|string, mixed>
     */
    protected function removeProtectedChannelJsonData(array $fixtures): array
    {
        return $fixtures;
    }

    /**
     * @param array<int|string, mixed> $fixtures
     *
     * @return array<int|string, mixed>
     */
    protected function removeProtectedAdminJsonData(array $fixtures): array
    {
        return $fixtures;
    }
}
