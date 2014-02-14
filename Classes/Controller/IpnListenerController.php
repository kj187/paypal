<?php
namespace Aijko\Paypal\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 AIJKO GmbH <info@aijko.de
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Sofortige Zahlungsbestätigung (IPN)
 * https://developer.paypal.com/webapps/developer/docs/classic/ipn/integration-guide/IPNIntro/
 *
 * @author Julian Kleinhans <julian.kleinhans@aijko.de>
 * @copyright Copyright belongs to the respective authors
 * @package Aijko\Paypal
 */
class IpnListenerController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * @var integer
	 */
	protected $timeIdentifier;

	/**
	 * @var \IpnListener
	 */
	protected $ipnListener;

	/**
	 * @var \Aijko\Paypal\Domain\Repository\OrderRepository
	 */
	protected $orderRepository;

	/**
	 * @return void
	 * @throws \Exception
	 */
	public function receiveAction() {
		$this->timeIdentifier = date('YmdHis');
		$this->orderRepository = $this->objectManager->get('Aijko\Paypal\Domain\Repository\OrderRepository');
		$this->ipnListener = $this->objectManager->get('IpnListener');

		// FAKE
		#$_POST = (array)json_decode('{"mc_gross":"5.00","protection_eligibility":"Eligible","address_status":"unconfirmed","item_number1":"","payer_id":"JJX73SL2BPE5N","tax":"0.00","address_street":"ESpachstr. 1","payment_date":"05:49:01 Feb 14, 2014 PST","payment_status":"Completed","charset":"windows-1252","address_zip":"79111","mc_shipping":"0.00","mc_handling":"0.00","first_name":"Firstname","mc_fee":"0.45","address_country_code":"DE","address_name":"Firstname Lastname","notify_version":"3.7","custom":"85d043117eef0765915574b0e69074a3f319c62f","payer_status":"unverified","business":"sp_merchant@aijko.de","address_country":"Germany","num_cart_items":"1","mc_handling1":"0.00","address_city":"Freiburg","verify_sign":"AiPC9BjkCyDFQXbSkoZcgqH3hpacAfCT52esb-3zWiomFbk.I4PFwfpN","payer_email":"sp_buyer@aijko.de","mc_shipping1":"0.00","tax1":"0.00","txn_id":"71V23571AK8904132","payment_type":"instant","last_name":"Lastname","address_state":"Empty","item_name1":"EFX - Uploader","receiver_email":"sp_merchant@aijko.de","payment_fee":"","quantity1":"1","receiver_id":"94SC9AKRREUUY","txn_type":"cart","mc_gross_1":"5.00","mc_currency":"EUR","residence_country":"DE","test_ipn":"1","transaction_subject":"85d043117eef0765915574b0e69074a3f319c62f","payment_gross":"","ipn_track_id":"ef20d9dc392e3"}');
		#$_SERVER['REQUEST_METHOD'] = 'POST';

		$identifier = $_POST['txn_id'];
		\Aijko\SharepointConnector\Utility\Logger::info('Paypal.Raw.Response', array($_POST));

		if ($this->settings['context']['sandbox']) {
			$this->ipnListener->use_sandbox = TRUE;
		}

		try {
			$this->ipnListener->requirePostMethod();
			$verified = $this->ipnListener->processIpn();

			if ($verified) { // The processIpn() method returned true if the IPN was "VERIFIED" and false if it was "INVALID".
				if ('Completed' == $_POST['payment_status']) {
					if (!$this->orderRepository->findOneByTxnid($identifier)) { // Check that $_POST['txn_id'] has not been previously processed
						if ($this->settings['seller']['email'] == $_POST['receiver_email']) { // Check that $_POST['receiver_email'] is your Primary PayPal email

							$orderObject = $this->objectManager->get('Aijko\\Paypal\\Domain\\Model\\Order');
							$orderObject->setTxnid($identifier);
							$orderObject->setIdentifier($timeIdentifier . '_' . $identifier);
							$orderObject->setResponse($this->ipnListener->getTextReport());
							$this->orderRepository->add($orderObject);

							// clean session hash
							#$GLOBALS['TYPO3_DB']->exec_DELETEquery('fe_session_data', 'hash = "' . mysql_real_escape_string($_POST['custom']) . '"');

							$subject = 'IPN OK [' . $this->timeIdentifier . '_' . $identifier . ']';
							$this->sendNotificationAndLog($subject, $subject);

							// TODO SIGNAL SLOT  + beim signalslot in die powermail mail schreiben (SignalSlot)
							// SignalSlot zu sharepoint_shop -> übergabe der Daten an Sharepoint

						} else {
							throw new \Aijko\Paypal\Exception('receiver_email is not equals ' . $this->settings['seller']['email'] . ' [' . $this->timeIdentifier . '_' . $identifier . ']', 1392387854);
						}
					} else {
						throw new \Aijko\Paypal\Exception('IPN txn_id has been previously processed [' . $this->timeIdentifier . '_' . $identifier . ']', 1392387855);
					}
				} else {
					throw new \Aijko\Paypal\Exception('IPN payment_status is not completed [' . $this->timeIdentifier . '_' . $identifier . ']', 1392387856);
				}
			} else {
				$content = 'An Invalid IPN *may* be caused by a fraudulent transaction attempt. Its a good idea to have a developer or sys admin manually investigate any invalid IPN.';
				throw new \Aijko\Paypal\Exception('Invalid IPN [' . $this->timeIdentifier . '_' . $identifier . ']', 1392387857);
			}
		} catch (\Aijko\Paypal\Exception $e) {
			if (!$content) $content = $e->getMessage();
			$this->sendNotificationAndLog($e->getMessage(), $content, 'error');
			die($e->getMessage());
		} catch (\Exception $e) {
			$this->sendNotificationAndLog('IPN Exception [' . $this->timeIdentifier . '_' . $identifier . ']', $e->getMessage(), 'error');
			die($e->getMessage());
		}

		// TODO redirect
		return "OK";
	}

	/**
	 * @param string $subject
	 * @param string $content
	 * @param string $loggerMethodName
	 * @throws \Exception
	 */
	protected function sendNotificationAndLog($subject, $content, $loggerMethodName = 'info') {
		\Aijko\Paypal\Service\Notification::sendNotification(
			array('email' => $this->settings['notification']['from']['email'], 'name' => $this->settings['notification']['from']['name']),
			array('email' => $this->settings['notification']['to']['email'], 'name' => $this->settings['notification']['to']['name']),
			$subject,
			$content . "\n\n" . $this->ipnListener->getTextReport()
		);

		\Aijko\SharepointConnector\Utility\Logger::$loggerMethodName($subject, array($content . $this->ipnListener->getTextReport()));
	}

}

?>