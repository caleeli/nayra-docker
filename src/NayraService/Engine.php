<?php

namespace ProcessMaker\NayraService;

use ProcessMaker\Nayra\Bpmn\Models\DataStore;
use ProcessMaker\Nayra\Bpmn\Models\ScriptTask;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\EngineInterface;
use ProcessMaker\Nayra\Contracts\EventBusInterface;
use ProcessMaker\Nayra\Contracts\RepositoryInterface;
use ProcessMaker\Nayra\Engine\EngineTrait;

class Engine implements EngineInterface
{
    use EngineTrait;

    private $repository;

    public $transactions = [];

    public function __construct()
    {
        $this->uid = uniqid();
        $this->repository = new Repository();
        $this->dispatcher = new EventBus();
        $this->setDataStore(new DataStore());
        $this->dispatcher->listen(ScriptTaskInterface::EVENT_SCRIPT_TASK_ACTIVATED, function (ScriptTask $task, TokenInterface $token) {
            $task->runScript($token);
            $this->runToNextState();
        });
    }

    public function setRepository(RepositoryInterface $factory)
    {
        $this->repository = $factory;
    }

    public function setDispatcher(EventBusInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    public function getRepository()
    {
        return $this->repository;
    }
}
