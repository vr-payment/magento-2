<?php
/**
 * VRPayment Magento 2
 *
 * This Magento 2 extension enables to process payments with VRPayment (https://www.vr-payment.de).
 *
 * @package VRPayment_Payment
 * @author VR Payment GmbH (https://www.vr-payment.de)
 * @license http://www.apache.org/licenses/LICENSE-2.0  Apache Software License (ASL 2.0)

 */
namespace VRPayment\Payment\Api;

use VRPayment\Payment\Api\Data\TokenInfoInterface;

/**
 * Token info management interface.
 *
 * @api
 */
interface TokenInfoManagementInterface
{

    /**
     * Fetches the token version's latest state from VRPayment and updates the stored information.
     *
     * @param int $spaceId
     * @param int $tokenVersionId
     * @return void
     */
    public function updateTokenVersion($spaceId, $tokenVersionId);

    /**
     * Fetches the token's latest state from VRPayment and updates the stored information.
     *
     * @param int $spaceId
     * @param int $tokenId
     * @return void
     */
    public function updateToken($spaceId, $tokenId);

    /**
     * Deletes the token on VRPayment.
     *
     * @param TokenInfoInterface $token
     * @return void
     */
    public function deleteToken(TokenInfoInterface $token);
}