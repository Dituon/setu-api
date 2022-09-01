<?php
include("core/function.php");
if (!isset($_GET['type'])) {
    @header('HTTP/1.1 400 Bad Request');
    return;
}

$r18 = !!$_GET['r18'];
switch ($_GET['type']) {
    case "get_url_img":
        printImage($_GET['url']);
        break;
    case "get_pid_img":
        printImageByPid($_GET['pid'], $_GET['page']);
        break;
    case "get_pid_novel":
        printNovel($_GET['pid'], $_GET['page']);
        break;
    case "ranking_img":
        getRankingImage($_GET['r']);
        break;
    case "random_img":
        printRandomImage($_GET['tag'], $r18);
        break;
    case "search_img":
        searchImage($_GET['tag'], $_GET['r'], $r18, $_GET['mode']);
        break;
    case "search_novel":
        searchNovel($_GET['tag'], $_GET['r'], $r18, $_GET['mode']);
        break;
    case "random_voice":
        getVoice($_GET['tag']);
        break;
    case "img_search_img":
        searchImageByImage($_GET['url']);
        break;
}