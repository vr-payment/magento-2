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
namespace VRPayment\Payment\Block\Adminhtml\Customer\Tab\Renderer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Framework\DataObject;
use VRPayment\PluginCore\Token\State as CoreTokenState;

/**
 * Block to render the state grid column of the token grid.
 */
class State extends AbstractRenderer
{
    /**
     * Render human-readable state label for the given row.
     *
     * @param \Magento\Framework\DataObject $row
     * @return \Magento\Framework\Phrase
     */
    public function render(DataObject $row)
    {
        switch ($row->getData($this->getColumn()
            ->getIndex())) {
            case CoreTokenState::ACTIVE->value:
                return \__('Active');
            case CoreTokenState::INACTIVE->value:
                return \__('Inactive');
            default:
                return \__('Unknown State');
        }
    }
}
