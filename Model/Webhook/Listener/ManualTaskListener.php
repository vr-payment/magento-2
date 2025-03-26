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

use VRPayment\Payment\Model\Service\ManualTaskService;
use VRPayment\Payment\Model\Webhook\ListenerInterface;
use VRPayment\Payment\Model\Webhook\Request;

/**
 * Webhook listener to handle manual tasks.
 */
class ManualTaskListener implements ListenerInterface
{

    /**
     *
     * @var ManualTaskService
     */
    private $manualTaskService;

    /**
     *
     * @param ManualTaskService $manualTaskService
     */
    public function __construct(ManualTaskService $manualTaskService)
    {
        $this->manualTaskService = $manualTaskService;
    }

    public function execute(Request $request)
    {
        $this->manualTaskService->update();
    }
}