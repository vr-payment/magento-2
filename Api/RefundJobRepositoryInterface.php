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
namespace VRPayment\Payment\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use VRPayment\Payment\Api\Data\RefundJobInterface;

/**
 * Refund job CRUD interface.
 *
 * @api
 */
interface RefundJobRepositoryInterface
{

    /**
     * Create refund job
     *
     * @param RefundJobInterface $object
     * @return RefundJobInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(RefundJobInterface $object);

    /**
     * Get job about refund job by entity ID
     *
     * @param int $entityId
     * @return RefundJobInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get($entityId);

    /**
     * Get job about refund job by order ID
     *
     * @param int $orderId
     * @return RefundJobInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getByOrderId($orderId);

    /**
     * Get job about refund job by external ID
     *
     * @param string $externalId
     * @return RefundJobInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getByExternalId($externalId);

    /**
     * Retrieve refund jobs matching the specified criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return \VRPayment\Payment\Api\Data\RefundJobSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * Delete refund job
     *
     * @param RefundJobInterface $object
     * @return bool Will returned True if deleted
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function delete(RefundJobInterface $object);

    /**
     * Delete refund job by identifier
     *
     * @param string $entityId
     * @return bool Will returned True if deleted
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\StateException
     */
    public function deleteByIdentifier($entityId);
}