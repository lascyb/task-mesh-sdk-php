<?php

namespace taskmesh\sdk;

/**
 * EventPayload 单条埋点载荷，用于批量 events 接口
 */
class EventPayload
{
    /**
     * EventPayload 初始化，eventType:{start|step|log|warning|error|metric}
     */
    public function __construct(
        protected string $eventType,
        protected string $eventName = '',
        protected string $message = '',
        protected mixed $data = null,
        protected ?int $costMs = null,
        protected ?string $eventTime = null
    ) {
    }

    /**
     * step 步骤埋点
     */
    public static function step(
        string $eventName,
        string $message = '',
        mixed $data = null,
        ?int $costMs = null,
        ?string $eventTime = null
    ): self {
        return new self('step', $eventName, $message, $data, $costMs, $eventTime);
    }

    /**
     * log 日志埋点
     */
    public static function log(
        string $message,
        string $eventName = 'LOG',
        mixed $data = null,
        ?string $eventTime = null
    ): self {
        return new self('log', $eventName, $message, $data, null, $eventTime);
    }

    /**
     * warning 警告埋点
     */
    public static function warning(
        string $message,
        string $eventName = 'WARNING',
        mixed $data = null,
        ?string $eventTime = null
    ): self {
        return new self('warning', $eventName, $message, $data, null, $eventTime);
    }

    /**
     * error 错误埋点
     */
    public static function error(
        string $message,
        string $eventName = 'ERROR',
        mixed $data = null,
        ?string $eventTime = null
    ): self {
        return new self('error', $eventName, $message, $data, null, $eventTime);
    }

    /**
     * metric 指标埋点
     */
    public static function metric(
        string $eventName,
        mixed $data = null,
        ?int $costMs = null,
        ?string $eventTime = null
    ): self {
        return new self('metric', $eventName, '', $data, $costMs, $eventTime);
    }

    /**
     * toArray 转为 API 请求字段
     */
    public function toArray(): array
    {
        return array_filter([
            'event_type' => $this->eventType,
            'event_name' => $this->eventName,
            'message'    => $this->message,
            'data'       => $this->data,
            'cost_ms'    => $this->costMs,
            'event_time' => $this->eventTime,
        ], static fn($value) => $value !== null && $value !== '');
    }
}
