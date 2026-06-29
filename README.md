# taskmesh/sdk-php

Task Mesh Worker 埋点 SDK（PHP），用于向 Task Mesh 服务上报任务执行生命周期与过程事件。

仓库：[https://github.com/lascyb/task-mesh-sdk-php](https://github.com/lascyb/task-mesh-sdk-php)

## 要求

- PHP >= 8.0.2
- ext-json

## 安装

```bash
composer require taskmesh/sdk-php
```

未发布 Packagist 前，可直接从 GitHub 安装：

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/lascyb/task-mesh-sdk-php"
        }
    ],
    "require": {
        "taskmesh/sdk-php": "^1.0"
    }
}
```

本地 path 开发：

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../task-mesh-sdk-php",
            "options": { "symlink": true }
        }
    ],
    "require": {
        "taskmesh/sdk-php": "@dev"
    }
}
```

## 配置

| 参数 | 说明 |
|------|------|
| `baseUrl` | Task Mesh 服务根地址，如 `http://127.0.0.1:8081` |
| `token` | Worker Token，对应请求头 `X-Task-Token`，与服务端 `[TASK] WORKER_TOKEN` 一致 |

## 快速开始

推荐使用 `Run` 上下文串联一次完整执行：

```php
<?php

use taskmesh\sdk\Client;
use taskmesh\sdk\EventPayload;
use taskmesh\sdk\Exception;

$client = new Client('http://127.0.0.1:8081', 'your-worker-token');

$run = $client->run(
    taskKey: 'sync_orders',
    triggerType: 'cron',
    workerId: 'worker-01',
);

try {
    $run->start();
    $run->step('FETCH', '拉取订单');
    $run->log('处理完成');
    $run->finish('success');
} catch (Exception $e) {
    $run->finish('failed', errorMessage: $e->getMessage());
    throw $e;
}
```

## Client 直接调用

```php
$client = new Client('http://127.0.0.1:8081', 'your-worker-token');

// 开始执行
$client->runStart(
    taskKey: 'sync_orders',
    runId: '550e8400-e29b-41d4-a716-446655440000',
    triggerType: 'cron',
    workerId: 'worker-01',
);

// 单条埋点
$client->event(
    runId: '550e8400-e29b-41d4-a716-446655440000',
    eventType: 'step',
    eventName: 'FETCH',
    message: '拉取订单',
);

// 批量埋点
$client->events('550e8400-e29b-41d4-a716-446655440000', [
    EventPayload::step('FETCH', '拉取订单'),
    EventPayload::log('处理完成'),
]);

// 结束执行，status: success|failed|timeout|skipped
$client->runFinish(
    runId: '550e8400-e29b-41d4-a716-446655440000',
    status: 'success',
);
```

## Run 便捷方法

| 方法 | event_type | 说明 |
|------|------------|------|
| `start()` | — | 上报 runStart |
| `finish($status)` | — | 上报 runFinish |
| `step($name, $message)` | step | 步骤 |
| `log($message)` | log | 日志 |
| `warning($message)` | warning | 警告 |
| `error($message)` | error | 错误 |
| `metric($name, $data)` | metric | 指标 |
| `events([...])` | — | 批量埋点 |

未传 `runId` 时，`Run` 会自动生成 UUID v4。

## API 映射

| SDK 方法 | HTTP |
|----------|------|
| `runStart()` | `POST /api/task.Track/runStart` |
| `runFinish()` | `POST /api/task.Track/runFinish` |
| `event()` | `POST /api/task.Track/event` |
| `events()` | `POST /api/task.Track/events` |

请求头：`X-Task-Token: {token}`，`Content-Type: application/json`。

成功响应：`code === 1`，SDK 返回 `data` 数组；失败抛出 `taskmesh\sdk\Exception`。

## 异常

```php
use taskmesh\sdk\Exception;

try {
    $run->start();
} catch (Exception $e) {
    // $e->getMessage() 服务端错误信息
    // $e->getCode()    业务错误码（如 401、422）
}
```

## License

Apache-2.0
