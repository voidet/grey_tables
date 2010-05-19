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
			if (!empty($queryData['conditions'][$Model->alias.'.'.$password]) &&
			   !empty($queryData['conditions'][$Model->alias.'.'.$username]) &&
			   (empty($queryData['avoidRecursion']) || $queryData['avoidRecursion'] !== true)) {
				$user_id = $this->findSaltedUser($Model, $queryData['conditions']);
				if (!empty($user_id)) {
					unset($queryData['conditions']);
					$queryData['conditions'] = $user_id;
				}
			}
			return $queryData;
		}

		function beforeSave(&$Model) {
			$data = &$Model->data[$Model->alias];
			extract($this->settings[$Model->alias]);
			if (empty($this->id) && !empty($data[$password])) {
				if (empty($data[$field])) {
					$data[$field] = $this->generateSaltString();
				}
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

		function findSaltedUser(&$Model, $fields = array()) {
			if (!empty($fields)) {
				extract($this->settings[$Model->alias]);

				$db =& $Model->getDataSource();
				$passwordExpression = $db->expression(sprintf('SHA1(CONCAT(%s, %s))',
					$db->value($fields[$Model->alias.'.'.$password]),
					$db->name($field)
				));

				$user_id = $Model->find('first', array(
						'conditions' => array(
							$Model->alias.'.'.$username => $fields[$Model->alias.'.'.$username],
							$Model->alias.'.'.$password => $passwordExpression
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
					unset($fields[$Model->alias.'.'.$password], $fields[$Model->alias.'.'.$username]);
				}
			}
			return $fields;
		}

		function hashPasswords(&$data, $alias) {
			extract($this->settings[$alias]);
			if (isset($data[$alias][$password])) {
				$Model->data = $data;
				$Model->data[$alias][$field] = Security::hash($this->generateSaltString(), null, true);
				$Model->data[$alias][$password] = Security::hash($data[$alias][$password], null, true);
				return $Model->data;
			}
			return $data;
		}

	}

?>