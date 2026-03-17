<?php

declare(strict_types=1);

namespace VRPayment\Payment\Model\CoreWebhook\PaymentMethodConfiguration;

use VRPayment\Payment\Api\PaymentMethodConfigurationManagementInterface;
use VRPayment\PluginCore\Log\LoggerInterface;
use VRPayment\PluginCore\Webhook\Command\WebhookCommandInterface;
use VRPayment\PluginCore\Webhook\Listener\WebhookListenerInterface;
use VRPayment\PluginCore\Webhook\WebhookContext;

class SynchronizeListener implements WebhookListenerInterface
{

    /**
     *
     * @param LoggerInterface $logger
     * @param PaymentMethodConfigurationManagementInterface $management
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly PaymentMethodConfigurationManagementInterface $management
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
        return new SynchronizeCommand($context, $this->logger, $this->management);
    }
}
