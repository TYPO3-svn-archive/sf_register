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
 * An frontend user edit controller
 */
class Tx_SfRegister_Controller_FeuserEditController extends Tx_SfRegister_Controller_FeuserController {
	/**
	 * Form action
	 *
	 * @param Tx_SfRegister_Domain_Model_FrontendUser $user
	 * @return string An HTML form
	 * @ignorevalidation $user
	 */
	public function formAction(Tx_SfRegister_Domain_Model_FrontendUser $user = NULL) {
		if ($user == NULL && $this->isUserLoggedIn() ||
				($user instanceof Tx_SfRegister_Domain_Model_FrontendUser &&
				 $user->getUid() != $GLOBALS['TSFE']->fe_user->user['uid'])) {
			$user = $this->userRepository->findByUid($GLOBALS['TSFE']->fe_user->user['uid']);

			$user = $this->moveTempFile($user);
		}

		if ($user instanceof Tx_SfRegister_Domain_Model_FrontendUser) {
			$user->prepareDateOfBirth();
		}

		$originalRequest = $this->request->getOriginalRequest();
		if ($originalRequest->hasArgument('temporaryImage')) {
			$this->view->assign('temporaryImage', $originalRequest->getArgument('temporaryImage'));
		}
		$this->view->assign('user', $user);
	}

	/**
	 * Preview action
	 *
	 * @param Tx_SfRegister_Domain_Model_FrontendUser $user
	 * @return void
	 * @validate $user Tx_SfRegister_Domain_Validator_UserValidator
	 */
	public function previewAction(Tx_SfRegister_Domain_Model_FrontendUser $user) {
		$user = $this->moveTempFile($user);

		$user->prepareDateOfBirth();

		if ($this->request->hasArgument('temporaryImage')) {
			$this->view->assign('temporaryImage', $this->request->getArgument('temporaryImage'));
		}

		$this->view->assign('user', $user);
	}

	/**
	 * Save action
	 *
	 * @param Tx_SfRegister_Domain_Model_FrontendUser $user
	 * @return void
	 * @validate $user Tx_SfRegister_Domain_Validator_UserValidator
	 */
	public function saveAction(Tx_SfRegister_Domain_Model_FrontendUser $user) {
		$user = $this->moveImageFile($user);

		if ($this->isDisabledAfterEdit()) {
			$user->setDisable(TRUE);
		}

		$user = $this->sendEmailsPostEdit($user);

		$user = Tx_SfRegister_Services_Hook::process('save', $user, $this->settings, $this->objectManager);

		$this->userRepository->update($user);

		t3lib_div::makeInstance('Tx_SfRegister_Services_Session')
			->remove('captchaWasValidPreviously');

		if ($this->settings['forwardToEditAfterSave']) {
			$this->forward('form');
		}
	}


	/**
	 * Send notification or activation email either to user or admin
	 *
	 * @param Tx_SfRegister_Domain_Model_FrontendUser $user
	 * @return Tx_SfRegister_Domain_Model_FrontendUser
	 */
	protected function sendEmailsPostEdit($user) {
		$mailService = $this->objectManager->get('Tx_SfRegister_Services_Mail');

		if ($this->isNotifyActivationPostEditingToAdmin()) {
			$user = $mailService->sendAdminActivationMailAfterEdit($user);
		} elseif ($this->isNotifyActivationPostEditingToUser()) {
			$user = $mailService->sendUserActivationMailAfterEdit($user);
		}

		if ($this->isNotifyPostEditingToAdmin()) {
			$mailService->sendAdminNotificationMailAfterEdit($user);
		}
		if ($this->isNotifyPostEditingToUser()) {
			$mailService->sendUserNotificationMailAfterEdit($user);
		}

		return $user;
	}


	/**
	 * @return boolean
	 */
	protected function isDisabledAfterEdit() {
		$result = FALSE;

		if ($this->isNotifyActivationPostEditingToUser() || $this->isNotifyActivationPostEditingToAdmin()) {
			$result = TRUE;
		}

		return $result;
	}

	/**
	 * @return boolean
	 */
	protected function isNotifyActivationPostEditingToUser() {
		$result = FALSE;

		if ($this->settings['notifyActivationPostEditingToUser']) {
			$result = TRUE;
		}

		return $result;
	}

	/**
	 * @return boolean
	 */
	protected function isNotifyActivationPostEditingToAdmin() {
		$result = FALSE;

		if ($this->settings['notifyActivationPostEditingToAdmin']) {
			$result = TRUE;
		}

		return $result;
	}

	/**
	 * @return boolean
	 */
	protected function isNotifyPostEditingToUser() {
		$result = FALSE;

		if (!$this->isNotifyActivationPostEditingToUser() && $this->settings['notifyPostEditingToUser']) {
			$result = TRUE;
		}

		return $result;
	}

	/**
	 * @return boolean
	 */
	protected function isNotifyPostEditingToAdmin() {
		$result = FALSE;

		if ($this->isNotifyActivationPostEditingToAdmin() && $this->settings['notifyPostEditingToAdmin']) {
			$result = TRUE;
		}

		return $result;
	}
}

?>
