<?php declare(strict_types=1);

namespace Cicada\Core\Content\Cms\DataAbstractionLayer\FieldSerializer;

use Cicada\Core\Content\Cms\DataResolver\FieldConfig;
use Cicada\Core\Framework\DataAbstractionLayer\Field\Field;
use Cicada\Core\Framework\DataAbstractionLayer\FieldSerializer\JsonFieldSerializer;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\NotBlank;

#[Package('frontend')]
class SlotConfigFieldSerializer extends JsonFieldSerializer
{
    protected function getConstraints(Field $field): array
    {
        return [
            new All([
                'constraints' => new Collection([
                    'allowExtraFields' => false,
                    'allowMissingFields' => false,
                    'fields' => [
                        'source' => [
                            new Choice([
                                'choices' => [
                                    FieldConfig::SOURCE_STATIC,
                                    FieldConfig::SOURCE_MAPPED,
                                    FieldConfig::SOURCE_PRODUCT_STREAM,
                                    FieldConfig::SOURCE_DEFAULT,
                                ],
                            ]),
                            new NotBlank(),
                        ],
                        'value' => [],
                    ],
                ]),
            ]),
        ];
    }
}