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
namespace VRPayment\Payment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Module\Manager as ModuleManager;
use Magento\Sales\Model\Order\Creditmemo;
use VRPayment\Payment\Helper\Data as Helper;
use VRPayment\Sdk\Model\LineItemReductionCreate;

/**
 * Observer to collect the line item reductions for the fooman surcharges.
 */
class CollectFoomanSurchargeLineItemReductions implements ObserverInterface
{

    /**
     *
     * @var ModuleManager
     */
    private $moduleManager;

    /**
     *
     * @var Helper
     */
    private $helper;

    /**
     *
     * @param ModuleManager $moduleManager
     * @param Helper $helper
     */
    public function __construct(ModuleManager $moduleManager, Helper $helper)
    {
        $this->moduleManager = $moduleManager;
        $this->helper = $helper;
    }

    public function execute(Observer $observer)
    {
        /* @var Creditmemo $creditmemo */
        $creditmemo = $observer->getCreditmemo();
        /* @var \VRPayment\Sdk\Model\LineItem[] $baseLineItems */
        $baseLineItems = $observer->getData('baseLineItems');
        $transport = $observer->getTransport();

        if ($this->moduleManager->isEnabled('Fooman_Surcharge')) {
            $transport->setData('items',
                \array_merge($transport->getData('items'), $this->convertFoomanSurcharges($creditmemo, $baseLineItems)));
        }
    }

    /**
     * Converts the fooman surcharge lines to line item reductions.
     *
     * @param Creditmemo $creditmemo
     * @param \VRPayment\Sdk\Model\LineItem[] $baseLineItems
     * @return LineItemReductionCreate[]
     */
    protected function convertFoomanSurcharges(Creditmemo $creditmemo, $baseLineItems)
    {
        if (! $creditmemo->getExtensionAttributes()) {
            return [];
        }

        if (! $creditmemo->getExtensionAttributes()->getFoomanTotalGroup()) {
            return [];
        }

        $baseLineItemMap = [];
        foreach ($baseLineItems as $lineItem) {
            $baseLineItemMap[$lineItem->getUniqueId()] = $lineItem;
        }

        $items = [];
        foreach ($creditmemo->getExtensionAttributes()
            ->getFoomanTotalGroup()
            ->getItems() as $item) {
            if ($item->getAmount() <= 0) {
                continue;
            }
            $items[] = $this->createSurchargeReduction($creditmemo, $item->getTypeId(),
                $item->getAmount() + $item->getTaxAmount(),
                isset($baseLineItemMap['fooman_surcharge_' . $item->getTypeId()]) ? $baseLineItemMap['fooman_surcharge_' .
                $item->getTypeId()] : null);
        }
        return $items;
    }

    /**
     *
     * @param Creditmemo $creditmemo
     * @param string $code
     * @param float $amount
     * @param mixed $baseLineItem
     * @return LineItemReductionCreate[]
     */
    private function createSurchargeReduction(Creditmemo $creditmemo, $code, $amount, $baseLineItem)
    {
        $reduction = new LineItemReductionCreate();
        $reduction->setLineItemUniqueId('fooman_surcharge_' . $code);
        if ($baseLineItem != null && $baseLineItem->getAmountIncludingTax() == $amount) {
            $reduction->setQuantityReduction(1);
            $reduction->setUnitPriceReduction(0);
        } else {
            $reduction->setQuantityReduction(0);
            $reduction->setUnitPriceReduction($this->helper->roundAmount($amount, $creditmemo->getOrderCurrencyCode()));
        }
        return $reduction;
    }
}