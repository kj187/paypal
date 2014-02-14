<?php
namespace Aijko\Paypal\Service;

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
 * Request Service
 * https://developer.paypal.com/webapps/developer/docs/classic/paypal-payments-standard/integration-guide/cart_upload/
 *
 * @author Julian Kleinhans <julian.kleinhans@aijko.de>
 * @copyright Copyright belongs to the respective authors
 * @package paypal
 */
class Request {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @var \Aijko\Paypal\Service\Security
	 */
	protected $securityService;

	/**
	 * @var array
	 */
	protected $data;

	/**
	 * @var float
	 */
	protected $tax;

	/**
	 * @var \Aijko\SharepointConnector\Utility\View
	 */
	protected $viewUtility;

	/**
	 * Inject all dependencies, DI is not available here
	 */
	public function __construct() {
		$this->objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->securityService = $this->objectManager->get('Aijko\\Paypal\\Service\\Security');
		$this->configurationManager = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManagerInterface');
		$pluginConfiguration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK, 'paypal');
		$this->settings = $pluginConfiguration['settings'];
		$this->securityService->injectSettings($this->settings);
		$this->viewUtility = $this->objectManager->get('Aijko\\SharepointConnector\\Utility\\View');
		$this->viewUtility->setTemplateRootPath($pluginConfiguration['view']['templateRootPath']);
	}

	/**
	 * @param float $tax
	 * @return \Aijko\Paypal\Service\Request
	 */
	public function setTax($tax) {
		$this->tax = (float)$tax;
		return $this;
	}

	/**
	 * @param array $data
	 * @return \Aijko\Paypal\Service\Request
	 */
	public function setData(array $data) {
		$this->data = $this->mergeWithGlobalData($data);
		return $this;
	}

	/**
	 * @return void
	 */
	public function process() {
		echo $this->viewUtility->getStandaloneView(array(
			'requestUrl' => $this->settings['request']['url'],
			'encryptedData' => $this->securityService->encrypt($this->data)
		), 'Request.html')->render();

		die();
	}

	/**
	 * @param $data
	 * @return array
	 */
	protected function mergeWithGlobalData($data) {
		$totalNetPrice = $data['amount_1']*$data['quantity_1']; // @TODO change later for basket behaviour
		return array_merge(array(
			'cmd' => $this->settings['request']['cmd'],
			'business' => $this->settings['seller']['email'],
			'cert_id' => $this->settings['seller']['cert_id'],
			'upload' => $this->settings['request']['upload'],
			'currency_code' => $this->settings['request']['currency_code'],
			'return' => $this->buildUrl($this->settings['request']['return_confirmation_uid']),
			'cancel_return' => $this->buildUrl($this->settings['request']['return_cancel_uid']),
			'notify_url' => $this->buildUrl($GLOBALS['TSFE']->id, $this->settings['ipnListener']['typeNum']),
			'tax_cart' => number_format(\Aijko\Paypal\Utility\Math::calculateTax($totalNetPrice, $this->tax), 2, '.', ''),
		), $data);
	}

	/**
	 * @param integer $pageUid
	 * @param integer $pageType
	 * @return string
	 */
	protected function buildUrl($pageUid, $pageType = 0) {
		$uriBuilder = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Web\\Routing\\UriBuilder');
		$uriBuilder->setTargetPageUid($pageUid);
		if ($pageType) $uriBuilder->setTargetPageType($pageType);
		return $uriBuilder->setCreateAbsoluteUri(TRUE)->build();
	}

}

?>