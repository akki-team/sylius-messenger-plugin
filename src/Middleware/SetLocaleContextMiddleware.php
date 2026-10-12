<?php
declare(strict_types=1);


namespace Akki\SyliusMessengerPlugin\Middleware;

use Akki\SyliusMessengerPlugin\Stamp\LocaleContextStamp;
use Akki\SyliusSettableLocalePlugin\Context\SettableLocaleContextInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

final class SetLocaleContextMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly SettableLocaleContextInterface $settableLocaleContext
    )
    {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $localeContextStamp = $envelope->last(LocaleContextStamp::class);

        if (null !== $localeContextStamp) {
            $this->settableLocaleContext->setLocaleCode($localeContextStamp->localeCode);
        }

        // Reset even when the handler throws: otherwise the value stays set in a long-running
        // worker and leaks into the next messages it handles.
        try {
            return $stack->next()->handle($envelope, $stack);
        } finally {
            if (null !== $localeContextStamp) {
                $this->settableLocaleContext->reset();
            }
        }
    }

}
