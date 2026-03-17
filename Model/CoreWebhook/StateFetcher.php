<?php

declare(strict_types=1);

namespace VRPayment\Payment\Model\CoreWebhook;

use VRPayment\PluginCore\Webhook\DefaultStateFetcher;
use VRPayment\Sdk\Service\WebhookEncryptionService;
use VRPayment\Sdk\Service\TransactionService;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Magento-specific wrapper for the DefaultStateFetcher.
 * Its only job is to get the spaceId from Magento's configuration.
 */
class StateFetcher extends DefaultStateFetcher
{

}
