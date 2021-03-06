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
 * Validator to check against current password
 *
 * @scope singleton
 */
class Tx_SfRegister_Domain_Validator_EqualCurrentPasswordValidator extends Tx_Extbase_Validation_Validator_AbstractValidator {
	/**
	 * Configuration manager
	 *
	 * @var Tx_Extbase_Configuration_ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * Settings
	 *
	 * @var array
	 */
	protected $settings = array();

	/**
	 * Frontend user repository
	 *
	 * @var Tx_SfRegister_Domain_Repository_FrontendUserRepository
	 */
	protected $userRepository = NULL;

	/**
	 * Inject a configuration manager
	 *
	 * @param Tx_Extbase_Configuration_ConfigurationManager $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(Tx_Extbase_Configuration_ConfigurationManager $configurationManager) {
		$this->configurationManager = $configurationManager;
		$this->settings = $this->configurationManager->getConfiguration(Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS);
	}

	/**
	 * Inject the frontend user repository
	 *
	 * @param Tx_SfRegister_Domain_Repository_FrontendUserRepository $repository
	 * @return void
	 */
	public function injectUserRepository(Tx_SfRegister_Domain_Repository_FrontendUserRepository $repository) {
		$this->userRepository = $repository;
	}

	/**
	 * If the given value is set
	 *
	 * @param boolean $password The value
	 * @return boolean
	 */
	public function isValid($password) {
		$result = TRUE;

		if (!$this->isUserLoggedIn()) {
			$this->addError(Tx_Extbase_Utility_Localization::translate('error.changepassword.notloggedin', 'SfRegister'), 1301599489);
			$result = FALSE;
		} else {
			$user = $this->userRepository->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);

			$password = $this->encryptPassword($password);

			if ($user->getPassword() !== $password) {
				$this->addError(Tx_Extbase_Utility_Localization::translate('error.changepassword.notequal', 'SfRegister'), 1301599507);
				$result = FALSE;
			}
		}

		return $result;
	}

	/**
	 * Check if the user is logged in
	 *
	 * @return boolean
	 */
	protected function isUserLoggedIn() {
		return $GLOBALS['TSFE']->fe_user->user === FALSE ? FALSE : TRUE;
	}

	/**
	 * Encrypt the password
	 *
	 * @param string $password
	 * @return string
	 */
	protected function encryptPassword($password) {
			// @todo use static method from createController
		if (t3lib_extMgm::isLoaded('saltedpasswords') && tx_saltedpasswords_div::isUsageEnabled('FE')) {
			$saltObject = tx_saltedpasswords_salts_factory::getSaltingInstance(NULL);

			if (is_object($saltObject)) {
				$password = $saltObject->getHashedPassword($password);
			}
		} elseif ($this->settings['encryptPassword'] === 'md5') {
			$password = md5($password);
		} elseif ($this->settings['encryptPassword'] === 'sha1') {
			$password = sha1($password);
		}

		return $password;
	}
}

?>