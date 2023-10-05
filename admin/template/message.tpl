import(crud.tpl, {"type":"message"})

[data-v-data]|deleteAllButFirstChild

[data-v-data]|before = <?php 
	foreach ($this->message['message'] as $name => $value) {
?>
	[data-v-data-name]  = $name
	[data-v-data-value] = $value
	
[data-v-data]|after = <?php 
	}
?>

[data-v-meta]|deleteAllButFirstChild
[data-v-meta]|before = <?php 
	foreach ($this->message['meta'] as $name => $value) {
?>
	[data-v-meta-name]  = $name
	[data-v-meta-value] = $value
	
[data-v-meta]|after = <?php 
	}
?>

