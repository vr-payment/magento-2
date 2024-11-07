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

use Magento\Sales\Api\Data\OrderInterface;

/**
 * Interface for VR payment order data.
 *
 * @api
 */
interface OrderRepositoryInterface
{

	/**
	 * Get order by Order Increment Id
	 *
	 * @param $incrementId
	 * @return OrderInterface|null
	 */
    public function getOrderByIncrementId($incrementId);

	/**
	 * Get order by Id
	 *
	 * @param string $id
	 * @return OrderInterface|null
	 */
    public function getOrderById($id);
}
