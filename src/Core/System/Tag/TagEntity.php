<?php declare(strict_types=1);

namespace Cicada\Core\System\Tag;

use Cicada\Core\Content\Category\CategoryCollection;
use Cicada\Core\Content\LandingPage\LandingPageCollection;
use Cicada\Core\Content\Media\MediaCollection;
use Cicada\Core\Framework\DataAbstractionLayer\Entity;
use Cicada\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Cicada\Core\Framework\Log\Package;

#[Package('core')]
class TagEntity extends Entity
{
    use EntityIdTrait;

    protected string $name;

    protected ?MediaCollection $media = null;

    protected ?CategoryCollection $categories = null;

    protected ?LandingPageCollection $landingPages = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }


    public function getMedia(): ?MediaCollection
    {
        return $this->media;
    }

    public function setMedia(MediaCollection $media): void
    {
        $this->media = $media;
    }

    public function getCategories(): ?CategoryCollection
    {
        return $this->categories;
    }

    public function setCategories(CategoryCollection $categories): void
    {
        $this->categories = $categories;
    }

    public function getLandingPages(): ?LandingPageCollection
    {
        return $this->landingPages;
    }

    public function setLandingPages(LandingPageCollection $landingPages): void
    {
        $this->landingPages = $landingPages;
    }
}
