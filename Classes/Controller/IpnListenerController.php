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
 *
 * @author Julian Kleinhans <julian.kleinhans@aijko.de>
 * @copyright Copyright belongs to the respective authors
 * @package paypal
 */
class IpnListenerController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * @param string $a
	 * @param string $b
	 *
	 * @return string
	 */
	public function receiveAction($a = '', $b = '') {

		mail('julian.kleinhans@aijko.com', 'Test', print_r($_GET, TRUE), print_r($_POST, TRUE), print_r($_SERVER, TRUE), print_r($a, TRUE), print_r($b, TRUE));

		return "OK";
	}

}
?>