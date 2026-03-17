<?php

declare(strict_types=1);

namespace VRPayment\Payment\Model\CoreWebhook\PaymentMethodConfiguration;

use VRPayment\Payment\Api\PaymentMethodConfigurationManagementInterface;
use VRPayment\PluginCore\Log\LoggerInterface;
use VRPayment\PluginCore\Webhook\Command\WebhookCommand;
use VRPayment\PluginCore\Webhook\WebhookContext;

class SynchronizeCommand extends WebhookCommand
{

    /**
     *
     * @param WebhookContext $context
     * @param LoggerInterface $logger
     * @param PaymentMethodConfigurationManagementInterface $management
     */
    public function __construct(
        WebhookContext $context,
        LoggerInterface $logger,
        private readonly PaymentMethodConfigurationManagementInterface $management
    ) {
        parent::__construct($context, $logger);
    }

    /**
     * Execute synchronize command for the current webhook context.
     *
     * @return mixed
     */
    public function execute(): mixed
    {
        $this->logger->info('Running SynchronizeCommand');

        $this->management->synchronize();

        $this->logger->debug('Command Synchronize for PaymentMethodConfiguration completed.');

        return null;
    }
}
