<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 AIJKO GmbH <info@aijko.de>
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

if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$TCA['tx_paypal_domain_model_order'] = array(
	'ctrl' => $TCA['tx_paypal_domain_model_order']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'txnid, identifier, response',
	),
	'types' => array(
		'1' => array('showitem' => 'txnid, identifier, response'),
	),
	'palettes' => array(
		'1' => array('showitem' => ''),
	),
	'columns' => array(
		'crdate' => array(
			'exclude' => 1,
			'l10n_mode' => 'mergeIfNotBlank',
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.crdate',
			'config' => array(
				'type' => 'input',
				'size' => 13,
				'max' => 20,
				'eval' => 'datetime',
				'checkbox' => 0,
				'default' => 0,
				'range' => array(
					'lower' => mktime(0, 0, 0, date('m'), date('d'), date('Y'))
				),
			),
		),
		'txnid' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:paypal/Resources/Private/Language/locallang_db.xlf:tx_paypal_domain_model_order.txnid',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'eval' => 'trim,required',
				'readOnly' => 1
			),
		),
		'identifier' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:paypal/Resources/Private/Language/locallang_db.xlf:tx_paypal_domain_model_order.identifier',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'eval' => 'trim,required',
				'readOnly' => 1
			),
		),
		'response' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:paypal/Resources/Private/Language/locallang_db.xlf:tx_paypal_domain_model_order.response',
			'config' => array(
				'type' => 'text',
				'eval' => 'trim,required',
				'cols' => '70',
				'rows' => '60',
				'readOnly' => 1
			),
		),

	),
);

?>