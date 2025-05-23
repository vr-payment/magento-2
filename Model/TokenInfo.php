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
namespace VRPayment\Payment\Model;

use VRPayment\Payment\Api\Data\TokenInfoInterface;
use VRPayment\Payment\Model\ResourceModel\TokenInfo as ResourceModel;

/**
 * Token info model.
 */
class TokenInfo extends \Magento\Framework\Model\AbstractModel implements TokenInfoInterface
{

    /**
     * Event prefix
     *
     * @var string
     */
    protected $_eventPrefix = 'vrpayment_payment_token_info';

    /**
     * Event object
     *
     * @var string
     */
    protected $_eventObject = 'info';

    /**
     * Initialize model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel::class);
    }

    public function getConnectorId()
    {
        return $this->getData(TokenInfoInterface::CONNECTOR_ID);
    }

    public function getCreatedAt()
    {
        return $this->getData(TokenInfoInterface::CREATED_AT);
    }

    public function getCustomerId()
    {
        return $this->getData(TokenInfoInterface::CUSTOMER_ID);
    }

    public function getName()
    {
        return $this->getData(TokenInfoInterface::NAME);
    }

    public function getPaymentMethodId()
    {
        return $this->getData(TokenInfoInterface::PAYMENT_METHOD_ID);
    }

    public function getSpaceId()
    {
        return $this->getData(TokenInfoInterface::SPACE_ID);
    }

    public function getState()
    {
        return $this->getData(TokenInfoInterface::STATE);
    }

    public function getTokenId()
    {
        return $this->getData(TokenInfoInterface::TOKEN_ID);
    }
}