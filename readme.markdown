#CakePHP Grey Tables Behavior

The purpose of this behavior is to add security on sensitive data stored within your auth tables. The idea behind Grey Tables is simply a row based salting of password strings, per user. If you are unfamiliar with Rainbow Tables I would recommend reading into what exactly they are over at [Wikipedia - Rainbow Tables](http://en.wikipedia.org/wiki/Rainbow_tables).

##Installation
Simply place the grey_tables.php file within your app/models/behaviors/ folder. Depending on which Auth user model you would like to include (i.e user, member, admin, customers), you will need to edit your model. To do so open your model file and include the behavior via:

	var $actsAs = array('GreyTables');

The next method is used by the Auth component to hash the passwords, which Grey Tables will provide a salt string along side with the posted data (including password):

	class Member extends AppModel {

		var $actsAs = array('GreyTables');

		function hashPasswords($data) {
			return $this->Behaviors->GreyTables->hashPasswords($data, $this->alias);
		}

	}

##Logging In

You should now be able to create and login users via the Auth component. If you however do not have any users in the DB table you will need to make a salt string and a password based off that salt. To do so in any component you could do something like this:

	$salt = Security::hash(String::uuid, null, true);
	echo 'Salt String: '.$salt;
	echo 'Password String: '.Security::hash('yourpasswordhere'.$salt, null, true);