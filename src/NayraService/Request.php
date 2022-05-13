<?php

namespace ProcessMaker\NayraService;

use ProcessMaker\Nayra\Bpmn\Models\DataStore;
use ProcessMaker\Nayra\Storage\BpmnDocument;

class Request
{
    /**
     * @var BpmnDocument
     */
    public $bpmn;
    /**
     * @var DataStore
     */
    public $dataStore;

    /**
     * @var Instance[]
     */
    public $instances;

    /**
     * @var Token[]
     */
    private $tokens = [];

    /**
     * @var Engine
     */
    public $engine;

    public function __construct($attributes)
    {
        $engine = new Engine();
        if (isset($attributes['engine_id'])) {
            $engine->engine_id = $attributes['engine_id'];
        }
        $repository = $engine->getRepository();
        $bpmnDoc = new BpmnDocument();
        $bpmnDoc->setEngine($engine);
        $bpmnDoc->setFactory($repository);
        $bpmnDoc->load('./bpmn/' . $attributes['bpmn']);
        $bpmnDoc->deploy_name = $attributes['bpmn'];
        $this->bpmn = $bpmnDoc;
        $this->engine = $engine;

        if (isset($attributes['callback'])) {
            $this->engine->callbackUrl = $attributes['callback'];
        }

        if (isset($attributes['transactions'])) {
            $state = $attributes['state'];
            foreach ($attributes['transactions'] as $transaction) {
                switch ($transaction['type']) {
                    case 'create':
                        switch ($transaction['entity']) {
                            case 'request':
                                $state['requests'] = $transaction['data'];
                                break;

                            case 'token':
                                # code...
                                break;
                        }
                        break;
                }
            }
        }
        if (isset($attributes['state'])) {
            $requests = $attributes['state']['requests'] ?? [];
            foreach ($requests as $request) {
                $process = $bpmnDoc->getProcess($request['callable_id']);
                $dataStore = $this->engine->getRepository()->createDataStore();
                $dataStore->setData($request['data'] ?? []);
                $instance = $repository->createExecutionInstance();
                $this->engine->loadProcess($process);
                $instance->setDataStore($dataStore);
                $instance->setProcess($process);
                $instance->setId($request['id']);
                $process->addInstance($instance);
                $this->engine->addInstance($instance);
                // Add tokens
                $tokens = $request['tokens'] ?? [];
                foreach ($tokens as $token) {
                    $tokenInstance = $repository->createTokenInstance();
                    $tokenInstance->setProperties($token ?? []);
                    $element = $bpmnDoc->findElementById($token['element_id'])->getBpmnElementInstance();
                    $tokenInstance->setInstance($instance);
                    $element->addToken($instance, $tokenInstance);
                    $this->tokens[$tokenInstance->getId()] = $tokenInstance;
                }
                $this->instances[$instance->getId()] = $instance;
            }
        }
    }

    public function startProcess(array $params)
    {
        if (isset($params['process_id'])) {
            $dataStore = $this->engine->getRepository()->createDataStore();
            $dataStore->setData($params['data'] ?? []);
            $this->dataStore = $dataStore;
            $process = $this->bpmn->getProcess($params['process_id']);
            $instance = $process->call($this->dataStore);
            $instance->setProperty('extra_properties', $params['extra_properties'] ?? []);
            $this->engine->runToNextState();
        }
        if (isset($params['element_id'])) {
            $dataStore = $this->engine->getRepository()->createDataStore();
            $dataStore->setData($params['data'] ?? []);
            $this->dataStore = $dataStore;
            $start = $this->bpmn->getStartEvent($params['element_id']);
            $process = $start->getProcess();
            $instance = $this->engine->createExecutionInstance($process, $dataStore);
            $instance = $start->start($instance);
            $this->engine->runToNextState();
        }
    }

    public function completeTask(array $params)
    {
        if (isset($params['element_id'])) {
            $token = $this->tokens[$params['token_id']];
            $task = $this->bpmn->getActivity($params['element_id']);
            foreach ($params['data'] as $key => $value) {
                $token->getInstance()->getDataStore()->putData($key, $value);
            }
            $this->engine->runToNextState();
            $task->complete($token);
            $this->engine->runToNextState();
        }
    }

    public function runScript(array $params)
    {
        if (isset($params['element_id'])) {
            $token = $this->tokens[$params['token_id']];
            $task = $this->bpmn->getScriptTask($params['element_id']);
            $task->runScript($token);
            $this->engine->runToNextState();
        }
    }

    public function nextState(array $params)
    {
        if (isset($params['transactions'])) {
            foreach ($params['transactions'] as $transaction) {
            }
        }
        $this->engine->runToNextState();
    }

    public function callback(array $params)
    {
        $this->engine->callback($params);
    }
}
