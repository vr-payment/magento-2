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
namespace VRPayment\Payment\Model\ResourceModel\TokenInfo;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use VRPayment\Payment\Model\TokenInfo;
use VRPayment\Payment\Model\ResourceModel\TokenInfo as ResourceModel;

/**
 * Token info resource collection.
 */
class Collection extends AbstractCollection
{

    /**
     *
     * @var string
     */
    protected $_idFieldName = 'entity_id';

    /**
     * Event prefix
     *
     * @var string
     */
    protected $_eventPrefix = 'vrpayment_payment_token_info_resource_collection';

    /**
     * Event object
     *
     * @var string
     */
    protected $_eventObject = 'info_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(TokenInfo::class, ResourceModel::class);
    }

    /**
     * Filters the collection by space.
     *
     * @param int $spaceId
     * @return $this
     */
    public function addSpaceFilter($spaceId)
    {
        $this->addFieldToFilter('main_table.space_id', $spaceId);
        return $this;
    }

    /**
     * Filter the collection by customer ID.
     *
     * @param int $customerId
     * @return $this
     */
    public function addCustomerIdFilter($customerId)
    {
        $this->addFieldToFilter('main_table.customer_id', $customerId);
        return $this;
    }

    /**
     * Reset sort order
     *
     * @return $this
     */
    public function resetSortOrder()
    {
        $this->getSelect()->reset(\Magento\Framework\DB\Select::ORDER);
        return $this;
    }
}