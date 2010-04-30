<?php

	class Member extends AppModel {
		
		var $actsAs = array('GreyTables');

		function hashPasswords($data) {
			return $this->Behaviors->GreyTables->hashPasswords($data, $this->alias);
		}
		
	}

?>