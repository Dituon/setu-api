# setu-api

很久很久之前写的api, 近期在群友要求下开源

封装了 Pixiv插图/小说, 萌娘百科语音, SauceNAO/trace.moe图片搜索

基于 `php 7.0+`, 可轻易改写为`php 5.6`或更低版本

# 部署

仅需原生`php 7.0+`, 无需安装第三方库

# 请求

请求方式: `GET`

使用 `type` 区分搜索模式

| `type`           | 说明                 | 参数                                                         | 返回      | 平台                     |
|------------------|--------------------|------------------------------------------------------------|---------|------------------------|
| `get_url_img`    | 通过URL获取图片(作为反代服务器) | `url`: 图片地址                                                | `IMAGE` | `Pixiv`                |
| `get_pid_img`    | 通过Pid获取图片          | `pid`: Pid ,<br/> `page`: 页数(默认为`1`)                       | `IMAGE` | `Pixiv`                |
| `get_pid_novel`  | 通过Pid获取小说(不包含系列)   | `pid`: Pid ,<br/> `page`: 页数(默认为`1`)                       | `JSON`  | `Pixiv`                |
| `ranking_img`    | 获取Pixiv日榜图片        | `r`: 排名                                                    | `IMAGE` | `Pixiv`                |
| `random_img`     | 随机色图               | `tag`: 标签,<br/> `r18`(见下文)                                 | `JSON`  | `lolicon.app`          |
| `random_voice`   | 随机角色语音             | `tag`: 标签                                                  | `JSON`  | `萌娘百科`                 |
| `search_img`     | 图片搜索               | `tag`: 标签,<br/> `r`: 排名,<br/> `r18`(见下文) <br/> `mode`(见下文) | `JSON`  | `Pixiv`                |
| `search_novel`   | 小说搜索               | `tag`: 标签,<br/> `r`: 排名,<br/> `r18`(见下文) <br/> `mode`(见下文) | `JSON`  | `Pixiv`                |
| `img_search_img` | 以图搜图               | `url`: 图片地址                                                | `JSON`  | `SauceNAO` `trace.moe` |

### `r18`

可以为空, 空或`0`为全年龄向, 其它数值为`R-18`模式

注意: 程序根据`Pixiv`作品`Tag`判断`r18`, 可能有误判现象

### `mode`

搜索模式(enum)

| `mode`         | `search_img`          | `search_novel`      |
|----------------|-----------------------|---------------------|
| `default` `0`  | 按时间顺序搜索`2000`收藏数以上的作品 | 按时间顺序搜索`50`收藏数以上的作品 |
| `top` `1`      | 按收藏数量顺序搜索             | 按收藏数量顺序搜索           |
| `enhanced` `2` | 按时间顺序搜索`100`收藏数以上的作品  | 按时间顺序搜索`1`收藏数以上的作品  |

# 返回

### `IMAGE`

**content-type**: `image/jpeg`

可直接作为图片处理

### `JSON`

**content-type**: `application/json`

* **`type`**: 类型
* **`r18`**: `null` 或 `R-18` 或 `R-18G`
* **`title`**: 标题
* **`url`**: 资源URL, 用于音频地址, 小说封面等
* **`caption`**:简介, 包含收藏数, 标签等信息
* **`pid`**:Pid (`str`)
* **`page`**:当前页数
* **`content`**:(小说专用) 正文

| `type`          | 说明        | 非空字段                                                 |
|-----------------|-----------|------------------------------------------------------|
| `image`         | 图片        | `r18` `title` `url` `pid` `page`                     |
| `voice`         | 音频        | `url`                                                |
| `anime`         | 图片搜索返回的数据 | `title` `url` `caption`                              |
| `novel-oneshot` | 单篇小说      | `r18` `title` `url` `pid` `page` `caption` `content` |
| `novel-series`  | 系列小说      | `r18` `title` `url` `pid` `page` `caption` `content` |

# 后话

如果此程序和您预期的一样正常工作，请给我一个 `star`

欢迎提交任何请求

交流群: `828350277`
