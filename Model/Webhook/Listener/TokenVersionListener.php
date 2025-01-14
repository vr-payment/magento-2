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
namespace VRPayment\Payment\Model\Webhook\Listener;

use VRPayment\Payment\Api\TokenInfoManagementInterface;
use VRPayment\Payment\Model\Webhook\ListenerInterface;
use VRPayment\Payment\Model\Webhook\Request;

/**
 * Webhook listener to handle token versions.
 */
class TokenVersionListener implements ListenerInterface
{

    /**
     *
     * @var TokenInfoManagementInterface
     */
    private $tokenInfoManagement;

    /**
     *
     * @param TokenInfoManagementInterface $tokenInfoManagement
     */
    public function __construct(TokenInfoManagementInterface $tokenInfoManagement)
    {
        $this->tokenInfoManagement = $tokenInfoManagement;
    }

    public function execute(Request $request)
    {
        $this->tokenInfoManagement->updateTokenVersion($request->getSpaceId(), $request->getEntityId());
    }
}