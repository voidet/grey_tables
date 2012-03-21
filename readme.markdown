#CakePHP Grey Tables Authenticate Adapter for CakePHP 2.0

The purpose of this behavior is to add security on sensitive data stored within your auth tables. The idea behind Grey Tables is simply a row based salting of password strings, per user. If you are unfamiliar with Rainbow Tables I would recommend reading into what exactly they are over at [Wikipedia - Rainbow Tables](http://en.wikipedia.org/wiki/Rainbow_tables).

##Installation
Install the plugin:

	cd myapp
	git clone git://github.com/voidet/Grey-Tables.git app/Plugin/GreyTables

Next set up Grey Tables to act as your authenticate object by putting this in your beforeFilter:

$this->Auth->authenticate = array(
  'GreyTables.GreyTables' => array('userModel' => 'User')
);

##Logging In

You should now be able to create and login users via the Auth component. If you however do not have any users in the DB table you will need to make a salt string and a password based off that salt. To do so in any component you could do something like this:

	$salt = Security::hash(String::uuid, null, true);
	echo 'Salt String: '.$salt;
	echo 'Password String: '.Security::hash('yourpasswordhere'.$salt, null, true);
