<?php
/**
 * VRPay Magento 2
 *
 * This Magento 2 extension enables to process payments with VRPay (https://www.vr-payment.de).
 *
 * @package VRPayment_Payment
 * @author VR Payment GmbH (https://www.vr-payment.de)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)

 */
namespace VRPayment\Payment\Model\Service;

use Magento\Framework\UrlInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use VRPayment\Payment\Helper\Data as Helper;
use VRPayment\Payment\Model\ApiClient;
use VRPayment\Payment\Model\Webhook\Entity;
use VRPayment\Payment\Model\Webhook\ListenerPoolInterface;
use VRPayment\Payment\Model\Webhook\Request;
use VRPayment\Sdk\Model\CreationEntityState;
use VRPayment\Sdk\Model\DeliveryIndicationState;
use VRPayment\Sdk\Model\EntityQuery;
use VRPayment\Sdk\Model\EntityQueryFilter;
use VRPayment\Sdk\Model\EntityQueryFilterType;
use VRPayment\Sdk\Model\ManualTaskState;
use VRPayment\Sdk\Model\RefundState;
use VRPayment\Sdk\Model\TokenVersionState;
use VRPayment\Sdk\Model\TransactionCompletionState;
use VRPayment\Sdk\Model\TransactionInvoiceState;
use VRPayment\Sdk\Model\TransactionState;
use VRPayment\Sdk\Model\WebhookListenerCreate;
use VRPayment\Sdk\Model\WebhookUrl;
use VRPayment\Sdk\Model\WebhookUrlCreate;
use VRPayment\Sdk\Service\WebhookListenerService;
use VRPayment\Sdk\Service\WebhookUrlService;

/**
 * Service to handle webhooks.
 */
class WebhookService
{

    /**
     *
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     *
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     *
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     *
     * @var Helper
     */
    private $helper;

    /**
     *
     * @var ListenerPoolInterface
     */
    private $webhookListenerPool;

    /**
     *
     * @var ApiClient
     */
    private $apiClient;

    /**
     *
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlInterface $urlBuilder
     * @param Helper $helper
     * @param ListenerPoolInterface $webhookListenerPool
     * @param ApiClient $apiClient
     */
    public function __construct(StoreManagerInterface $storeManager, ScopeConfigInterface $scopeConfig,
        UrlInterface $urlBuilder, Helper $helper, ListenerPoolInterface $webhookListenerPool, ApiClient $apiClient)
    {
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->urlBuilder = $urlBuilder;
        $this->helper = $helper;
        $this->webhookListenerPool = $webhookListenerPool;
        $this->apiClient = $apiClient;
    }

    /**
     * Execute the webhook request.
     *
     * @param Request $request
     * @return void
     */
    public function execute(Request $request)
    {
        $this->webhookListenerPool->get(strtolower($request->getListenerEntityTechnicalName()))
            ->execute($request);
    }

    /**
     * Installs the necessary webhooks in VRPay.
     * @return void
     */
    public function install()
    {
        $spaceIds = [];
        foreach ($this->storeManager->getWebsites() as $website) {
            $spaceId = $this->scopeConfig->getValue('vrpayment_payment/general/space_id',
                ScopeInterface::SCOPE_WEBSITE, $website->getId());
            if ($spaceId && ! in_array($spaceId, $spaceIds)) {
                $webhookUrl = $this->getWebhookUrl($spaceId);
                if (! ($webhookUrl instanceof WebhookUrl)) {
                    $webhookUrl = $this->createWebhookUrl($spaceId);
                }

                $webhookListeners = $this->getWebhookListeners($spaceId, $webhookUrl);
                foreach ($this->getEntities() as $webhookEntity) {
                    if (! $this->isWebhookListenerExisting($webhookEntity, $webhookListeners)) {
                        $this->createWebhookListener($spaceId, $webhookEntity, $webhookUrl);
                    }
                }
            }
        }
    }

