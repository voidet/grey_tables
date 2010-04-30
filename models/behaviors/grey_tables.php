<?php

	class GreyTablesBehavior extends ModelBehavior {
		
		function setup(&$model, $settings = array()) {
			$default = array('field' => 'salt');

			if (!isset($this->settings[$model->name])) {
				$this->settings[$model->name] = $default;
			}

			$this->settings[$model->name] = array_merge($this->settings[$model->name], ife(is_array($settings), $settings, array()));
		}
		
		function beforeFind(&$model, $queryData) {
			if(!empty($queryData['conditions'][$model->name.'.password']) && !empty($queryData['conditions'][$model->name.'.username'])) {
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
				$data['password'] = $this->generateSaltedPassword($data['password'], $data[$this->settings[$model->name]['field']]);
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
				$user_id = $model->query('SELECT `'.$model->name.'`.`id` as \'id\' FROM '.$model->table.' as '.$model->name.' WHERE `'.$model->name.'`.`username` = \''.$fields[$model->name.'.username'].'\' AND `'.$model->name.'`.`password` = SHA1(CONCAT(\''.$fields[$model->name.'.password'].'\', `'.$this->settings[$model->name]['field'].'`)) LIMIT 1');
				if (!empty($user_id)) {
					$fields[$model->name.'.id'] = $user_id[0][$model->name]['id'];
					unset($fields[$model->name.'.password'], $fields[$model->name.'.username']);
				}
			}
			return $fields;
		}
		
		function hashPasswords(&$data, $alias) {
			if (isset($data[$alias]['password'])) {
				$model->data = $data;
				$model->data[$alias][$this->settings[$alias]['field']] = Security::hash(String::uuid(), null, true);
				$model->data[$alias]['password'] = Security::hash($data[$alias]['password'], null, true);
				return $model->data;
			}
			return $data;
		}
		
	}

?>