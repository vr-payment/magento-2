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
namespace VRPayment\Payment\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use VRPayment\Payment\Api\Data\TransactionInfoInterface;

/**
 * Transaction info CRUD interface.
 *
 * @api
 */
interface TransactionInfoRepositoryInterface
{

    /**
     * Create transaction info
     *
     * @param TransactionInfoInterface $object
     * @return TransactionInfoInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(TransactionInfoInterface $object);

    /**
     * Get info about transaction info by entity ID
     *
     * @param int $entityId
     * @return TransactionInfoInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get($entityId);

    /**
     * Get info about transaction info by transaction ID
     *
     * @param int $spaceId
     * @param int $transactionId
     * @return TransactionInfoInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getByTransactionId($spaceId, $transactionId);

    /**
     * Get info about transaction info by order ID
     *
     * @param int $orderId
     * @return TransactionInfoInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getByOrderId($orderId);

    /**
     * Retrieve transaction infos matching the specified criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return \VRPayment\Payment\Api\Data\TransactionInfoSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * Delete transaction info
     *
     * @param TransactionInfoInterface $object
     * @return bool Will returned True if deleted
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function delete(TransactionInfoInterface $object);

    /**
     * Delete transaction info by identifier
     *
     * @param string $entityId
     * @return bool Will returned True if deleted
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function deleteByIdentifier($entityId);
}