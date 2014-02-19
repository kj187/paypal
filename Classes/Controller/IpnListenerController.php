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
 * Instant Payment Notification (IPN)
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
	 * @var integer
	 */
	protected $txnIdentifier;

	/**
	 * @var \IpnListener
	 */
	protected $ipnListener;

	/**
	 * @var \Aijko\Paypal\Domain\Repository\OrderRepository
	 */
	protected $orderRepository;

	/**
	 * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
	 */
	protected $persistenceManager;

	/**
	 * @return void
	 * @throws \Exception
	 */
	public function receiveAction() {
		#$_SERVER['REQUEST_METHOD'] = 'POST';
		#$_POST = (array)json_decode('{"mc_gross":"5.00","protection_eligibility":"Eligible","address_status":"unconfirmed","item_number1":"","payer_id":"JJX73SL2BPE5N","tax":"0.00","address_street":"ESpachstr. 1","payment_date":"02:31:58 Feb 19, 2014 PST","payment_status":"Completed","charset":"windows-1252","address_zip":"79111","mc_shipping":"0.00","mc_handling":"0.00","first_name":"Firstname","mc_fee":"0.45","address_country_code":"DE","address_name":"Firstname Lastname","notify_version":"3.7","custom":"f2ff2b33043f13db295fd621049710b2ba738f4c","payer_status":"unverified","business":"sp_merchant@aijko.de","address_country":"Germany","num_cart_items":"1","mc_handling1":"0.00","address_city":"Freiburg","verify_sign":"AhgfD0syzV.maHmOZIBk-C.VWKzrAfXjOAMzKUJoaj54LK4.Y9d5aMjI","payer_email":"sp_buyer@aijko.de","mc_shipping1":"0.00","tax1":"0.00","txn_id":"5WA532952T182543A","payment_type":"instant","last_name":"Lastname","address_state":"Empty","item_name1":"EFX - Uploader","receiver_email":"sp_merchant@aijko.de","payment_fee":"","quantity1":"1","receiver_id":"94SC9AKRREUUY","txn_type":"cart","mc_gross_1":"5.00","mc_currency":"EUR","residence_country":"DE","test_ipn":"1","transaction_subject":"f2ff2b33043f13db295fd621049710b2ba738f4c","payment_gross":"","ipn_track_id":"5c737c81db99f"}');

		\Aijko\SharepointConnector\Utility\Logger::info('Paypal.Raw.Response', array($_POST));
		$this->timeIdentifier = date('YmdHis');
		$this->txnIdentifier = $_POST['txn_id'];
		$this->orderRepository = $this->objectManager->get('Aijko\Paypal\Domain\Repository\OrderRepository');
		$this->ipnListener = $this->objectManager->get('IpnListener');
		$this->persistenceManager = $this->objectManager->get ('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager');
		if ($this->settings['context']['sandbox']) {
			$this->ipnListener->use_sandbox = TRUE;
		}

		try {
			$this->ipnListener->requirePostMethod();
			$verified = $this->ipnListener->processIpn($_POST);
			if ($verified) { // The processIpn() method returned true if the IPN was "VERIFIED" and false if it was "INVALID".
			#if (1) {
				if ('Completed' == $_POST['payment_status']) {
					if (!$this->orderRepository->findOneByTxnid($this->txnIdentifier)) { // Check that $_POST['txn_id'] has not been previously processed
						if ($this->settings['seller']['email'] == $_POST['receiver_email']) { // Check that $_POST['receiver_email'] is your Primary PayPal email
							$order = $this->storeAndGetOrder();
							$subject = 'IPN OK [' . $this->timeIdentifier . '_' . $this->txnIdentifier . ']';
							$this->sendNotificationAndLog($subject, $subject);
							$this->signalSlotDispatcher->dispatch(__CLASS__, __FUNCTION__ . 'StoreOrderAfter', array($order, $_POST));
						} else {
							throw new \Aijko\Paypal\Exception('receiver_email is not equals ' . $this->settings['seller']['email'] . ' [' . $this->timeIdentifier . '_' . $this->txnIdentifier . ']', 1392387854);
						}
					} else {
						throw new \Aijko\Paypal\Exception('IPN txn_id has been previously processed [' . $this->timeIdentifier . '_' . $this->txnIdentifier . ']', 1392387855);
					}
				} else {
					throw new \Aijko\Paypal\Exception('IPN payment_status is not completed [' . $this->timeIdentifier . '_' . $this->txnIdentifier . ']', 1392387856);
				}
			} else {
				$content = 'An Invalid IPN *may* be caused by a fraudulent transaction attempt. Its a good idea to have a developer or sys admin manually investigate any invalid IPN.';
				throw new \Aijko\Paypal\Exception('Invalid IPN [' . $this->timeIdentifier . '_' . $this->txnIdentifier . ']', 1392387857);
			}
		} catch (\Aijko\Paypal\Exception $e) {
			if (!$content) {
				$content = $e->getMessage();
			}
			$this->sendNotificationAndLog($e->getMessage(), $content, 'error');
			die($e->getMessage());
		} catch (\Exception $e) {
			$this->sendNotificationAndLog('IPN Exception [' . $this->timeIdentifier . '_' . $this->txnIdentifier . ']', $e->getMessage(), 'error');
			die($e->getMessage());
		}

		return '';
	}

	/**
	 * @return \Aijko\Paypal\Domain\Model\Order
	 */
	protected function storeAndGetOrder() {
		$orderObject = $this->objectManager->get('Aijko\\Paypal\\Domain\\Model\\Order');
		$orderObject->setPid($this->settings['storagePid']);
		$orderObject->setCrdate(time());
		$orderObject->setTxnid($this->txnIdentifier);
		$orderObject->setIdentifier($this->timeIdentifier . '_' . $this->txnIdentifier);
		$orderObject->setResponse($this->ipnListener->getTextReport());
		$this->orderRepository->add($orderObject);
		$this->persistenceManager->persistAll();
		return $orderObject;
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