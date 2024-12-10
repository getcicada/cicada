<?php declare(strict_types=1);

namespace Cicada\Core\Framework\DataAbstractionLayer\Dbal\Exception;

use Cicada\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException;
use Cicada\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\Response;

#[Package('core')]
class ParentAssociationCanNotBeFetched extends DataAbstractionLayerException
{
    public function __construct()
    {
        parent::__construct(
            Response::HTTP_BAD_REQUEST,
            'FRAMEWORK__PARENT_ASSOCIATION_CAN_NOT_BE_FETCHED',
            'It is not possible to read the parent association directly. Please read the parents via a separate call over the repository'
        );
    }
}