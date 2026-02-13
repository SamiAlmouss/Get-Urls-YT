<?php

// التأكد من وجود المتغير url
if (!isset($_GET['url']) || empty($_GET['url'])) {
    http_response_code(400);
    echo "يرجى إرسال الرابط باستخدام ?url=";
    exit;
}

$url = $_GET['url'];

// التحقق من صحة الرابط
if (!filter_var($url, FILTER_VALIDATE_URL)) {
    http_response_code(400);
    echo "الرابط غير صالح";
    exit;
}

// تهيئة cURL
$ch = curl_init();

curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_USERAGENT => 'Mozilla/5.0'
]);

$response = curl_exec($ch);

// معالجة الأخطاء
if (curl_errno($ch)) {
    http_response_code(500);
    echo "خطأ في جلب الصفحة: " . curl_error($ch);
    curl_close($ch);
    exit;
}

curl_close($ch);

$source = $response;

/* ==============================
   استخراج العناوين
================================ */

$results = [];

preg_match_all(
    '/"title"\s*:\s*\{"runs"\s*:\s*\[\{"text"\s*:\s*"([^"]+)"\}\]\s*,"accessibility"/u',
    $source,
    $matches
);

if (!empty($matches[1])) {
    $results = $matches[1];
}

/* ==============================
   استخراج روابط يوتيوب
================================ */

function getMatches($string, $pattern) {
    preg_match_all($pattern, $string, $matches);
    return $matches[1] ?? [];
}

function decodeId($id){
    return $id;
}

function encodeId($id){
    return $id;
}

function findYoutube($x){
    $URLs = array();

    $matches = array_merge(
        getMatches($x,'#watch\?v=([\w\-]{11})#'),
        getMatches($x,'#https?://youtu\.be/([\w\-]{11})#')
    );

    foreach ($matches as $id){
        $id = encodeId(decodeId($id));
        $URLs[] = "https://www.youtube.com/watch?v=$id";
