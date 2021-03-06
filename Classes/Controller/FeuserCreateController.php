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
 * An frontend user create controller
 */
class Tx_SfRegister_Controller_FeuserCreateController extends Tx_SfRegister_Controller_FeuserController {
	/**
	 * User repository
	 *
	 * @var Tx_SfRegister_Domain_Model_FrontendUserRepository
	 */
	protected $userRepository = NULL;

	/**
	 * Usergroup repository
	 *
	 * @var Tx_Extbase_Domain_Repository_FrontendUserGroupRepository
	 */
	protected $userGroupRepository = NULL;

 	/**
	 * @param Tx_Extbase_Domain_Repository_FrontendUserGroupRepository $frontendUserGroupRepository
	 * @return void
	 */
	public function injectFrontendUserGroupRepository(Tx_Extbase_Domain_Repository_FrontendUserGroupRepository $frontendUserGroupRepository) {
		$this->userGroupRepository = $frontendUserGroupRepository;
	}

	/**
	 * Form action
	 *
	 * @param Tx_SfRegister_Domain_Model_FrontendUser $user
	 * @return string An HTML form
	 * @ignorevalidation $user
	 */
	public function formAction(Tx_SfRegister_Domain_Model_FrontendUser $user = NULL) {
		if ($user === NULL ||
				$user instanceof Tx_SfRegister_Interfaces_FrontendUser &&
				$user->getUid()) {
			$user = $this->objectManager->create('Tx_SfRegister_Domain_Model_FrontendUser');
		} else {
			$user = $this->moveTempFile($user);
		}

		$user->prepareDateOfBirth();

		$originalRequest = $this->request->getOriginalRequest();
		if ($originalRequest !== NULL && $originalRequest->hasArgument('temporaryImage')) {
			$this->view->assign('temporaryImage', $originalRequest->getArgument('temporaryImage'));
		}

		$user = Tx_SfRegister_Services_Hook::process('form', $user, $this->settings, $this->objectManager);

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
		$user->setPassword($this->encryptPassword($user->getPassword()));

		if ($this->isNotifyPreActivationToUser() || $this->isNotifyPreActivationToAdmin()) {
			$user->setDisable(TRUE);
			$user->setActivatedOn(new DateTime('1970-01-01'));
			$user = $this->setUsergroupPreActivation($user);
		} else {
			$user = $this->moveImageFile($user);
			$user = $this->addUsergroup($user, $this->settings['usergroup']);
		}

		$user = $this->sendEmailsPreSave($user);

		if ($this->settings['useEmailAddressAsUsername']) {
			$user->setUsername($user->getEmail());
		}

		$this->userRepository->add($user);
		$this->persistAll();

		t3lib_div::makeInstance('Tx_SfRegister_Services_Session')
			->remove('captchaWasValidPreviously');

		if ($this->settings['autologinPostRegistration']) {
			$this->autoLogin($user);
		}

		if ($this->settings['redirectPostRegistrationPageId']) {
			$this->redirectToPage($this->settings['redirectPostRegistrationPageId']);
		}
	}

	/**
	 * Initialization confirm action
	 *
	 * @return void
	 */
	protected function initializeConfirmAction() {
		$this->userRepository = t3lib_div::makeInstance('Tx_SfRegister_Domain_Repository_FrontendUserRepository');
		$this->userGroupRepository = t3lib_div::makeInstance('Tx_Extbase_Domain_Repository_FrontendUserGroupRepository');
	}

	/**
	 * Confirm action
	 *
	 * @param string $authCode
	 * @return void
	 */
	public function confirmAction($authCode) {
		$user = $this->userRepository->findByMailhash($authCode);

		if (!($user instanceof Tx_SfRegister_Domain_Model_FrontendUser)) {
			$this->view->assign('userNotFound', 1);
		} else {
			$this->view->assign('user', $user);

			if ($user->getActivatedOn()) {
				$this->view->assign('userAlreadyActive', 1);
			} else {
				$user = $this->changeUsergroupPostActivation($user);
				$user = $this->moveImageFile($user);
				$user->setDisable(FALSE);
				$user->setActivatedOn(new DateTime('now'));

				$this->sendEmailsPostConfirm($user);

				if ($this->settings['autologinPostActivation']) {
					$this->persistAll();
					$this->autoLogin($user);
				}

				if ($this->settings['redirectPostActivationPageId']) {
					$this->redirectToPage($this->settings['redirectPostActivationPageId']);
				}

				$this->view->assign('userActivated', 1);
			}
		}
	}


	/**
	 * Redirect to a page with given id
	 *
	 * @param integer $pageId
	 * @return void
	 */
	protected function redirectToPage($pageId) {
		$url = $this->uriBuilder
			->setTargetPageUid($pageId)
			->build();
		$this->redirectToUri($url);
	}

	/**
	 * Persist all data that was not stored by now
	 *
	 * @return void
	 */
	protected function persistAll() {
		$this->objectManager->get('Tx_Extbase_Persistence_Manager')->persistAll();
	}

	/**
	 * Login user with service
	 *
	 * @param Tx_SfRegister_Domain_Model_FrontendUser $user
	 * @return void
	 */
	protected function autoLogin(Tx_SfRegister_Domain_Model_FrontendUser $user) {
		$this->objectManager->get('Tx_SfRegister_Services_Login')->loginUserById($user->getUid());
	}


