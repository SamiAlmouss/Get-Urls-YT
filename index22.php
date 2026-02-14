    echo "ﻱﺮﺟﻯ ﺇﺮﺳﺎﻟ ﺎﻟﺭﺎﺒﻃ ﺏﺎﺴﺘﺧﺩﺎﻣ ?url=";
    exit();}

$url = $_GET['url'];
$source = file_get_contents($url);
$titles = [];
$durations = [];

preg_match_all(
    '/"title"\s*:\s*\{"runs"\s*:\s*\[\{"text"\s*:\s*"([^,

    $source,
    $matches
);


if (!empty($matches[1])) {
    $titles = $matches[1];
};

preg_match_all(
    '/"simpleText"\s*:\s*"(\d{1,2}:\d{2}(?::\d{2})?)"/u' 

    $source,
    $durationMatches
);


index11.php                            10,13           9%
