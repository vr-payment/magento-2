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
namespace VRPayment\Payment\Model\Webhook\Listener;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Lock\LockManagerInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\ResourceModel\Order as OrderResourceModel;
use Psr\Log\LoggerInterface;
use VRPayment\Payment\Api\TransactionInfoManagementInterface;
use VRPayment\Payment\Api\TransactionInfoRepositoryInterface;
use VRPayment\Payment\Model\ApiClient;
use VRPayment\Payment\Model\Webhook\Request;
use VRPayment\Sdk\Service\TransactionService;

/**
 * Webhook listener to handle transactions.
 */
class TransactionListener extends AbstractOrderRelatedListener
{
    /**
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     *
     * @var TransactionInfoRepositoryInterface
     */
    private $transactionInfoRepository;

    /**
     *
     * @var TransactionInfoManagementInterface
     */
    private $transactionInfoManagement;

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
     * @param TransactionInfoManagementInterface $transactionInfoManagement
     * @param ApiClient $apiClient
     * @param LoggerInterface $logger
     * @param LockManagerInterface $lockManager
     */
    public function __construct(
        ResourceConnection $resource,
        LoggerInterface $logger, OrderFactory $orderFactory,
        OrderResourceModel $orderResourceModel,
        CommandPoolInterface $commandPool,
        TransactionInfoRepositoryInterface $transactionInfoRepository,
        TransactionInfoManagementInterface $transactionInfoManagement,
        ApiClient $apiClient,
        LockManagerInterface $lockManager
        ) {
        parent::__construct($resource, $logger, $orderFactory, $orderResourceModel, $commandPool,
            $transactionInfoRepository, $lockManager);
        $this->transactionInfoRepository = $transactionInfoRepository;
        $this->transactionInfoManagement = $transactionInfoManagement;
        $this->apiClient = $apiClient;
        $this->logger=$logger;
    }

    /**
     * Actually processes the order related webhook request.
     *
     * @param \VRPayment\Sdk\Model\Transaction $entity
     * @param Order $order
     * @return void
     */
    protected function process($entity, Order $order)
    {
        $this->logger->debug("TRANSACTION-LISTENER::process");
        $transactionInfo = $this->transactionInfoRepository->getByOrderId($order->getId());
        if ((string)$transactionInfo->getState() != $entity->getState()) {
            parent::process($entity, $order);
        }
        $this->transactionInfoManagement->update($entity, $order);
    }

    /**
     * Loads the transaction for the webhook request.
     *
     * @param Request $request
     * @return \VRPayment\Sdk\Model\Transaction
     */
    protected function loadEntity(Request $request)
    {
        return $this->apiClient->getService(TransactionService::class)->read($request->getSpaceId(),
            $request->getEntityId());
    }

    /**
     * Gets the transaction's ID.
     *
     * @param \VRPayment\Sdk\Model\Transaction $entity
     * @return int
     */
    protected function getTransactionId($entity)
    {
        return $entity->getId();
    }
}
