/* input elements */
[data-v-component-plugin-contact-form-form] input|value = 
<?php
	$name = '@@__name__@@';
	$value = '@@__value__@@';
	 if (isset($_POST[$name])) {
		$value = $_POST[$name]; 
	 }
	 
	 echo htmlspecialchars($value);
?>


/* textarea elements */
[data-v-component-plugin-contact-form-form] textarea = 
<?php
	$name = '@@__name__@@';
	$value = '@@__value__@@';
	 if (isset($_POST[$name])) {
		$value = $_POST[$name]; 
	 }
	 
	 echo htmlspecialchars($value);
?>

