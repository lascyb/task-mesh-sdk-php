<?php

namespace taskmesh\sdk;

/**
 * Client 任务埋点 HTTP 客户端
 */
class Client
{
    protected HttpTransport $transport;

    /**
     * Client 初始化
     *
     * @param string $baseUrl 服务根地址，如 https://task.example.com
     * @param string $token   Worker Token，对应请求头 X-Task-Token
     * @param int    $timeout 请求超时秒数
     */
    public function __construct(
        protected string $baseUrl,
        protected string $token,
        int $timeout = 10,
        ?HttpTransport $transport = null
    ) {
        $this->transport = $transport ?? new HttpTransport($timeout);
    }

    /**
     * runStart 开始一次任务执行
     */
    public function runStart(
        string $taskKey,
        string $runId,
        string $triggerType = 'manual',
        ?string $scheduledTime = null,
        ?string $startTime = null,
        string $workerId = '',
        mixed $inputPayload = null,
        string $message = '',
        mixed $data = null
    ): array {
        return $this->post('runStart', $this->omitNull([
            'task_key'       => $taskKey,
            'run_id'         => $runId,
            'trigger_type'   => $triggerType,
            'scheduled_time' => $scheduledTime,
            'start_time'     => $startTime,
            'worker_id'      => $workerId,
            'input_payload'  => $inputPayload,
            'message'        => $message,
            'data'           => $data,
        ]));
    }

    /**
     * runFinish 结束一次任务执行，status:{success|failed|timeout|skipped}
     */
    public function runFinish(
        string $runId,
        string $status,
        ?string $endTime = null,
        ?int $durationMs = null,
        mixed $result = null,
        string $errorMessage = ''
    ): array {
        return $this->post('runFinish', $this->omitNull([
            'run_id'        => $runId,
            'status'        => $status,
            'end_time'      => $endTime,
            'duration_ms'   => $durationMs,
            'result'        => $result,
            'error_message' => $errorMessage,
        ]));
    }

    /**
     * event 写入单条埋点，eventType:{start|step|log|warning|error|metric}
     */
    public function event(
        string $runId,
        string $eventType,
        string $eventName = '',
        string $message = '',
        mixed $data = null,
        ?int $costMs = null,
        ?string $eventTime = null
    ): array {
        return $this->post('event', $this->omitNull([
            'run_id'     => $runId,
            'event_type' => $eventType,
            'event_name' => $eventName,
            'message'    => $message,
            'data'       => $data,
            'cost_ms'    => $costMs,
            'event_time' => $eventTime,
        ]));
    }

    /**
     * events 批量写入埋点
     *
     * @param EventPayload[] $events
     */
    public function events(string $runId, array $events): array
    {
        $items = [];
        foreach ($events as $event) {
            if (!$event instanceof EventPayload) {
                throw new Exception('events item must be EventPayload');
            }
            $items[] = $event->toArray();
        }

        return $this->post('events', [
            'run_id' => $runId,
            'events' => $items,
        ]);
    }

    /**
     * run 创建一次执行上下文，便于 Worker 串联 start/finish/event
     */
    public function run(
        string $taskKey,
        ?string $runId = null,
        string $triggerType = 'manual',
        string $workerId = '',
        ?string $scheduledTime = null
    ): Run {
        return new Run($this, $taskKey, $runId, $triggerType, $workerId, $scheduledTime);
    }

    /**
     * post 发送 POST 请求
     */
    protected function post(string $action, array $data): array
    {
        $url = rtrim($this->baseUrl, '/') . '/api/task.Track/' . $action;
        $body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            throw new Exception('埋点请求序列化失败');
        }

        $response = $this->transport->post($url, [
            'X-Task-Token' => $this->token,
            'Content-Type' => 'application/json; charset=utf-8',
            'Accept'       => 'application/json',
        ], $body);

        $result = json_decode($response['body'], true);
        if (!is_array($result)) {
            throw new Exception('埋点响应解析失败', $response['status']);
        }

        if ((int)($result['code'] ?? 0) !== 1) {
            $msg = trim((string)($result['msg'] ?? ''));
            if ($msg === '') {
                $msg = '埋点请求失败';
            }
            $msg .= ' response=' . json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            throw new Exception($msg, (int)($result['code'] ?? 0));
        }

        return is_array($result['data'] ?? null) ? $result['data'] : [];
    }

    /**
     * omitNull 去掉值为 null 的字段
     */
    protected function omitNull(array $data): array
    {
        return array_filter($data, static fn($value) => $value !== null);
    }
}
