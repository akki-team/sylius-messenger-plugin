<?php
declare(strict_types=1);


namespace Akki\SyliusMessengerPlugin\Middleware;

use Akki\SyliusMessengerPlugin\Stamp\ChannelContextStamp;
use Akki\SyliusSettableChannelPlugin\Context\SettableChannelContextInterface;
use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Routing\RequestContext;

final class SetChannelContextMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ChannelRepositoryInterface      $channelRepository,
        private readonly SettableChannelContextInterface $settableChannelContext,
        private readonly RequestContext $requestContext
    )
    {
    }

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $channelContextStamp = $envelope->last(ChannelContextStamp::class);

        if (null !== $channelContextStamp) {
            $channel = $this->channelRepository->findOneByCode($channelContextStamp->channelCode);

            if (null === $channel) {
                throw new \RuntimeException(sprintf('Channel with code "%s" not found', $channelContextStamp->channelCode));
            }

            $this->settableChannelContext->setChannel($channel);
            $this->requestContext->setHost($channel->getHostname());
            $this->requestContext->setScheme('https');
        }

        $envelope = $stack->next()->handle($envelope, $stack);

        if (null !== $channelContextStamp) {
            $this->settableChannelContext->reset();
        }

        return $envelope;
    }

}
