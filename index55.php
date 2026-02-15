<?php

if (!isset($_GET['url']) || empty($_GET['url'])) {
    http_response_code(400);
    ech "Url is missing!";
    exit();}

$url = $_GET['url'];
$source = file_get_contents($url);
$titles = [];
$durations = [];


preg_match_all(
    '/"title"\s*:\s*\{"runs"\s*:\s*\[\{"text"\s*:\s*"([^"]+)"\}\]\s*,"accessibility"/u',
    $source,
    $matches
);

if (!empty($matches[1])) {
    $titles = $matches[1];
};
// '/"simpleText"\s*:\s*"(\d{1,2}:\d{2}(?::\d{2})?)"/u',
preg_match_all(
  '/"label":"(\d+)\s*minutes?,\s*(\d+)\s*seconds?"/',
  $text,
  $durationMatches2);
if (!empty($durationMatches2)) {
    $durations2 = $durationMatches2[1] .':'. $durationMatches2[2];}


preg_match_all(
  '/"simpleText"\s*:\s*"(\d{1,2}:\d{2}(?::\d{2})?)"\s*},\s*"style"/u',
  $source,
  $durationMatches);


if (!empty($durationMatches[1])) {
    $durations = $durationMatches[1];
};

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
    "durations2" => $durations2,
    ];

echo json_encode($response,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);


?>
