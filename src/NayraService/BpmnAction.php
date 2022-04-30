<?php

namespace ProcessMaker\NayraService;

class BpmnAction
{
    public $action;
    public $params;
    /**
     * @var Request
     */
    public $request;

    public function __construct($attributes)
    {
        $this->action = $attributes['action'];
        $this->params = $attributes['params'];
        $this->request = new Request($attributes);
    }

    public function execute()
    {
        switch ($this->action) {
            case 'START_PROCESS':
                $this->request->startProcess($this->params);
                break;
        }
        // return actions
        return [];
    }
}
