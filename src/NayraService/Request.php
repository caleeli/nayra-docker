<?php

namespace ProcessMaker\NayraService;

use ProcessMaker\Nayra\Bpmn\Models\DataStore;
use ProcessMaker\Nayra\Engine\ExecutionInstance;
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
     * @var ExecutionInstance
     */
    public $instance;

    /**
     * @var Engine
     */
    public $engine;

    public function __construct($attributes)
    {
        // $this->tokens = $attributes['tokens'];
        // $this->status = $attributes['status'];

        $engine = new Engine();
        $repository = $engine->getRepository();
        $bpmnDoc = new BpmnDocument();
        $bpmnDoc->setEngine($engine);
        $bpmnDoc->setFactory($repository);
        $bpmnDoc->load('./bpmn/' . $attributes['bpmn']);
        $this->bpmn = $bpmnDoc;
        $this->engine = $engine;

        // if (isset($attributes['tokens'])) {
        //     $instance = $repository->createExecutionInstanceRepository()->createExecutionInstance();
        //     $instance->setDataStore($dataStore);
        // }
    }

    public function startProcess(array $params)
    {
        if ($params['process_id']) {
            $dataStore = $this->engine->getRepository()->createDataStore();
            $dataStore->setData($params['data'] ?? []);
            $this->dataStore = $dataStore;
            $process = $this->bpmn->getProcess($params['process_id']);
            $this->instance = $process->call($this->dataStore);
            $this->engine->runToNextState();
        }
    }
}
