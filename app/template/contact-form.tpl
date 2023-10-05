/* input elements */
[data-v-component-plugin-contact-form-form] input|value = 
<?php
	$name = '@@__name__@@';
	$value = '@@__value__@@';
	 if (isset($_POST[$name])) 
		echo $_POST[$name]; 
	 else if (isset($_GET[$name])) 
		echo $_GET[$name]; 
	 else echo $value;
?>


/* textarea elements */
[data-v-component-plugin-contact-form-form] textarea = 
<?php
	$name = '@@__name__@@';
	$value = '@@__value__@@';
	 if (isset($_POST[$name])) 
		echo $_POST[$name]; 
	 else if (isset($_GET[$name])) 
		echo $_GET[$name]; 
	 else echo $value;
?>

