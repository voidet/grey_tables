<?php

	class GreyTablesBehavior extends ModelBehavior {

		function setup(&$model, $settings = array()) {
			$default = array(
				'field' => 'salt',
				'username' => 'username',
				'password' => 'password'
			);

			if (!isset($this->settings[$model->name])) {
				$this->settings[$model->name] = $default;
			}

			$this->settings[$model->name] = array_merge($this->settings[$model->name], ife(is_array($settings), $settings, array()));
		}

		function beforeFind(&$model, $queryData) {
			if(!empty($queryData['conditions'][$model->name.'.'.$this->settings[$model->name]['password']]) && !empty($queryData['conditions'][$model->name.'.'.$this->settings[$model->name]['username']]) && (empty($queryData['conditions']['avoidRecursion']) || $queryData['conditions']['avoidRecursion'] !== true)) {
				$user_id = $this->findSaltedUser($model, $queryData['conditions']);
				if (!empty($user_id)) {
					unset($queryData['conditions']);
					$queryData['conditions'] = $user_id;
				}
			}
			return $queryData;
		}

		function beforeSave(&$model) {
			if (empty($this->id) && !empty($model->data[$model->name])) {
				$data = &$model->data[$model->name];
				$data[$this->settings[$model->name]['password']] = $this->generateSaltedPassword($data[$this->settings[$model->name]['password']], $data[$this->settings[$model->name]['field']]);
			}
			return parent::beforeSave(&$model);
		}

		function generateSaltedPassword($password = '', $saltString) {
			if (!empty($password)) {
				return Security::hash($password.$saltString, null, false);
			}
		}

		function findSaltedUser(&$model, $fields = array()) {
			if (!empty($fields)) {

				$db =& $model->getDataSource();
				$passwordField
				$passwordExpression = $db->expression(sprintf('SHA1(CONCAT(%s, %s))',
					$db->name($this->settings[$model->name]['password']),
					$db->name($this->settings[$model->name]['field']),
				));

				$user_id = $model->find('first', array(
						'conditions' => array(
							$model->name.'.'.$this->settings[$model->name][$this->settings[$model->name]['username']] => $fields[$model->name.'.'.$this->settings[$model->name][$this->settings[$model->name]['username']]],
							$model->name . '.' . $this->settings[$model->name]['password'] = $passwordExpression
						),
						'fields' => array(
							'id'),
						'recursive' => -1,
						'avoidRecursion' => true)
				);

				if (!empty($user_id)) {
					$fields[$model->name.'.id'] = $user_id[$model->name]['id'];
					unset($fields[$model->name.'.'.$this->settings[$model->name]['password']], $fields[$model->name.'.'.$this->settings[$model->name]['username']]);
				}
			}
			return $fields;
		}

		function hashPasswords(&$data, $alias) {
			if (isset($data[$alias]['password'])) {
				$model->data = $data;
				$model->data[$alias][$this->settings[$alias]['field']] = Security::hash(String::uuid(), null, true);
				$model->data[$alias][$this->settings[$model->name]['password']] = Security::hash($data[$alias][$this->settings[$model->name]['password']], null, true);
				return $model->data;
			}
			return $data;
		}

	}

?>