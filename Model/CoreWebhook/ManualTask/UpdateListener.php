<?php

declare(strict_types=1);

namespace VRPayment\Payment\Model\CoreWebhook\ManualTask;

use VRPayment\Payment\Model\Service\ManualTaskService;
use VRPayment\PluginCore\Webhook\Command\WebhookCommandInterface;
use VRPayment\PluginCore\Webhook\Listener\WebhookListenerInterface;
use VRPayment\PluginCore\Webhook\WebhookContext;
use VRPayment\PluginCore\Log\LoggerInterface;

class UpdateListener implements WebhookListenerInterface
{

    /**
     *
     * @param LoggerInterface $logger
     * @param ManualTaskService $manualTaskService
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ManualTaskService $manualTaskService,
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
        return new UpdateCommand($this->logger, $context, $this->manualTaskService);
    }
}
