<?php

namespace Athenea\MonologBundle;

use Athenea\MonologBundle\DependencyInjection\AtheneaMonologExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class AtheneaMonologBundle extends AbstractBundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new AtheneaMonologExtension();
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
    }
}