<?php

namespace taskmesh\sdk;

/**
 * HttpTransport 基于 ext-curl 的 HTTP 传输层
 */
class HttpTransport
{
    /**
     * HttpTransport 初始化
     *
     * @param int $timeout 请求超时秒数
     */
    public function __construct(protected int $timeout = 10)
    {
    }

    /**
     * post 发送 POST 请求
     *
     * @param string               $url     请求地址
     * @param array<string, string> $headers 请求头
     * @param string               $body    请求体
     *
     * @return array{status: int, body: string}
     */
    public function post(string $url, array $headers, string $body): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new Exception('curl 初始化失败');
        }

        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }

        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => $headerLines,
        ]);

        $response = curl_exec($ch);
        if ($response === false) {
            throw new Exception('HTTP 请求失败: ' . curl_error($ch));
        }

        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        return [
            'status' => $status,
            'body'   => (string)$response,
        ];
    }
}
