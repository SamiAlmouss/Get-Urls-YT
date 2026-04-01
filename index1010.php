<?php

if (!isset($_GET['vid']) || empty($_GET['vid'])) {
    http_response_code(400);
    ech "videoID is missing!";
    exit();}

$vid = $_GET['url'];
$url = "https://www.youtube.com/watch?v=$id";
$source = file_get_contents($url);
echo $source;
?>