	/**
	 * Add usergroup to user
	 *
	 * @param Tx_SfRegister_Domain_Model_FrontendUser $user
	 * @param integer $usergroupUid
	 * @return Tx_SfRegister_Domain_Model_FrontendUser
	 */
	protected function addUsergroup(Tx_SfRegister_Domain_Model_FrontendUser $user, $usergroupUid) {
		$usergroupToAdd = $this->userGroupRepository->findByUid($usergroupUid);
		$user->addUsergroup($usergroupToAdd);

		return $user;
	}

	/**
	 * Set usergroup to user before activation
	 *
	 * @param Tx_SfRegister_Domain_Model_FrontendUser $user
	 * @return Tx_SfRegister_Domain_Model_FrontendUser
	 */
	protected function setUsergroupPreActivation(Tx_SfRegister_Domain_Model_FrontendUser $user) {
		if (intval($this->settings['usergroupPreActivation']) > 0) {
			$user = $this->addUsergroup($user, $this->settings['usergroupPreActivation']);
		}

		return $user;
	}

	/**
	 * Change usergroup of user after activation
	 *
	 * @param Tx_SfRegister_Domain_Model_FrontendUser $user
	 * @return Tx_SfRegister_Domain_Model_FrontendUser
	 */
	protected function changeUsergroupPostActivation(Tx_SfRegister_Domain_Model_FrontendUser $user) {
		if (intval($this->settings['usergroupPostActivation']) > 0 &&
				intval($this->settings['usergroupAfterActivation']) != intval($this->settings['usergroupPreActivation'])) {
			$user = $this->addUsergroup($user, $this->settings['usergroupPostActivation']);

			$usergroupToRemove = $this->userGroupRepository->findByUid($this->settings['usergroupPreActivation']);
			$user->removeUsergroup($usergroupToRemove);
		}

		return $user;
	}


	/**
	 * Send emails to user and/or to admin
	 *
	 * @param Tx_SfRegister_Domain_Model_FrontendUser $user
	 * @return Tx_SfRegister_Domain_Model_FrontendUser
	 */
	protected function sendEmailsPreSave($user) {
		/** @var $mailService Tx_SfRegister_Services_Mail */
		$mailService = $this->objectManager->get('Tx_SfRegister_Services_Mail');

		if ($this->isNotifyPreActivationToAdmin()) {
			$user = $mailService->sendAdminNotificationMailPreActivation($user);
		} elseif ($this->isNotifyPreActivationToUser()) {
			$user = $mailService->sendUserNotificationMailPreActivation($user);
		}

		if ($this->isNotifyToAdmin()) {
			$mailService->sendAdminNotificationMail($user);
		}
		if ($this->isNotifyToUser()) {
			$mailService->sendUserNotificationMail($user);
		}

		return $user;
	}

	/**
	 * Send emails to user and/or to admin
	 *
	 * @param Tx_SfRegister_Domain_Model_FrontendUser $user
	 * @return Tx_SfRegister_Domain_Model_FrontendUser
	 */
	protected function sendEmailsPostConfirm($user) {
		/** @var $mailService Tx_SfRegister_Services_Mail */
		$mailService = $this->objectManager->get('Tx_SfRegister_Services_Mail');

		if ($this->isNotifyPostActivationToAdmin()) {
			$mailService->sendAdminNotificationMailPostActivation($user);
		}
		if ($this->isNotifyPostActivationToUser()) {
			$mailService->sendUserNotificationMailPostActivation($user);
		}

		return $user;
	}


	/**
	 * Check if the admin should get notified account registration
	 *
	 * @return boolean
	 */
	protected function isNotifyToAdmin() {
		$result = FALSE;

		if ($this->settings['notifyToAdmin'] && !$this->isNotifyPreActivationToAdmin()) {
			$result = TRUE;
		}

		return $result;
	}

	/**
	 * Check if the admin should get notified about account activation
	 *
	 * @return boolean
	 */
	protected function isNotifyPostActivationToAdmin() {
		$result = FALSE;

		if ($this->settings['notifyPostActivationToAdmin']) {
			$result = TRUE;
		}

		return $result;
	}

	/**
	 * Check if the admin need to activate the account
	 *
	 * @return boolean
	 */
	protected function isNotifyPreActivationToAdmin() {
		$result = FALSE;

		if ($this->settings['notifyPreActivationToAdmin']) {
			$result = TRUE;
		}

		return $result;
	}

	/**
	 * Check if the user should get notified account registration
	 *
	 * @return boolean
	 */
	protected function isNotifyToUser() {
		$result = FALSE;

		if (($this->settings['notifyToUser'] && !$this->isNotifyPreActivationToUser()) ||
				($this->settings['notifyToUser'] && $this->isNotifyPreActivationToAdmin())) {
			$result = TRUE;
		}

		return $result;
	}

	/**
	 * Check if the user should get notified about account activation
	 *
	 * @return boolean
	 */
	protected function isNotifyPostActivationToUser() {
		$result = FALSE;

		if ($this->settings['notifyPostActivationToUser']) {
			$result = TRUE;
		}

		return $result;
	}

	/**
	 * Check if the user need to activate the account
	 *
	 * @return boolean
	 */
	protected function isNotifyPreActivationToUser() {
		$result = FALSE;

		if ($this->settings['notifyPreActivationToUser']) {
			$result = TRUE;
		}

		return $result;
	}
}

?>
