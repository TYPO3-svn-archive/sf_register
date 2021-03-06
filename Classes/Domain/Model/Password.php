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
 * A password object for validation
 */
class Tx_SfRegister_Domain_Model_Password extends Tx_Extbase_DomainObject_AbstractEntity {
	/**
	 * Password
	 *
	 * @var string
	 */
	protected $password;

	/**
	 * Password again
	 *
	 * @var string
	 */
	protected $passwordAgain;

	/**
	 * Old password
	 *
	 * @var string
	 */
	protected $oldPassword;

	/**
	 * Setter for password
	 *
	 * @param string $password
	 * @return void
	 */
	public function setPassword($password) {
		$this->password = $password;
	}

	/**
	 * Getter for password
	 *
	 * @return string
	 */
	public function getPassword() {
		return $this->password;
	}

	/**
	 * Setter for passwordAgain
	 *
	 * @param string $passwordAgain
	 * @return void
	 */
	public function setPasswordAgain($passwordAgain) {
		$this->passwordAgain = $passwordAgain;
	}

	/**
	 * Getter for passwordAgain
	 *
	 * @return string
	 */
	public function getPasswordAgain() {
		return $this->passwordAgain;
	}

	/**
	 * Setter for oldPassword
	 *
	 * @param string $oldPassword
	 * @return void
	 */
	public function setOldPassword($oldPassword) {
		$this->oldPassword = $oldPassword;
	}

	/**
	 * Getter for oldPassword
	 *
	 * @return string
	 */
	public function getOldPassword() {
		return $this->oldPassword;
	}
}

?>