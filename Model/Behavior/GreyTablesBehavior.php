<?php

class GreyTablesBehavior extends ModelBehavior {

	function beforeSave(Model $Model) {
		$data = &$Model->data[$Model->alias];

		if (!empty($data['password'])) {
			$data['salt'] = $this->generateSaltString();
			$data['password'] = $this->generateSaltedPassword($data['password'], $data['salt']);
		} else {
			unset($data['password']);
		}
		return parent::beforeSave($Model);
	}

	public function generateSaltString() {
		return Security::hash(String::uuid(), null, true);
	}

	public function generateSaltedPassword($password = '', $saltString) {
		if (!empty($password)) {
			$password = Security::hash($password, null, true);
			return Security::hash($password.$saltString, null, false);
		}
	}

}