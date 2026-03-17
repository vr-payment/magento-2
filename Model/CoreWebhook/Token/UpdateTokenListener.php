<?php

declare(strict_types=1);

namespace VRPayment\Payment\Model\CoreWebhook\Token;

use VRPayment\Payment\Api\TokenInfoManagementInterface;
use VRPayment\PluginCore\Log\LoggerInterface;
use VRPayment\PluginCore\Webhook\WebhookContext;
use VRPayment\PluginCore\Webhook\Command\WebhookCommandInterface;
use VRPayment\PluginCore\Webhook\Listener\WebhookListenerInterface;

class UpdateTokenListener implements WebhookListenerInterface
{
    /**
     *
     * @param LoggerInterface $logger
     * @param TokenInfoManagementInterface $tokenInfoManagement
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly TokenInfoManagementInterface $tokenInfoManagement,
    ) {
    }

    /**
     * Create webhook command for the given context.
     *
     * @param WebhookContext $context
     * @return WebhookCommandInterface
     */
    public function getCommand(WebhookContext $context): WebhookCommandInterface
    {
        return new UpdateTokenCommand($context, $this->logger, $this->tokenInfoManagement);
    }
}
