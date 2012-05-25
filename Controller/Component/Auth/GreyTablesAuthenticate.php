<?php

App::uses('FormAuthenticate', 'Controller/Component/Auth');

class GreyTablesAuthenticate extends FormAuthenticate {

	private $Model;

	public function __construct(ComponentCollection $collection, $settings) {
		$defaults = array(
			'saltField' => 'salt'
		);

		$this->settings = array_merge($this->settings, $defaults);
		$this->Model = ClassRegistry::init($this->settings['userModel']);
	}

	function _findUser($usernameData, $passwordData) {
		extract($this->settings);
		$passwordData = $this->_password($passwordData);

		$user = $this->Model->find('first', array(
				'conditions' => array(
					$this->Model->alias.'.'.$fields['username'] => $usernameData
				),
				'fields' => array('*', 'password'),
				'recursive' => $recursive,
				'avoidRecursion' => true
			)
		);

		if (empty($user) || empty($user[$this->Model->alias])) {
			return false;
		}

		$isValidUser = $this->testSaltedUser($passwordData, $user[$this->Model->alias][$saltField], $user[$this->Model->alias][$fields['password']]);
		if ($isValidUser) {
			unset($user[$this->Model->alias][$fields['password']]);
		} else {
			return false;
		}

		return $user[$this->Model->alias];
	}

	function beforeSave(&$Model) {
		$data = &$Model->data[$Model->alias];
		extract($this->settings[$Model->alias]);
		if (!empty($data[$password])) {
			$data[$field] = $this->generateSaltString();
			$data[$password] = $this->generateSaltedPassword($data[$password], $data[$field]);
		}
		return parent::beforeSave($Model);
	}

	function generateSaltString() {
		return Security::hash(String::uuid(), null, true);
	}

	function generateSaltedPassword($password = '', $saltString) {
		if (!empty($password)) {
			return Security::hash($password.$saltString, null, false);
		}
	}

	function testSaltedUser($password = '', $saltString = '', $dbPassword = '') {
		$saltedPassword = $this->generateSaltedPassword($password, $saltString);
		if ($dbPassword == $saltedPassword) {
			return true;
		}
	}

}