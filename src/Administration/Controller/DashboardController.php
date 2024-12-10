<?php declare(strict_types=1);

namespace Cicada\Administration\Controller;

use Cicada\Core\Framework\Log\Package;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Route(defaults: ['_routeScope' => ['administration']])]
#[Package('administration')]
class DashboardController extends AbstractController
{
    public function __construct()
    {
    }
}
