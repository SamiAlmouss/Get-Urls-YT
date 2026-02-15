<?php

if (!isset($_GET['url']) || empty($_GET['url'])) {
    http_response_code(400);
    echo "Url is missing!";
    exit();}

$url = $_GET['url'];
$source = file_get_contents($url);
$titles = [];
$durations = [];


preg_match_all(
    '/"title"\s*:\s*\{"runs"\s*:\s*\[\{"text"\s*:\s*"([^"]+)"\}\]\s*,"accessibility"/u',
    $source,
    $matches1
);

if (!empty($matches1[1])) {
    $titles = $matches1[1];
};
//================================================
preg_match_all(
    '/simpleText":"(\d+:\d+)"},"style":"DEFAULT"}/',
    $source,
    $matches2
);

if (!empty($matches2[1])) {
    $durations = $matches2[1];
};

//=========================================================

function getMatches($string, $pattern) {
    preg_match_all($pattern, $string, $matches);
    return $matches[1] ?? [];};
function findYoutube($x){
    $IDs_ = array();
    $URLs_ = array();
    $THUMBNAILs_ = array();
    $matches = array_merge(
        getMatches($x,'#watch\?v=([\w\-]{11})#'),
        getMatches($x,'#https?://youtu\.be/([\w\-]{11})#'));
    foreach ($matches as $id){
        $IDs_[$id] = $id;
        $URLs_[$id] = "https://www.youtube.com/watch?v=$id";
        $THUMBNAILs_[$id] = "https://img.youtube.com/vi/$id/default.jpg";
}
    return [array_values($IDs_),
            array_values($URLs_),
            array_values($THUMBNAILs_)];
}
[$IDs,$URLs,$THUMBNAILs] = findYoutube($source);
header("Content-Type:application/json;charset=UTF-8");
$response = [
    "Count" => count($IDs),
    "IDs" => $IDs,
    "Thumbnails" => $THUMBNAILs,
    "URLs" => $URLs,
    "titles" => $titles,
    "durations" => $durations,
    ];

echo json_encode($response,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);


?>
