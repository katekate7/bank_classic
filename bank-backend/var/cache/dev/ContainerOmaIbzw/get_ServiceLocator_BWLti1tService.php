<?php

namespace ContainerOmaIbzw;

use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

/**
 * @internal This class has been auto-generated by the Symfony Dependency Injection Component.
 */
class get_ServiceLocator_BWLti1tService extends App_KernelDevDebugContainer
{
    /**
     * Gets the private '.service_locator.BWLti1t' shared service.
     *
     * @return \Symfony\Component\DependencyInjection\ServiceLocator
     */
    public static function do($container, $lazyLoad = true)
    {
        return $container->privates['.service_locator.BWLti1t'] = new \Symfony\Component\DependencyInjection\Argument\ServiceLocator($container->getService, [
            'expense' => ['privates', '.errored..service_locator.BWLti1t.App\\Entity\\Expense', NULL, 'Cannot autowire service ".service_locator.BWLti1t": it references class "App\\Entity\\Expense" but no such service exists.'],
        ], [
            'expense' => 'App\\Entity\\Expense',
        ]);
    }
}
