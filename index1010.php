<?php

if (!isset($_GET['vid']) || empty($_GET['vid'])) {
    http_response_code(400);
    echo "videoID is missing!";
    exit();}

$vid = $_GET['vid'];
$url = "https://www.youtube.com/watch?v=$vid";
$source = file_get_contents($url);
echo $source;
?>
