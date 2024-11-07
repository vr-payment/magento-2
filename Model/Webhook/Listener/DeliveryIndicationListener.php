<?php
/**
 * VR payment Magento 2
 *
 * This Magento 2 extension enables to process payments with VR payment (https://www.vr-payment.de).
 *
 * @package VRPayment_Payment
 * @author VR Payment GmbH (https://www.vr-payment.de)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)

 */
namespace VRPayment\Payment\Model\Webhook\Listener;

use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResourceModel;
use Psr\Log\LoggerInterface;
use VRPayment\Payment\Api\TransactionInfoRepositoryInterface;
use VRPayment\Payment\Model\ApiClient;
use VRPayment\Payment\Model\Webhook\Request;
use VRPayment\Sdk\Service\DeliveryIndicationService;

/**
 * Webhook listener to handle delivery indications.
 */
class DeliveryIndicationListener extends AbstractOrderRelatedListener
{

    /**
     *
     * @var ApiClient
     */
    private $apiClient;

    /**
     *
     * @param ResourceConnection $resource
     * @param LoggerInterface $logger
     * @param OrderFactory $orderFactory
     * @param OrderResourceModel $orderResourceModel
     * @param CommandPoolInterface $commandPool
     * @param TransactionInfoRepositoryInterface $transactionInfoRepository
     * @param ApiClient $apiClient
     */
    public function __construct(ResourceConnection $resource, LoggerInterface $logger, OrderFactory $orderFactory,
        OrderResourceModel $orderResourceModel, CommandPoolInterface $commandPool,
        TransactionInfoRepositoryInterface $transactionInfoRepository, ApiClient $apiClient)
    {
        parent::__construct($resource, $logger, $orderFactory, $orderResourceModel, $commandPool,
            $transactionInfoRepository);
        $this->apiClient = $apiClient;
    }

    /**
     * Loads the delivery indication for the webhook request.
     *
     * @param Request $request
     * @return \VRPayment\Sdk\Model\DeliveryIndication
     */
    protected function loadEntity(Request $request)
    {
        return $this->apiClient->getService(DeliveryIndicationService::class)->read($request->getSpaceId(),
            $request->getEntityId());
    }

    /**
     * Gets the transaction's ID.
     *
     * @param \VRPayment\Sdk\Model\DeliveryIndication $entity
     * @return int
     */
    protected function getTransactionId($entity)
    {
        return $entity->getLinkedTransaction();
    }
}