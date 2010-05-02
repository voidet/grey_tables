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

			$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], $settings);
		}

		function beforeFind(&$Model, $queryData) {
			extract($this->settings[$Model->alias]);
			if(!empty($queryData['conditions'][$Model->alias.'.'.$password]) &&
			   !empty($queryData['conditions'][$Model->alias.'.'.$username]) &&
			   (empty($queryData['conditions']['avoidRecursion']) || $queryData['conditions']['avoidRecursion'] !== true)) {
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
				extract($this->settings[$Model->alias]);
				$data = &$Model->data[$Model->alias];
				$password = $this->generateSaltedPassword($data[$password], $data[$field]);
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
				extract($this->settings[$Model->alias]);

				$db =& $Model->getDataSource();
				$passwordField
				$passwordExpression = $db->expression(sprintf('SHA1(CONCAT(%s, %s))',
					$db->name($password),
					$db->name($field),
				));

				$user_id = $Model->find('first', array(
						'conditions' => array(
							$Model->alias.'.'.$username => $fields[$Model->alias.'.'.$username],
							$Model->alias.'.'.$password = $passwordExpression
						),
						'fields' => array(
							'id'
						),
						'recursive' => -1,
						'avoidRecursion' => true
					)
				);

				if (!empty($user_id)) {
					$fields[$Model->alias.'.id'] = $user_id[$Model->alias]['id'];
					unset($fields[$Model->alias.'.'.$password, $fields[$Model->alias.'.'.$username]);
				}
			}
			return $fields;
		}

		function hashPasswords(&$data, $alias) {
			if (isset($data[$alias]['password'])) {
				extract($this->settings[$Model->alias]);
				$Model->data = $data;
				$Model->data[$alias][$field] = Security::hash(String::uuid(), null, true);
				$Model->data[$alias][$password] = Security::hash($data[$alias][$password], null, true);
				return $Model->data;
			}
			return $data;
		}

	}

?>