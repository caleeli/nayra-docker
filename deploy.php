<?php

$file = $_FILES['file'];
// copy to bpmn folder
$name = $file['name'];
copy($file['tmp_name'], 'bpmn/' . $name);
