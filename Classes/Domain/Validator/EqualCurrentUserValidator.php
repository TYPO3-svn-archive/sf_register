<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Sebastian Fischer <typo3@evoweb.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
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
 * A validator to check if the userid is equal to the id of the logged in user
 *
 * @scope singleton
 */
class Tx_SfRegister_Domain_Validator_EqualCurrentUserValidator extends Tx_Extbase_Validation_Validator_AbstractValidator {
	/**
	 * If the given value is empty
	 *
	 * @param string $value The value
	 * @return boolean
	 */
	public function isValid($value) {
		$result = TRUE;

		if ($value != $GLOBALS['TSFE']->fe_user->user['uid']) {
			$this->addError(Tx_Extbase_Utility_Localization::translate('error.notequalcurrentuser', 'SfRegister'), 1305009260);
			$result = FALSE;
		}

		return $result;
	}
}

?>