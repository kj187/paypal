<?php
$extensionClassesPath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('paypal') . '/';

return array(
	'IpnListener' => $extensionClassesPath . 'Resources/Private/Php/PHP-PayPal-IPN/ipnlistener.php',
);

?>