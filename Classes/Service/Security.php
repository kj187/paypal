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
 * Security Service
 * https://developer.paypal.com/webapps/developer/docs/classic/paypal-payments-standard/integration-guide/encryptedwebpayments/#id08A3I0MK05Z
 *
 * @author Julian Kleinhans <julian.kleinhans@aijko.de>
 * @copyright Copyright belongs to the respective authors
 * @package Aijko\Paypal
 */
class Security {

	/**
	 * @var array
	 */
	protected $settings;

	/**
	 * @param array $settings
	 */
	public function injectSettings(array $settings) {
		$this->settings = $settings;
	}

	/**
	 * Public Key Encryption Used by Encrypted Website Payments
	 *
	 * Encrypted Website Payments uses public key encryption, or asymmetric cryptography, which provides security and convenience
	 * by allowing senders and receivers of encrypted communication to exchange public keys to unlock each others messages.
	 * The fundamental aspects of public key encryption are:
	 *
	 * Public keys
	 * They are created by receivers and are given to senders before they encrypt and send information. Public certificates
	 * comprise a public key and identity information, such as the originator of the key and an expiry date.
	 * Public certificates can be signed by certificate authorities, who guarantee that public certificates and their
	 * public keys belong to the named entities. You and PayPal exchange each others' public certificates.
	 *
	 * Private keys
	 * They are created by receivers are kept to themselves. You create a private key and keep it in your system.
	 * PayPal keeps its private key on its system.
	 *
	 * The encryption process
	 * Senders use their private keys and receivers' public keys to encrypt information before sending it.
	 * Receivers use their private keys and senders' public keys to decrypt information after receiving it.
	 * This encryption process also uses digital signatures in public certificates to verify the sender of the information.
	 * You use your private key and PayPal's public key to encrypt your HTML button code.
	 * PayPal uses its private key and your public key to decrypt button code after people click your payment buttons.
	 *
	 * @param array $data
	 * @return string
	 * @throws \Exception
	 */
	public function encrypt(array $data) {
		$extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['paypal']);
		$openSSL = $extensionConfiguration['opensslPath'];

		$certificationDir = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($this->settings['certification']['dir']);
		if ($this->settings['context']['sandbox']) {
			$certificationDir .= 'Sandbox/';
		}

		$files = array();
		foreach($this->settings['certification']['file'] as $type => $file) {
			$files[$type] = $certificationDir . $file;
			if (!file_exists($files[$type])) {
				throw new \Exception('Certification "' . $files[$type] . '" does not exist!', 1392135405);
			}
		}

		$data['cert_id']= $this->settings['seller']['cert_id'];
		$data['bn']= 'ShoppingCart_WPS';
		$hash = '';
		foreach ($data as $key => $value) {
			if ($value != '') {
				$hash .= $key . '=' . $value ."\n";
			}
		}

		$openssl_cmd = "($openSSL smime -sign -signer " . $files['public'] . " -inkey " . $files['private'] . " " .
			"-outform der -nodetach -binary <<_EOF_\n$hash\n_EOF_\n) | " .
			"$openSSL smime -encrypt -des3 -binary -outform pem " . $files['public_paypal'] . "";
		exec($openssl_cmd, $output, $error);

		if (!$error) {
			return implode("\n", $output);
		} else {
			throw new \Exception('Paypal Request Encryption failed!', 1392135967);
		}
	}

}

?>