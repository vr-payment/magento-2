<?php
/**
 * VR Payment Magento 2
 *
 * This Magento 2 extension enables to process payments with VR Payment (https://www.vr-payment.de).
 *
 * @package VRPayment_Payment
 * @author VR Payment GmbH (https://www.vr-payment.de)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)

 */
namespace VRPayment\Payment\Model\Service;

use Magento\Framework\UrlInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use VRPayment\Payment\Model\CoreWebhook\RegistryConfigurer;
use VRPayment\Payment\Model\Settings\SettingsProvider;
use VRPayment\PluginCore\Webhook\WebhookProcessor;
use VRPayment\PluginCore\Webhook\WebhookService as PluginCoreWebhookService;

/**
 * Service to handle webhooks.
 */
class WebhookService
{

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var PluginCoreWebhookService
     */
    private $pluginCoreWebhookService;

    /**
     * @var RegistryConfigurer
     */
    private $registryConfigurer;

    /**
     * @var WebhookProcessor
     */
    private $webhookProcessor;

    /**
     * @var SettingsProvider
     */
    private $settingsProvider;

    /**
     * @param StoreManagerInterface $storeManager
     * @param PluginCoreWebhookService $pluginCoreWebhookService
     * @param RegistryConfigurer $registryConfigurer
     * @param WebhookProcessor $webhookProcessor
     * @param SettingsProvider $settingsProvider
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        PluginCoreWebhookService $pluginCoreWebhookService,
        RegistryConfigurer $registryConfigurer,
        WebhookProcessor $webhookProcessor,
        SettingsProvider $settingsProvider
    ) {
        $this->storeManager = $storeManager;
        $this->pluginCoreWebhookService = $pluginCoreWebhookService;
        $this->registryConfigurer = $registryConfigurer;
        $this->webhookProcessor = $webhookProcessor;
        $this->settingsProvider = $settingsProvider;
    }

    /**
     * Installs webhooks.
     *
     * Installs the necessary webhooks in VR Payment for every configured space,
     * using the base URL of the website where each space ID is configured.
     *
     * @return void
     */
    public function install()
    {
        $this->registryConfigurer->configure();
        $registry = $this->webhookProcessor->getListenerRegistry();

        foreach ($this->getWebhookTargets() as $target) {
            $this->pluginCoreWebhookService->synchronizeWebhooks(
                $target['spaceId'],
                $target['url'],
                'Magento 2',
                $registry
            );
        }
    }

    /**
     * Retrieves an array of spaceId and url pairs.
     *
     * Collects distinct (spaceId, url) pairs across all websites, so webhooks are
     * registered against the domain where the space ID is actually configured.
     *
     * @return array<int, array{spaceId:int, url:string}>
     */
    private function getWebhookTargets(): array
    {
        $targets = [];
        $seen = [];
        foreach ($this->storeManager->getWebsites() as $website) {
            $spaceId = $this->settingsProvider->getSpaceIdForWebsite((int)$website->getId());
            if ($spaceId === null) {
                continue;
            }
            $url = $this->getUrlForWebsite($website);
            $key = $spaceId . '|' . $url;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $targets[] = ['spaceId' => $spaceId, 'url' => $url];
        }
        return $targets;
    }

    /**
     * Gets the webhook endpoint URL for a specific website.
     *
     * @param WebsiteInterface $website
     * @return string
     */
    private function getUrlForWebsite(WebsiteInterface $website): string
    {
        $route = 'index.php/vrpayment_payment/webhook/index/';
        return $website->getDefaultStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB) . $route;
    }
}
