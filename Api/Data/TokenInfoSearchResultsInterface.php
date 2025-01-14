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
namespace VRPayment\Payment\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Interface for VRPay token info search results.
 *
 * @api
 */
interface TokenInfoSearchResultsInterface extends SearchResultsInterface
{

    /**
     * Get token infos list.
     *
     * @return \VRPayment\Payment\Api\Data\TokenInfoInterface[]
     */
    public function getItems();

    /**
     * Set token infos list.
     *
     * @param \VRPayment\Payment\Api\Data\TokenInfoInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}