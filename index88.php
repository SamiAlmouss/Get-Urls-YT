<?php

set_time_limit(0);

$playlistId = "PLAYLIST_ID";

if (!isset($_GET["url"])){
     echo "url is missing !!";
     die();

}
$playlistUrl = $_GET["url"];
$videos = [];
$delaySeconds = 1; // delay بين الطلبات
$maxRetries = 3;
function extractContinuationToken($continuationItemRenderer) {
    // المسار الثاني (الأبسط)
    $token = $continuationItemRenderer
        ['continuationEndpoint']
        ['continuationCommand']
        ['token'] ?? null;
    
    if ($token) return $token;
    
    // المسار الأول (commandExecutorCommand)
    $commands = $continuationItemRenderer
        ['continuationEndpoint']
        ['commandExecutorCommand']
        ['commands'] ?? [];
    
    foreach ($commands as $cmd) {
        if (isset($cmd['continuationCommand']['token'])) {
            return $cmd['continuationCommand']['token'];
        }
    }
    
    return null;
}
function curlRequest($url, $postData = null, $headers = [])
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0");
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($postData !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
    }

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new Exception("cURL Error: " . $error);
    }

    curl_close($ch);
    return $response;
}

try {

    // 1️⃣ جلب الصفحة الأولى
    $html = file_get_contents($playlistUrl);

    if (!$html) {
        throw new Exception("فشل تحميل الصفحة الأولى");
    }

    // 2️⃣ استخراج API KEY
    if (!preg_match('/"INNERTUBE_API_KEY":"([^"]+)"/', $html, $keyMatch)) {
        throw new Exception("لم يتم العثور على API KEY");
    }
    $apiKey = $keyMatch[1];

    // 3️⃣ استخراج CONTEXT
    if (!preg_match('/"INNERTUBE_CONTEXT":({.*?})/', $html, $contextMatch)) {
        throw new Exception("لم يتم العثور على CONTEXT");
    }
    $context = json_decode($contextMatch[1], true);

// تأكد أن client مضبوط
$context['client'] = [
    "hl" => "en",
    "gl" => "US",
    "clientName" => "WEB",
    "clientVersion" => $context['client']['clientVersion'] ?? "2.20260225.01.00"
];

// مهم جداً
if (preg_match('/"VISITOR_DATA":"([^"]+)"/', $html, $v)) {
    $context['client']['visitorData'] = $v[1];
}

    if (preg_match('/var\s+ytInitialData\s*=\s*/', $html, $m, PREG_OFFSET_CAPTURE)) {

    $start = $m[0][1] + strlen($m[0][0]);
    $braceCount = 0;
    $length = strlen($html);

    for ($i = $start; $i < $length; $i++) {
        if ($html[$i] === '{') $braceCount++;
        if ($html[$i] === '}') $braceCount--;
        if ($braceCount === 0) break;
    }

    $jsonString = substr($html, $start, $i - $start + 1);
    $initialData = json_decode($jsonString, true);
}







    // 5️⃣ استخراج فيديوهات الصفحة الأولى
    $playlistVideos =
        $initialData['contents']
        ['twoColumnBrowseResultsRenderer']
        ['tabs'][0]
        ['tabRenderer']
        ['content']
        ['sectionListRenderer']
        ['contents'][0]
        ['itemSectionRenderer']
        ['contents'][0]
        ['playlistVideoListRenderer']
        ['contents'];

    foreach ($playlistVideos as $item) {
        if (isset($item['playlistVideoRenderer'])) {
            $lengthText = $item['playlistVideoRenderer']['lengthText']['simpleText'] ?? null;
            $videos[] = [
                "id" => $item['playlistVideoRenderer']['videoId'],
                "title" => $item['playlistVideoRenderer']['title']['runs'][0]['text'],
                "duration" => $lengthText
            ];
        }
    }

    // 6️⃣ استخراج أول continuation
   $continuation = null;

foreach ($playlistVideos as $item) {
    if (isset($item['continuationItemRenderer'])) {
        $continuation = extractContinuationToken($item['continuationItemRenderer']);
    }
}
    // 7️⃣ حلقة جلب باقي الفيديوهات
    while (!empty($continuation)) {
      
        sleep($delaySeconds);

        $postData = [
            "context" => $context,
            "continuation" => $continuation
        ];

        $retryCount = 0;
        $json = null;


        while ($retryCount < $maxRetries) {
            try {
                $response = curlRequest(
    "https://www.youtube.com/youtubei/v1/browse?key=" . $apiKey,
    $postData,
    [
        "Content-Type: application/json",
        "Accept-Language: en-US,en;q=0.9",
        "Origin: https://www.youtube.com",
        "Referer: https://www.youtube.com/",
        "X-YouTube-Client-Name: 1",
        "X-YouTube-Client-Version: " . $context['client']['clientVersion']
    ]
);

                $json = json_decode($response, true);
               

                if (!$json) {
                    throw new Exception("JSON غير صالح");
                }

                break;

            } catch (Exception $e) {
                $retryCount++;
                sleep(2);
            }
        }

        if (!$json) {
            throw new Exception("فشل بعد عدة محاولات");
        }

       $items = [];

if (isset($json['onResponseReceivedActions'][0]['appendContinuationItemsAction']['continuationItems'])) {
    $items = $json['onResponseReceivedActions'][0]['appendContinuationItemsAction']['continuationItems'];
}
elseif (isset($json['onResponseReceivedEndpoints'][0]['appendContinuationItemsAction']['continuationItems'])) {
    $items = $json['onResponseReceivedEndpoints'][0]['appendContinuationItemsAction']['continuationItems'];
}
elseif (isset($json['continuationContents']['playlistVideoListContinuation']['contents'])) {
    $items = $json['continuationContents']['playlistVideoListContinuation']['contents'];
}

$continuation = null;

foreach ($items as $item) {
    if (isset($item['playlistVideoRenderer'])) {
        $lengthText = $item['playlistVideoRenderer']['lengthText']['simpleText'] ?? null;
        $videos[] = [
            "id" => $item['playlistVideoRenderer']['videoId'],
            "title" => $item['playlistVideoRenderer']['title']['runs'][0]['text'],
            "duration" => $lengthText
        ];
    }

    if (isset($item['continuationItemRenderer'])) {
        $continuation = extractContinuationToken($item['continuationItemRenderer']);
    }

    if (isset($item['itemSectionRenderer'])) {
        $continuation = null;
        break;
    }
}

/*
أحياناً يكون continuation هنا
*/
if (!$continuation && isset($json['continuationContents']['playlistVideoListContinuation']['continuations'][0]['nextContinuationData']['continuation'])) {
    $continuation =
        $json['continuationContents']['playlistVideoListContinuation']['continuations'][0]['nextContinuationData']['continuation'];
}
    }

  /*

    echo "تم جلب " . count($videos) . " فيديو" . "<br>";
    echo "===================================================<br>";

           
    foreach ($videos as $video) {
        echo "<br>" . $video['id'] . " - " . $video['title'] . " - " . $video['duration'] . "<br>" ;
    }
*/
header("Content-Type:application/json;charset=UTF-8");
$response = $videos;
     
     /*[
    "Count" => count($videos),
    "IDs" => $IDs,
    "Thumbnails" => $THUMBNAILs,
    "URLs" => $URLs,
    "titles" => $titles,
    "durations" => $durations,
    ];*/

echo json_encode($response,JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo "خطأ: " . $e->getMessage();
}
