<?php

	class GreyTablesBehavior extends ModelBehavior {

		function setup(&$Model, $settings = array()) {
			$default = array(
				'field' => 'salt',
				'username' => 'username',
				'password' => 'password'
			);

			if (!isset($this->settings[$Model->alias])) {
				$this->settings[$Model->alias] = $default;
			}

			$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], ife(is_array($settings), $settings, array()));
		}

		function beforeFind(&$Model, $queryData) {
			if(!empty($queryData['conditions'][$Model->alias.'.'.$this->settings[$Model->alias]['password']]) && !empty($queryData['conditions'][$Model->alias.'.'.$this->settings[$Model->alias]['username']]) && (empty($queryData['conditions']['avoidRecursion']) || $queryData['conditions']['avoidRecursion'] !== true)) {
				$user_id = $this->findSaltedUser($Model, $queryData['conditions']);
				if (!empty($user_id)) {
					unset($queryData['conditions']);
					$queryData['conditions'] = $user_id;
				}
			}
			return $queryData;
		}

		function beforeSave(&$Model) {
			if (empty($this->id) && !empty($Model->data[$Model->alias])) {
				$data = &$Model->data[$Model->alias];
				$data[$this->settings[$Model->alias]['password']] = $this->generateSaltedPassword($data[$this->settings[$Model->alias]['password']], $data[$this->settings[$Model->alias]['field']]);
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
					$db->name($this->settings[$Model->alias]['password']),
					$db->name($this->settings[$Model->alias]['field']),
				));

				$user_id = $Model->find('first', array(
						'conditions' => array(
							$Model->alias.'.'.$this->settings[$Model->alias][$this->settings[$Model->alias]['username']] => $fields[$Model->alias.'.'.$this->settings[$Model->alias][$this->settings[$Model->alias]['username']]],
							$Model->alias . '.' . $this->settings[$Model->alias]['password'] = $passwordExpression
						),
						'fields' => array(
							'id'),
						'recursive' => -1,
						'avoidRecursion' => true)
				);

				if (!empty($user_id)) {
					$fields[$Model->alias.'.id'] = $user_id[$Model->alias]['id'];
					unset($fields[$Model->alias.'.'.$this->settings[$Model->alias]['password']], $fields[$Model->alias.'.'.$this->settings[$Model->alias]['username']]);
				}
			}
			return $fields;
		}

		function hashPasswords(&$data, $alias) {
			if (isset($data[$alias]['password'])) {
				$Model->data = $data;
				$Model->data[$alias][$this->settings[$alias]['field']] = Security::hash(String::uuid(), null, true);
				$Model->data[$alias][$this->settings[$Model->alias]['password']] = Security::hash($data[$alias][$this->settings[$Model->alias]['password']], null, true);
				return $Model->data;
			}
			return $data;
		}

	}

?>