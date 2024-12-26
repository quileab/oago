<?php
$url = $_GET['url'];
header('Content-Type: image/jpeg, image/png, image/gif, image/webp');
echo file_get_contents($url);
