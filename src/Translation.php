<?php

/*
 * This file is part of the sayhe110/translation
 *
 * (c) sayhe110 <949426374@qq.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sayhe110\EasyTranslation;

use GuzzleHttp\Client;
use Sayhe110\EasyTranslation\Exceptions\InvalidArgumentException;
use Sayhe110\EasyTranslation\Exceptions\HttpException;
use Sayhe110\EasyTranslation\Handle\LanguageType;

class Translation
{
    protected $key;

    protected $appid;

    protected $guzzleOptions = [];

    public function __construct(string $key, string $appid)
    {
        $this->key = $key;
        $this->appid = $appid;
    }

    public function getHttpClient()
    {
        return new Client($this->guzzleOptions);
    }

    public function setGuzzleOptions(array $options)
    {
        $this->guzzleOptions = $options;
    }

    public function translation($text, $from = 'auto', $to = 'en', $canHttps = false)
    {
        if (empty($text)) {
            throw new InvalidArgumentException('Invalid translation text');
        }

        // 检查翻译源语言是否在翻译范围之内
        if ('auto' != $from) {
            (new LanguageType($from))->checkLanguage();
        }

        // 检查译文语言是否在翻译范围之内
        (new LanguageType($to))->checkLanguage();

        $url = $canHttps ?
            'http://api.fanyi.baidu.com/api/trans/vip/translate' :
            'https://fanyi-api.baidu.com/api/trans/vip/translate';

        $salt = \random_int(10000, 99999).time();

        // 根据文档生成 sign
        $sign = md5($this->appid.$text.$salt.$this->key);

        $query = array_filter([
            'q' => $text,
            'from' => $from,
            'to' => $to,
            'appid' => $this->appid,
            'salt' => $salt,
            'sign' => $sign,
        ]);

        try {
            $response = $this->getHttpClient()->get($url, [
                'query' => $query,
            ])->getBody()->getContents();

            // todo 先只返回 json
            return \json_decode($response, true);
        } catch (\Exception $e) {
            throw new HttpException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
