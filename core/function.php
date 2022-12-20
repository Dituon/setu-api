<?php
include("config.php");

/**
 * @param $url string 请求url
 * @return string
 */
function sendRequest(string $url): string
{
    global $config;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, $config->pixiv['useragent']);
    curl_setopt($ch, CURLOPT_REFERER, $config->pixiv['referer']);
    curl_setopt($ch, CURLOPT_COOKIE, $config->pixiv['cookie']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
    $r = curl_exec($ch);
    curl_close($ch);
    return $r;
}

/**
 * 打印指定pid插图
 *
 * @param string $id int pid
 * @param $page int 页数, 默认为第1页
 * @return void
 */
function printImageByPid(string $id, int $page = 1)
{
    $url = "https://www.pixiv.net/ajax/illust/$id/pages";
    $response = sendRequest($url);
    $response = json_decode($response, true);
    $response = $response['body'];
    printImage($response[--$page]['urls']['regular']);
}

/**
 * 打印随机色图 **JSON**
 *
 * @param $tag string
 * @param $r18 bool
 * @return void
 */
function printRandomImage(string $tag, bool $r18 = false)
{
    $ch = curl_init();
    $durl = 'https://api.lolicon.app/setu/v2?size=regular&r18=' . $r18 ? 1 : 0 . '&tag=' . urlencode($tag);
    curl_setopt($ch, CURLOPT_URL, $durl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
    $rj = json_decode(curl_exec($ch), true);
    curl_close($ch);

    $imgPid = $rj['data'][0]['pid'];
    settype($imgPid, "string");

    $arr = defaultArray();
    $arr['type'] = 'image';
    $arr['pid'] = $imgPid;
    $arr['url'] = 'https://www.pixiv.net/artworks/' . $rj['data'][0]['pid'];
    $rj['data'][0]['r18'] == true && ($arr['r18'] = 'R-18');
    $arr['title'] = $rj['data'][0]['title'] ?? "null";
    $arr['page'] = '1';
    printJSON($arr);
}

/**
 * 打印指定排名的日榜图片
 *
 * @param $num int 作品排名
 * @return void
 */
function getRankingImage(int $num)
{
    static $updateDate, $rankingList;

    if ($updateDate === date('d')) {
        printImageByPid($rankingList[--$num]);
        return;
    }

    $matches = array();
    $json = json_decode(
        sendRequest('https://www.pixiv.net/ranking.php?mode=daily&content=illust&p=1&format=json'),
    true
    )["contents"];

    foreach ($json as $value) {
        $matches[] = $value['illust_id'];
    }

    $rankingList = $matches;
    printImageByPid($matches[--$num]);
}

/**
 * 搜索并打印小说
 *
 * @param $tag string
 * @param $r int 排行
 * @param $r18 bool
 * @param $searchMode string enum: top / enhanced / default
 * @return void
 */
function searchNovel(string $tag, int $r, bool $r18 = false, string $searchMode = 'default')
{

    $r18 = $r18 ? 'r18' : 'safe';

    switch ($searchMode) {
        case '1':
        case 'top':
            $order = 'popular_male_d';
            $blt = '';
            break;
        case '2':
        case 'enhanced':
            $order = 'date_d';
            $blt = '1';
            break;
        case '0':
        case 'default':
        default:
            $order = 'date_d';
            $blt = '50';
            break;
    }

    $page = ceil($r / 24);
    $r = $r - 1 - ($page - 1) * 24;

    $tag = urlencode($tag);
    $url = 'https://www.pixiv.net/ajax/search/novels/' . $tag . '?word=' . $tag . '&order=' . $order . '&mode=' . $r18 . '&blt=' . $blt . '&p=' . $page . '&s_mode=s_tag&work_lang=zh-cn&gs=1&lang=zh';
    $ru = json_decode(sendRequest($url), true);
    $data = $ru['body']['novel']['data'][$r];

    $novelTitle = $data['title'];
    $novelIsOneshot = $data['isOneshot'];
    $novelIsConcluded = $data['isConcluded'];
    $novelEpisodeCount = $data['publishedEpisodeCount'];
    $novelCollect = $data['bookmarkCount'];
    $novelTags = $data['tags'];
    $novelTextLength = $data['publishedTextLength'];
    $novelCaption = $data['caption'];

    if ($novelIsOneshot == 'true') {
        $novelState = '单篇完结';
        $novelEpisodeCount = '1';
        $novelPid = $data['novelId'];
        $novelURL = 'https://www.pixiv.net/novel/show.php?id=' . $novelPid;
        $novelType = 'novel-oneshot';
    } else {
        $novelPid = $data['id'];
        $novelURL = 'https://www.pixiv.net/novel/series/' . $novelPid;
        $novelState = '系列';
        if ($novelIsConcluded == 'true') {
            $novelState .= '完结';
        } else {
            $novelState .= '更新中';
        }
        $novelType = 'novel-series';
    }

    if ($novelCaption != '') {
        $novelCaption = '\n简介: ' . mb_substr($novelCaption, 0, 60, 'utf-8') . '...';
    }

    settype($novelPid, "string");
    $arr = defaultArray();
    if ($novelTags[0] == 'R-18' || $novelTags[0] == 'R-18G') {
        $arr['r18'] = $novelTags[0];
    }
    $novelTags = '#' . mb_substr(implode(' #', $novelTags), 0, 40, 'utf-8');
    $novelCaption = '标签: ' . $novelTags . '\n收藏数: ' . $novelCollect . '\n共 ' . $novelEpisodeCount . ' 话 ' . $novelTextLength . '字 ('
        . $novelState . ')' . $novelCaption;
    $arr['type'] = $novelType;
    $arr['pid'] = $novelPid;
    $arr['url'] = $novelURL;
    $arr['title'] = $novelTitle;
    $arr['page'] = '1';
    $arr['caption'] = $novelCaption;
    printJSON($arr);
}

/**
 * 搜索图片, **打印JSON**
 *
 * @param $tag string
 * @param $r int 排行
 * @param $r18 bool
 * @param $searchMode string enum: top / enhanced / default
 * @return void
 */
function searchImage(string $tag, int $r, bool $r18 = false, string $searchMode = 'default')
{
    $r18 = $r18 ? 'r18' : 'safe';

    switch ($searchMode) {
        case '1':
        case 'top':
            $order = 'popular_male_d';
            $blt = '';
            break;
        case '2':
        case 'enhanced':
            $order = 'date_d';
            $blt = '100';
            break;
        case '0':
        case 'default':
        default:
            $order = 'date_d';
            $blt = '2000';
            break;
    }

    $page = ceil($r / 60);
    $r = $r - 1 - ($page - 1) * 60;

    $tag = urlencode($tag);
    $url = 'https://www.pixiv.net/ajax/search/artworks/' . $tag . '?word=' . $tag . '&order=' . $order . '&mode=' . $r18 . '&blt=' . $blt . '&p=' . $page . '&s_mode=s_tag&type=all&lang=zh';
    $ru = json_decode(sendRequest($url), true);
    $imgPid = $ru['body']['illustManga']['data'][$r]['id'];

    $imgTitle = $ru['body']['illustManga']['data'][$r]['title'];
    $imgTags = $ru['body']['illustManga']['data'][$r]['tags'];
    $arr = defaultArray();

    if ($imgTags[0] === 'R-18' || $imgTags[0] === 'R-18G') {
        $arr['r18'] = $imgTags[0];
    }
    settype($imgPid, "string");
    $arr['type'] = 'image';
    $arr['pid'] = $imgPid;
    $arr['url'] = 'https://www.pixiv.net/artworks/' . $imgPid;
    $arr['title'] = $imgTitle;
    $arr['page'] = '1';
    printJSON($arr);
}

/**
 * 打印小说
 *
 * @param $pid string
 * @return void
 */
function printNovel(string $pid)
{
    $html = file_get_contents("https://www.pixiv.net/novel/show.php?id=" . $pid);
    $titlere = '/(?<=title\":\").*?(?=\",\"likeCount)/';
    $captionre = '/(?<=description\":\").*?(?=\",\"id)/';
    $contentre = '/(?<=content\":\").*?(?=\",\"coverUrl)/';
    preg_match($titlere, $html, $title);
    preg_match($captionre, $html, $caption);
    preg_match($contentre, $html, $content);

    $arr = defaultArray();
    if (strpos($html, '"tag":"R-18"')) {
        $arr['r18'] = 'R-18';
    } else if (strpos($html[0], '"tag":"R-18G"')) {
        $arr['r18'] = 'R-18G';
    }
    $arr['type'] = 'novel-oneshot';
    $arr['pid'] = $pid;
    $arr['title'] = $title[0];
    $arr['page'] = '1';
    $arr['caption'] = $caption[0];
    $arr['content'] = str_replace("[newpage]", "", $content[0]);
    printJSON($arr);
}

/**
 * 打印音频 **JSON**
 *
 * @param $tag string
 * @return void
 */
function getVoice(string $tag)
{
    $opts = ['http' => ['header' => "Referer: mzh.moegirl.org.cn"]];
    $context = stream_context_create($opts);

    $url = 'https://mzh.moegirl.org.cn/api.php?action=query&format=json&prop=pageprops&generator=prefixsearch&ppprop=displaytitle&gpssearch=' . urlencode($tag);
    $json = json_decode(file_get_contents($url, false, $context), true);

    $arr = defaultArray();
    $arr['type'] = 'voice';

    if ($json['query']['pages'] == NULL) {
        $arr['url'] = 'null';
        printJSON($arr);
        return;
    }

    $title = array_shift($json['query']['pages'])['title'];

    $url = 'https://mzh.moegirl.org.cn/api.php?action=query&format=json&imlimit=100&prop=images&titles=' . urlencode($title);
    $json = json_decode(file_get_contents($url, false, $context), true);

    $resource = array_shift($json['query']['pages'])['images'];
    if ($resource == null) {
        $arr['url'] = 'null';
        printJSON($arr);
        return;
    }

    $voices = null;

    foreach ($resource as $r) {
        $end = substr($r['title'], -4);
        if ($end === '.mp3' || $end === '.ogg') {
            $voices[] = $r['title'];
        }
    }

    if (!$voices) {
        $arr['url'] = 'null';
        printJSON($arr);
        return;
    }
    is_array($voices) ? $res = $voices[random_int(0, sizeof($voices) - 1)] : $res = $voices;

    $url = 'https://mzh.moegirl.org.cn/api.php?action=query&format=json&prop=videoinfo&viprop=url&titles=' . urlencode($res);
    $json = json_decode(file_get_contents($url, false, $context), true);

    $arr['url'] = $json['query']['pages']['-1']['videoinfo'][0]['url'];
    printJSON($arr);
}

/**
 * 以图搜图, **打印JSON**
 *
 * @param string $url
 * @return void
 */
function searchImageByImage(string $url)
{
    global $config;

    $arr = defaultArray();
    $arr['type'] = 'anime';

    $stream_opts = [
        "ssl" => [
            "verify_peer" => false,
            "verify_peer_name" => false,
        ]
    ];

    $apikey = $config->saucenao['apikey'];
    $apiurl = "https://saucenao.com/search.php?db=999&output_type=2&testmode=1&numres=1&api_key=$apikey&url=$url";
    $json = json_decode(file_get_contents($apiurl, false, stream_context_create($stream_opts)), true);
    $similarity = number_format($json['results'][0]['header']['similarity'], 2);
    if ($similarity >= 80) { //saucenao
        $arr['title'] = '数据来源: SauceNAO (' . $similarity . '%)';
        $arr['url'] = $json['results'][0]['header']['thumbnail'];
        $arr['caption'] = $json['results'][0]['data']['jp_name'] ?? '';
        $arr['caption'] .= $json['data']['title_japanese'] ?? '';
        $arr['caption'] .= $json['data']['title'] ?? '';
        if (isset($json['data']['pixiv_id'])) {
            $arr['caption'] .= "\n" . $json['data']['pixiv_id'];
        }
        $ext_urls = $json['results'][0]['data']['ext_urls'][0];
        if (isset($ext_urls)) {
            $arr['caption'] .= '链接: ' . $ext_urls;
        }
        if (isset($json['results'][0]['data']['mal_id'])) {
            $arr['caption'] .= "\n第 " . $json['results'][0]['data']['part'] . ' 话' ?? '';
            $est_time = $json['results'][0]['data']['est_time'];
            if (isset($est_time)) {
                $arr['caption'] .= substr($est_time, 0, strpos($est_time, ' /'));
            }
            //            $url = "https://api.jikan.moe/v4/anime/" . $json['results'][0]['data']['mal_id'];
//            $json = json_decode(file_get_contents($url, false, stream_context_create($stream_opts)), true);
        }
    } else { //trace.moe
        $apiurl = "https://api.trace.moe/search?url=$url";
        $json = json_decode(file_get_contents($apiurl, false, stream_context_create($stream_opts)), true);
        $similarity = number_format($json['result'][0]['similarity'] * 100, 2);
        if ($similarity >= 80) {
            $arr['title'] = '数据来源: trace.moe (' . $similarity . '%)';
            $arr['url'] = $json['result'][0]['image'];
            $arr['caption'] = '链接: ' . $json['result'][0]['anilist'];
        }
    }

    printJSON($arr);
}

/**
 * 将修改后的默认数组打印为json
 *
 * @param $arr array
 * @return void
 */
function printJSON(array $arr)
{
    @header('content-type: application/json');
    echo (json_encode($arr));
}

/**
 * 打印一张网络图片
 *
 * @param $url string 图片地址
 * @return void
 */
function printImage(string $url)
{
    $ImageData = sendRequest($url);
    @header('content-type: image/jpeg');
    echo ($ImageData);
}

/**
 * 获取默认数组
 *
 * @return array
 */
function defaultArray(): array
{
    return array_fill_keys(array('type', 'pid', 'url', 'r18', 'title', 'page', 'caption', 'content'), '');
}