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
 * Notification Service
 * http://docs.typo3.org/TYPO3/CoreApiReference/ApiOverview/Mail/Index.html
 *
 * @author Julian Kleinhans <julian.kleinhans@aijko.de>
 * @copyright Copyright belongs to the respective authors
 * @package Aijko\Paypal
 */
class Notification {

	/**
	 * @param array $from  array('email' => '', 'name' => '')
	 * @param array $to array('email' => '', 'name' => '')
	 * @param $subject
	 * @param $body
	 * @return boolean
	 */
	public static function sendNotification(array $from, array $to, $subject, $body) {
		if (!count($from)) {
			$from = \TYPO3\CMS\Core\Utility\MailUtility::getSystemFrom();
		}

		$mail = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Mail\\MailMessage');
		return $mail->setFrom(array($from['email'] => $from['name']))
			->setTo(array($to['email'] => $to['name']))
			->setSubject($subject)
			->setBody($body)
			->send();
	}

}

?>