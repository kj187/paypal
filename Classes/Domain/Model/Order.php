<?php
namespace Aijko\Paypal\Domain\Model;

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
 * @author Julian Kleinhans <julian.kleinhans@aijko.de>
 * @copyright Copyright belongs to the respective authors
 * @package Aijko\Paypal
 */
class Order extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity {

	/**
	 * @var string
	 * @validate NotEmpty
	 */
	protected $txnid;

	/**
	 * @var string
	 * @validate NotEmpty
	 */
	protected $identifier;

	/**
	 * @var string
	 */
	protected $response;

	/**
	 * @param string $response
	 */
	public function setResponse($response) {
		$this->response = $response;
	}

	/**
	 * @return string
	 */
	public function getResponse() {
		return $this->response;
	}

	/**
	 * @return string $txnid
	 */
	public function getTxnid() {
		return $this->txnid;
	}

	/**
	 * @param string $txnid
	 * @return void
	 */
	public function setTxnid($txnid) {
		$this->txnid = $txnid;
	}

	/**
	 * @return string $identifier
	 */
	public function getIdentifier() {
		return $this->identifier;
	}

	/**
	 * @param string $identifier
	 * @return void
	 */
	public function setIdentifier($identifier) {
		$this->identifier = $identifier;
	}
	
}

?>