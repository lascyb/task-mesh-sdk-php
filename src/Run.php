<?php

namespace taskmesh\sdk;

/**
 * Run 单次任务执行上下文，封装 runStart / event / runFinish 调用
 */
class Run
{
    protected string $runId;

    /**
     * Run 初始化
     */
    public function __construct(
        protected Client $client,
        protected string $taskKey,
        ?string $runId = null,
        protected string $triggerType = 'manual',
        protected string $workerId = '',
        protected ?string $scheduledTime = null
    ) {
        $this->runId = $runId ?: self::uuid();
    }

    /**
     * getRunId 获取当前执行 ID
     */
    public function getRunId(): string
    {
        return $this->runId;
    }

    /**
     * start 上报执行开始
     */
    public function start(
        ?string $startTime = null,
        mixed $inputPayload = null,
        string $message = '',
        mixed $data = null
    ): array {
        return $this->client->runStart(
            $this->taskKey,
            $this->runId,
            $this->triggerType,
            $this->scheduledTime,
            $startTime,
            $this->workerId,
            $inputPayload,
            $message,
            $data
        );
    }

    /**
     * finish 上报执行结束，status:{success|failed|timeout|skipped}
     */
    public function finish(
        string $status,
        ?string $endTime = null,
        ?int $durationMs = null,
        mixed $result = null,
        string $errorMessage = ''
    ): array {
        return $this->client->runFinish(
            $this->runId,
            $status,
            $endTime,
            $durationMs,
            $result,
            $errorMessage
        );
    }

    /**
     * event 写入单条埋点，eventType:{start|step|log|warning|error|metric}
     */
    public function event(
        string $eventType,
        string $eventName = '',
        string $message = '',
        mixed $data = null,
        ?int $costMs = null,
        ?string $eventTime = null
    ): array {
        return $this->client->event(
            $this->runId,
            $eventType,
            $eventName,
            $message,
            $data,
            $costMs,
            $eventTime
        );
    }

    /**
     * events 批量写入埋点
     *
     * @param EventPayload[] $events
     */
    public function events(array $events): array
    {
        return $this->client->events($this->runId, $events);
    }

    /**
     * step 写入步骤埋点
     */
    public function step(
        string $eventName,
        string $message = '',
        mixed $data = null,
        ?int $costMs = null,
        ?string $eventTime = null
    ): array {
        return $this->event('step', $eventName, $message, $data, $costMs, $eventTime);
    }

    /**
     * log 写入日志埋点
     */
    public function log(
        string $message,
        string $eventName = 'LOG',
        mixed $data = null,
        ?string $eventTime = null
    ): array {
        return $this->event('log', $eventName, $message, $data, null, $eventTime);
    }

    /**
     * warning 写入警告埋点
     */
    public function warning(
        string $message,
        string $eventName = 'WARNING',
        mixed $data = null,
        ?string $eventTime = null
    ): array {
        return $this->event('warning', $eventName, $message, $data, null, $eventTime);
    }

    /**
     * error 写入错误埋点
     */
    public function error(
        string $message,
        string $eventName = 'ERROR',
        mixed $data = null,
        ?string $eventTime = null
    ): array {
        return $this->event('error', $eventName, $message, $data, null, $eventTime);
    }

    /**
     * metric 写入指标埋点
     */
    public function metric(
        string $eventName,
        mixed $data = null,
        ?int $costMs = null,
        ?string $eventTime = null
    ): array {
        return $this->event('metric', $eventName, '', $data, $costMs, $eventTime);
    }

    /**
     * uuid 生成 run_id，UUID v7（时间有序）
     */
    protected static function uuid(): string
    {
        $msec = (int)floor(microtime(true) * 1000);
        $data = random_bytes(16);

        $data[0] = chr(($msec >> 40) & 0xFF);
        $data[1] = chr(($msec >> 32) & 0xFF);
        $data[2] = chr(($msec >> 24) & 0xFF);
        $data[3] = chr(($msec >> 16) & 0xFF);
        $data[4] = chr(($msec >> 8) & 0xFF);
        $data[5] = chr($msec & 0xFF);
        $data[6] = chr((ord($data[6]) & 0x0F) | 0x70);
        $data[8] = chr((ord($data[8]) & 0x3F) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
