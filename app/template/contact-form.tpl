@contact = [data-v-component-plugin-contact-form-form]

@contact|prepend = <?php
	$vvveb_is_page_edit = Vvveb\isEditor();
	
	if (isset($_contact_idx)) $_contact_idx++; else $_contact_idx = 0;
	$previous_component = isset($current_component)?$current_component:null;
	$contact = $current_component = $this->_component['plugin_contact_form_form'][$_contact_idx] ?? [];

	$success = isset($contact['success']);
?>

/* input elements */
@contact input|value = 
<?php
	$name = '@@__name__@@';
	$value = '@@__value__@@';
	 if (isset($_POST[$name]) && !$success) {
		$value = $_POST[$name] . $success; 
	 }
	 
	 echo htmlspecialchars($value);
?>


/* textarea elements */
@contact textarea = 
<?php
	$name = '@@__name__@@';
	$value = '@@__value__@@';
	 if (isset($_POST[$name]) && !$success) {
		$value = $_POST[$name]; 
	 }
	 
	 echo htmlspecialchars($value);
?>

