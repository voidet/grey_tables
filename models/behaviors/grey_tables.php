<?php

	class GreyTablesBehavior extends ModelBehavior {

		function setup(&$Model, $settings = array()) {
			$default = array(
				'field' => 'salt',
				'username' => 'username',
				'password' => 'password'
			);

			if (!isset($this->settings[$Model->name])) {
				$this->settings[$Model->name] = $default;
			}

			$this->settings[$Model->name] = array_merge($this->settings[$Model->name], ife(is_array($settings), $settings, array()));
		}

		function beforeFind(&$Model, $queryData) {
			if(!empty($queryData['conditions'][$Model->name.'.'.$this->settings[$Model->name]['password']]) && !empty($queryData['conditions'][$Model->name.'.'.$this->settings[$Model->name]['username']]) && (empty($queryData['conditions']['avoidRecursion']) || $queryData['conditions']['avoidRecursion'] !== true)) {
				$user_id = $this->findSaltedUser($Model, $queryData['conditions']);
				if (!empty($user_id)) {
					unset($queryData['conditions']);
					$queryData['conditions'] = $user_id;
				}
			}
			return $queryData;
		}

		function beforeSave(&$Model) {
			if (empty($this->id) && !empty($Model->data[$Model->name])) {
				$data = &$Model->data[$Model->name];
				$data[$this->settings[$Model->name]['password']] = $this->generateSaltedPassword($data[$this->settings[$Model->name]['password']], $data[$this->settings[$Model->name]['field']]);
			}
			return parent::beforeSave(&$Model);
		}

		function generateSaltedPassword($password = '', $saltString) {
			if (!empty($password)) {
				return Security::hash($password.$saltString, null, false);
			}
		}

		function findSaltedUser(&$Model, $fields = array()) {
			if (!empty($fields)) {

				$db =& $Model->getDataSource();
				$passwordField
				$passwordExpression = $db->expression(sprintf('SHA1(CONCAT(%s, %s))',
					$db->name($this->settings[$Model->name]['password']),
					$db->name($this->settings[$Model->name]['field']),
				));

				$user_id = $Model->find('first', array(
						'conditions' => array(
							$Model->name.'.'.$this->settings[$Model->name][$this->settings[$Model->name]['username']] => $fields[$Model->name.'.'.$this->settings[$Model->name][$this->settings[$Model->name]['username']]],
							$Model->name . '.' . $this->settings[$Model->name]['password'] = $passwordExpression
						),
						'fields' => array(
							'id'),
						'recursive' => -1,
						'avoidRecursion' => true)
				);

				if (!empty($user_id)) {
					$fields[$Model->name.'.id'] = $user_id[$Model->name]['id'];
					unset($fields[$Model->name.'.'.$this->settings[$Model->name]['password']], $fields[$Model->name.'.'.$this->settings[$Model->name]['username']]);
				}
			}
			return $fields;
		}

		function hashPasswords(&$data, $alias) {
			if (isset($data[$alias]['password'])) {
				$Model->data = $data;
				$Model->data[$alias][$this->settings[$alias]['field']] = Security::hash(String::uuid(), null, true);
				$Model->data[$alias][$this->settings[$Model->name]['password']] = Security::hash($data[$alias][$this->settings[$Model->name]['password']], null, true);
				return $Model->data;
			}
			return $data;
		}

	}

?>