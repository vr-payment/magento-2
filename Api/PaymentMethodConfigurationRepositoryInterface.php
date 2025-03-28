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
use VRPayment\Payment\Api\Data\PaymentMethodConfigurationInterface;

/**
 * Payment method configuration CRUD interface.
 *
 * @api
 */
interface PaymentMethodConfigurationRepositoryInterface
{

    /**
     * Create payment method configuration
     *
     * @param PaymentMethodConfigurationInterface $object
     * @return PaymentMethodConfigurationInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(PaymentMethodConfigurationInterface $object);

    /**
     * Get info about payment method configuration by entity ID
     *
     * @param int $entityId
     * @return PaymentMethodConfigurationInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get($entityId);

    /**
     * Get info about payment method configuration by configuration ID
     *
     * @param int $spaceId
     * @param int $configurationId
     * @return PaymentMethodConfigurationInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getByConfigurationId($spaceId, $configurationId);

    /**
     * Retrieve payment method configurations matching the specified criteria.
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return \VRPayment\Payment\Api\Data\PaymentMethodConfigurationSearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * Delete payment method configuration
     *
     * @param PaymentMethodConfigurationInterface $object
     * @return bool Will returned True if deleted
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(PaymentMethodConfigurationInterface $object);

    /**
     * Delete payment method configuration by identifier
     *
     * @param string $entityId
     * @return bool Will returned True if deleted
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteByIdentifier($entityId);
}