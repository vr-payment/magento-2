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
namespace VRPayment\Payment\Model\ResourceModel;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Transaction Info Resource Model
 */
class TransactionInfo extends AbstractDb
{

    /**
     * Event prefix
     *
     * @var string
     */
    protected $_eventPrefix = 'vrpayment_payment_transaction_info_resource';

    /**
     * Serializable fields
     *
     * @var array<string, mixed>
     */
    protected $_serializableFields = [
        'failure_reason' => [
            null,
            null
        ],
        'labels' => [
            null,
            null
        ]
    ];

    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('vrpayment_payment_transaction_info', 'entity_id');
    }

    /**
     * Load the transaction info by space and transaction.
     *
     * @param AbstractModel $object
     * @param int $spaceId
     * @param int $transactionId
     * @return $this
     */
    public function loadByTransaction(AbstractModel $object, $spaceId, $transactionId)
    {
        $connection = $this->getConnection();
        if ($connection) {
            $select = $connection->select()
                ->from($this->getMainTable())
                ->where('space_id=:space_id')
                ->where('transaction_id=:transaction_id');
            $binds = [
                'space_id' => $spaceId,
                'transaction_id' => $transactionId
            ];
            $data = $connection->fetchRow($select, $binds);
            if ($data) {
                $object->setData($data);
            }
        }

        $this->unserializeFields($object);
        $this->_afterLoad($object);
        return $this;
    }
}