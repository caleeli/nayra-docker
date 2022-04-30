<?php

namespace ProcessMaker\NayraService;

use ProcessMaker\Nayra\Bpmn\ObservableTrait;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;
use ProcessMaker\Nayra\Contracts\EventBusInterface;

class EventBus implements EventBusInterface
{
    use ObservableTrait;

    public function listen($events, $listener)
    {
        $this->attachEvent($events, $listener);
    }

    public function hasListeners($eventName)
    {
        return !empty($this->observers[$eventName]);
    }

    public function subscribe($subscriber)
    {
        $this->attachEvent($subscriber->getSubscribedEvents(), $subscriber);
    }

    public function until($event, $payload = [])
    {
    }

    public function dispatch($event, $payload = [], $halt = false)
    {
        // LOG EVENT
        $logged = false;
        foreach ($payload as $token) {
            if ($token instanceof TokenInterface) {
                $this->logTokenEvent($event, $token);
                $logged = true;
                break;
            }
        }
        if (!$logged) {
            foreach ($payload as $instance) {
                if ($instance instanceof ExecutionInstanceInterface) {
                    $this->logInstanceEvent($event, $instance);
                    $logged = true;
                    break;
                }
            }
        }
        if (is_array($payload)) {
            $this->notifyEvent($event, ...$payload);
        } else {
            $this->notifyEvent($event, $payload);
        }
    }

    private function logTokenEvent(string $event, TokenInterface $token)
    {
        $timestamp = microtime(true);
        if (!isset($this->timing[$token->getId()])) {
            $this->timing[$token->getId()] = $timestamp;
        }
        $time = $timestamp - $this->timing[$token->getId()];
        $this->log(
            sprintf(
                '%s %0.3f %0.6f %s %s %s %s',
                date('Y-m-d H:i:s'),
                $timestamp,
                $time,
                $token->getId(),
                $token->getInstance()->getId(),
                $token->getOwnerElement()->getId(),
                $this->camel2snake($event),
            )
        );
    }

    private function logInstanceEvent(string $event, ExecutionInstanceInterface $instance)
    {
        $timestamp = microtime(true);
        if (!isset($this->timing[$instance->getId()])) {
            $this->timing[$instance->getId()] = $timestamp;
        }
        $time = $timestamp - $this->timing[$instance->getId()];
        $this->log(
            sprintf(
                '%s %0.3f %0.6f %s %s %s',
                date('Y-m-d H:i:s'),
                $timestamp,
                $time,
                $instance->getId(),
                $instance->getProcess()->getId(),
                $this->camel2snake($event),
            )
        );
    }

    private function log($text)
    {
        global $log_file, $log_filename;
        error_log($text);
        fwrite($log_file, $text . "\n");
        // Rotate log file
        $log_size = filesize($log_filename);
        if ($log_size > 1000000) {
            fclose($log_file);
            $log_file = fopen($log_filename, 'w');
        }
    }

    private function camel2snake($text)
    {
        return strtoupper(preg_replace('/([a-z])([A-Z])/', '$1_$2', $text));
    }

    public function push($event, $payload = [])
    {
        if (is_array($payload)) {
            $this->notifyEvent($event, ...$payload);
        } else {
            $this->notifyEvent($event, $payload);
        }
    }

    public function flush($event)
    {
    }

    public function forget($event)
    {
    }

    public function forgetPushed()
    {
    }
}
