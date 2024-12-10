<?php declare(strict_types=1);

namespace Cicada\Frontend\Theme;

use Cicada\Core\Framework\Log\Package;
use Cicada\Frontend\Theme\Exception\InvalidThemeConfigException;

#[Package('frontend')]
class ThemeConfigFieldFactory
{
    public function create(string $name, array $configFieldArray): ThemeConfigField
    {
        $configField = new ThemeConfigField();
        $configField->setName($name);

        foreach ($configFieldArray as $key => $value) {
            $setter = 'set' . ucfirst($key);
            if (!method_exists($configField, $setter)) {
                throw new InvalidThemeConfigException($key);
            }
            $configField->$setter($value); // @phpstan-ignore-line
        }

        return $configField;
    }
}
