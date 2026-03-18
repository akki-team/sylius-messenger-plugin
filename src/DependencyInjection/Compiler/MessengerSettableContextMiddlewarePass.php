<?php
declare(strict_types=1);


namespace Akki\SyliusMessengerPlugin\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class MessengerSettableContextMiddlewarePass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds('messenger.bus') as $busId => $tags) {
            if (true === $container->hasParameter($busMiddlewareParameter = $busId . '.middleware')) {

                $middlewares = $container->getParameter($busMiddlewareParameter);
                $middlewaresIds = array_map(fn($middleware) => $middleware['id'], $middlewares);
                $index = array_search('failed_message_processing_middleware', $middlewaresIds);

                if (false === $index) {
                    continue;
                }

                $middlewares = array_merge(array_slice($middlewares, 0, $index + 1), [
                    [
                        'id' => 'akki.sylius_messenger.set_channel_context_middleware',
                        'arguments' => [
                            new Reference('sylius.repository.channel'),
                            new Reference('akki_sylius_settable_channel_plugin.context.settable_channel_context'),
                            new Reference('router.request_context')
                        ]
                    ],
                    [
                        'id' => 'akki.sylius_messenger.set_locale_context_middleware',
                        'arguments' => [new Reference('akki_sylius_settable_locale_plugin.context.settable_locale_context')]
                    ]
                ], array_slice($middlewares, $index + 1));

                $container->setParameter($busMiddlewareParameter, $middlewares);
            }
        }
    }

}
