<?php
$directory = 'Output';
$filename = $directory . DIRECTORY_SEPARATOR . 'issues-' . time() . '.csv';
$config = getConfig();

if (empty($config['orgName']) || empty($config['repoName'])) {
    die('OrName and RepoName cannot be empty, please set it in config.php');
} else if (!is_writable($directory)) {
    die('Folder is not writable');
}

$headers = array();
if (!empty($config['auth_token'])) {
    $headers[] = 'Authorization: ' . $config['auth_token'];
}

$apiUrl = 'https://api.github.com/repos/' . $config['orgName'] . '/' . $config['repoName'] . '/issues';
$page = 1;
$limit = 10000;
$onlyIssues = true;
$params = array(
    'page' => $page,
    'per_page' => $limit,
    'sort' => 'created',
    'direction' => 'desc',
    'state' => 'all',
);


$startDate = date('Y-m-d');
$endDate = date('Y-m-d',strtotime('-365 days'));
$keepFetching = true;

$i = 0;

while ($keepFetching) {
    $url = $apiUrl . '?' . http_build_query($params);
    $response = makeCurlRequest($url, $headers);
    $data = json_decode($response, true);
    if (empty($data)) {
        echo 'Breaking due to no data' . PHP_EOL;
        break;
    } else if (!empty($data['message'])) {
        echo $data['message'] . PHP_EOL;
        break;
    } else if (!empty($curlInfo['http_code']) && $curlInfo['http_code'] != 200) {
        echo 'Curl Error: Invalid HTTP code: ' . $curlInfo['http_code'];
        break;
    }

    if ($i == 0) {
        $fp = fopen($filename, 'wb');
        fputcsv($fp, array('Issue No', 'Title', 'Created By', 'label', 'is_issue', 'state', 'created_at', 'updated_at'));
    }
    $i++;
    foreach ($data as $value) {
        if (!empty($startDate) && strtotime($value['created_at']) > strtotime($startDate)) {
            continue;
        }
        if (strtotime($value['created_at']) < strtotime($endDate)) {
            $keepFetching = false;
            break;
        }
        $isIssue = (empty($value['pull_request']['url']));
        if ($onlyIssues && !$isIssue) {
            continue;
        }
        if (!empty($value['labels'])) {
            foreach ($value['labels'] as $label) {
                fputcsv($fp, array(
                    $value['number'],
                    $value['title'],
                    $value['user']['login'],
                    $label['name'],
                    $isIssue,
                    $value['state'],
                    $value['created_at'],
                    $value['updated_at'],
                ));
            }
        } else {
            fputcsv($fp, array(
                $value['number'],
                $value['title'],
                $value['user']['login'],
                '',
                $isIssue,
                $value['state'],
                $value['created_at'],
                $value['updated_at'],
            ));
        }
    }
    $page++;
    $params['page'] = $page;
    usleep(mt_rand(100, 400));
}

if (isset($fp)) {
    fclose($fp);
    echo $filename . PHP_EOL;
}

function makeCurlRequest($url, $headers = array())
{
    global $curlInfo, $curlErrorMessage;
    $curlInfo = array();
    // Initialize a CURL session.
    $ch = curl_init();

// Return Page contents.
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

//grab URL and pass it to the variable.
    curl_setopt($ch, CURLOPT_URL, $url);

    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HEADER, $headers);
    }

    $agent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1)';
    curl_setopt($ch, CURLOPT_USERAGENT, $agent);

    $result = curl_exec($ch);

    $curlErrorMessage = curl_error($ch);

    $curlInfo = curl_getinfo($ch);

    curl_close($ch);

    return $result;
}

function getConfig()
{
    $config = array();
    $filePath = 'Config' . DIRECTORY_SEPARATOR . 'config.php';
    if (file_exists($filePath)) {
        $config = include($filePath);
    }

    return $config;
}