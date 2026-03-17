<?php

declare(strict_types=1);

namespace VRPayment\Payment\Model\CoreWebhook\TokenVersion;

use VRPayment\Payment\Api\TokenInfoManagementInterface;
use VRPayment\PluginCore\Log\LoggerInterface;
use VRPayment\PluginCore\Webhook\Command\WebhookCommand;
use VRPayment\PluginCore\Webhook\WebhookContext;

class UpdateTokenVersionCommand extends WebhookCommand
{

    /**
     *
     * @param WebhookContext $context
     * @param LoggerInterface $logger
     * @param TokenInfoManagementInterface $tokenInfoManagement
     */
    public function __construct(
        WebhookContext $context,
        LoggerInterface $logger,
        private readonly TokenInfoManagementInterface $tokenInfoManagement,
    ) {
        parent::__construct($context, $logger);
    }

    /**
     * Execute update token version command for the current webhook context.
     *
     * @return mixed
     */
    public function execute(): mixed
    {
        $this->logger->info(
            sprintf(
                'Running UpdateTokenVersionCommand for entity ID: %d',
                $this->context->entityId
            )
        );

        $spaceId = $this->context->spaceId;
        $tokenVersionId = $this->context->entityId;

        $this->tokenInfoManagement->updateTokenVersion($spaceId, $tokenVersionId);

        $this->logger->debug(
            sprintf(
                'Command UpdateTokenVersion for entity TokenVersion/%d completed.',
                $this->context->entityId
            )
        );
        return null;
    }
}