    /**
     * Gets whether a webhook listener already exists for the given entity.
     *
     * @param Entity $webhookEntity
     * @param \VRPayment\Sdk\Model\WebhookListener[] $webhookListeners
     * @return boolean
     */
    private function isWebhookListenerExisting(Entity $webhookEntity, array $webhookListeners)
    {
        foreach ($webhookListeners as $webhookListener) {
            if ($webhookListener->getEntity() == $webhookEntity->getId()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Creates a webhook listener.
     *
     * @param int $spaceId
     * @param Entity $webhookEntity
     * @param WebhookUrl $webhookUrl
     * @return \VRPayment\Sdk\Model\WebhookListener
     */
    private function createWebhookListener($spaceId, Entity $webhookEntity, WebhookUrl $webhookUrl)
    {
        $entity = new WebhookListenerCreate();
        $entity->setEntity($webhookEntity->getId());
        $entity->setEntityStates($webhookEntity->getStates());
        $entity->setName('Magento 2 ' . $webhookEntity->getName());
        $entity->setState(CreationEntityState::ACTIVE);
        $entity->setUrl($webhookUrl->getId());
        $entity->setNotifyEveryChange($webhookEntity->isNotifyEveryChange());
        return $this->apiClient->getService(WebhookListenerService::class)->create($spaceId, $entity);
    }

    /**
     * Gets the existing webhook listeners.
     *
     * @param int $spaceId
     * @param WebhookUrl $webhookUrl
     * @return \VRPayment\Sdk\Model\WebhookListener[]
     */
    private function getWebhookListeners($spaceId, WebhookUrl $webhookUrl)
    {
        $query = new EntityQuery();
        $filter = new EntityQueryFilter();
        $filter->setType(EntityQueryFilterType::_AND);
        $filter->setChildren(
            [
                $this->helper->createEntityFilter('state', CreationEntityState::ACTIVE),
                $this->helper->createEntityFilter('url.id', $webhookUrl->getId())
            ]);
        $query->setFilter($filter);
        return $this->apiClient->getService(WebhookListenerService::class)->search($spaceId, $query);
    }

    /**
     * Creates a webhook URL.
     *
     * @param int $spaceId
     * @return WebhookUrl
     */
    private function createWebhookUrl($spaceId)
    {
        $entity = new WebhookUrlCreate();
        $entity->setUrl($this->getUrl());
        $entity->setState(CreationEntityState::ACTIVE);
        $entity->setName('Magento 2');
        return $this->apiClient->getService(WebhookUrlService::class)->create($spaceId, $entity);
    }

    /**
     * Gets the existing webhook URL if existing.
     *
     * @param int $spaceId
     * @return WebhookUrl
     */
    private function getWebhookUrl($spaceId)
    {
        $query = new EntityQuery();
        $query->setNumberOfEntities(1);
        $filter = new EntityQueryFilter();
        $filter->setType(EntityQueryFilterType::_AND);
        $filter->setChildren(
            [
                $this->helper->createEntityFilter('state', CreationEntityState::ACTIVE),
                $this->helper->createEntityFilter('url', $this->getUrl())
            ]);
        $query->setFilter($filter);
        $result = $this->apiClient->getService(WebhookUrlService::class)->search($spaceId, $query);
        if (! empty($result)) {
            return \current($result);
        } else {
            return null;
        }
    }

    /**
     * Gets the webhook endpoint URL.
     *
     * @return string
     */
    private function getUrl()
    {
        return $this->urlBuilder->setScope($this->storeManager->getDefaultStoreView())
            ->getUrl('vrpayment_payment/webhook/index', [
            '_secure' => true,
            '_nosid' => true
        ]);
    }

    /**
     * Gets the webhook entities that are required.
     *
     * @return Entity[]
     */
    private function getEntities()
    {
        $listeners = [];

        $listeners[] = new Entity(1487165678181, 'Manual Task',
            [
                ManualTaskState::DONE,
                ManualTaskState::EXPIRED,
                ManualTaskState::OPEN
            ]);

        $listeners[] = new Entity(1472041857405, 'Payment Method Configuration',
            [
                CreationEntityState::ACTIVE,
                CreationEntityState::DELETED,
                CreationEntityState::DELETING,
                CreationEntityState::INACTIVE
            ], true);

        $listeners[] = new Entity(1472041829003, 'Transaction',
            [
                TransactionState::AUTHORIZED,
                TransactionState::DECLINE,
                TransactionState::FAILED,
                TransactionState::FULFILL,
                TransactionState::VOIDED,
                TransactionState::COMPLETED,
                TransactionState::PROCESSING,
                TransactionState::CONFIRMED
            ]);

        $listeners[] = new Entity(1472041819799, 'Delivery Indication',
            [
                DeliveryIndicationState::MANUAL_CHECK_REQUIRED
            ]);

        $listeners[] = new Entity(1472041831364, 'Transaction Completion', [
            TransactionCompletionState::FAILED
        ]);

        $listeners[] = new Entity(1472041816898, 'Transaction Invoice',
            [
                TransactionInvoiceState::NOT_APPLICABLE,
                TransactionInvoiceState::PAID
            ]);

        $listeners[] = new Entity(1472041839405, 'Refund', [
            RefundState::FAILED,
            RefundState::SUCCESSFUL
        ]);

        $listeners[] = new Entity(1472041806455, 'Token',
            [
                CreationEntityState::ACTIVE,
                CreationEntityState::INACTIVE,
                CreationEntityState::DELETING,
                CreationEntityState::DELETED
            ]);

        $listeners[] = new Entity(1472041811051, 'Token Version',
            [
                TokenVersionState::ACTIVE,
                TokenVersionState::OBSOLETE
            ]);

        return $listeners;
    }
}