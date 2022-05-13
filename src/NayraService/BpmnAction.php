<?php

namespace ProcessMaker\NayraService;

class BpmnAction
{
    public $action;
    public $params;
    public $callbackUrl;
    /**
     * @var Request
     */
    public $request;

    public function __construct(array $attributes)
    {
        $this->action = $attributes['action'];
        $this->params = $attributes['params'];
        $this->callbackUrl = $attributes['callback'] ?? '';
        $this->request = new Request($attributes);
    }

    public function execute()
    {
        switch ($this->action) {
            case 'START_PROCESS':
                $this->request->startProcess($this->params);
                break;
            case 'COMPLETE_TASK':
                $this->request->completeTask($this->params);
                break;
            case 'RUN_SCRIPT':
                $this->request->runScript($this->params);
                break;
            case 'NEXT_STATE':
                $this->request->nextState($this->params);
                break;
        }
        // return actions
        if ($this->callbackUrl) {
            return $this->request->engine->asyncHttpRequest($this->callbackUrl, $this->request->engine->transactions);
        }
        return [];
    }

    public static function transactionsToState(array $transactions)
    {
        $state = [];
        foreach ($transactions as $transaction) {
            switch ($transaction['type']) {
                case 'create':
                    switch ($transaction['entity']) {
                        case 'request':
                            $state['requests'][$transaction['id']] = [
                                'kjhsdkjashkjd'=>'dddd',
                                'id' => $transaction['id'],
                                'callable_id' => $transaction['properties']['process_id'],
                                'data' => $transaction['properties']['data'],
                                'tokens' => [],
                            ];
                            \error_log(\json_encode($state));
                            break;
                        case 'task':
                            $token = $transaction['properties'];
                            $state['requests'][$transaction['properties']['request_id']]['tokens'][$transaction['id']] = $token;
                            break;
                    }
                    break;
                case 'update':
                    switch ($transaction['entity']) {
                        case 'request':
                            foreach ($transaction['properties'] as $key => $value) {
                                $state['requests'][$transaction['id']][$key] = $value;
                            }
                            break;
                        case 'token':
                            foreach ($transaction['properties'] as $key => $value) {
                                $state['requests'][$transaction['request_id']]['tokens'][$transaction['request_id']][$key] = $value;
                            }
                            break;
                    }
                    break;
            }
        }
        return $state;
    }
}
