<?php

namespace ProcessMaker\NayraService;

use Exception;
use ProcessMaker\Nayra\Bpmn\Models\DataStore;
use ProcessMaker\Nayra\Bpmn\Models\ScriptTask;
use ProcessMaker\Nayra\Contracts\Bpmn\ScriptTaskInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ServiceTaskInterface;
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
    public $lastStoredTransaction = -1;
    public $callbackUrl = '';
    public $uid = '';

    public function __construct()
    {
        $this->uid = uniqid();
        $this->repository = new Repository();
        $this->dispatcher = new EventBus();
        $this->setDataStore(new DataStore());
        $this->dispatcher->listen(ScriptTaskInterface::EVENT_SCRIPT_TASK_ACTIVATED, function (ScriptTask $task, TokenInterface $token) {
            /*$this->asyncAction([
                "bpmn" => $task->getProcess()->getOwnerDocument()->deploy_name,
                "action" => 'RUN_SCRIPT',
                "params" => [
                    "request_id" => $token->getInstance()->getId(),
                    "token_id" => $token->getId(),
                    "element_id" => $token->getOwnerElement()->getId(),
                ],
                'state' => $this->getState(),
                'callback' => $this->callbackUrl,
            ]);*/
            $task->runScript($token);
            $this->runToNextState();
        });
        $this->dispatcher->listen(ServiceTaskInterface::EVENT_SERVICE_TASK_ACTIVATED, function (ServiceTaskInterface $task, TokenInterface $token) {
            // @todo run asynchronously
            $task->run($token);
            $this->runToNextState();
        });
    }

    private function getState() : array
    {
        $requests = [];
        foreach ($this->executionInstances as $instance) {
            //\error_log('entroooo');
            // \error_log('====> ' . $instance->getProperty('status'));
            // if ($instance->getProperty('status') === 'ACTIVE') {
            $tokens = [];
            foreach ($instance->getTokens() as $token) {
                if ($token->getStatus() === 'CLOSED') {
                    continue;
                }
                $tokens[] = array_merge($token->getProperties(), [
                    'id' => $token->getId(),
                    'status' => $token->getStatus(),
                    'index' => $token->getIndex(),
                    'element_id' => $token->getOwnerElement()->getId(),
                ]);
            }
            $requests[] = [
                'id' => $instance->getId(),
                'callable_id' => $instance->getProcess()->getId(),
                'data' => $instance->getDataStore()->getData(),
                'tokens' => $tokens,
            ];
            //}
        }
        \error_log('RUN SCRIPT');
        \error_log(json_encode($requests));
        return [
            'requests' => $requests,
        ];
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

    /**
     * @return Repository
     */
    public function getRepository()
    {
        return $this->repository;
    }

    public function addInstance(Instance $instance)
    {
        $instance->linkToEngine($this);
        $this->executionInstances[] = $instance;
    }

    private function asyncAction(array $context)
    {
        global $instances_path;
        $context['engine_id'] = $this->uid;
        // Save state
        $collaborationId = $this->uid; // $context['state']['requests'][0]['id'];
        $state = [
            'bpmn' => $context['bpmn'],
            'state' => $context['state'],
            //'callback' => $context['callback'],
        ];
        // store transactions
        $sequentialId = intval(microtime(true) * 1e7);
        for ($i=$this->lastStoredTransaction +1, $l=count($this->transactions); $i<$l; $i++) {
            $sequentialId++;
            $transaction = $this->transactions[$i];
            file_put_contents($instances_path . '/' . $collaborationId .'.'. $sequentialId . '.json', json_encode($transaction));
        }
        $context['callback'] = $_ENV['NAYRA_SERVER_URL'] . '/next_state.php?id=' . $collaborationId . '&callback=' . urlencode($context['callback']) . '&deploy_name=' . urlencode($context['bpmn']);
        error_log($context['callback']);
        // Send request to async worker
        $this->asyncHttpRequest($_ENV['NAYRA_SERVER_URL'] . '/actions.php', $context);
    }

    public function asyncHttpRequest($url, array $data)
    {
        // parse $url
        $url = parse_url($url);
        $host = $url['host'];
        $port = $url['port'] ?? 80;
        $path = $url['path'] ?? '/';
        error_log(json_encode($url));
        if (isset($url['query'])) {
            $path .= '?' . $url['query'];
        }
        $body = json_encode($data);
        $fp = fsockopen($host, $port, $errno, $errstr, 30);
        if (!$fp) {
            throw new Exception("Cannot connect to engine");
        } else {
            $out = "POST {$path} HTTP/1.1\r\n";
            $out .= "Host: 127.0.0.1\r\n";
            $out .= "Content-Type: application/json\r\n";
            $out .= "Content-Length: " . strlen($body) . "\r\n";
            $out .= "Connection: Close\r\n\r\n";
            fwrite($fp, $out);
            fwrite($fp, $body);
            fclose($fp);
        }
    }

    private function queueTransactions(array $transactions)
    {
    }
}
