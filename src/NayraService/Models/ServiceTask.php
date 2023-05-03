<?php

namespace ProcessMaker\NayraService\Models;

use Exception;
use ProcessMaker\Nayra\Bpmn\ActivityTrait;
use ProcessMaker\Nayra\Bpmn\Models\ServiceTask as NayraServiceTask;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\MultiInstanceLoopCharacteristicsInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;

class ServiceTask extends NayraServiceTask
{
    use ActivityTrait;

    public function run(TokenInterface $token)
    {
        if ($this->executeService($token)) {
            $this->complete($token);
        } else {
            $token->setStatus(ActivityInterface::TOKEN_STATE_FAILING);
        }
    }

    /**
     * Script runner fot testing purposes that just evaluates the sent php code
     *
     * @param TokenInterface $token
     * @param string $script
     * @return bool
     */
    private function executeService(TokenInterface $token)
    {
        $result = true;
        try {
            $element = $token->getOwnerElement();
            $loop = $element->getLoopCharacteristics();
            $isMulti = $loop instanceof MultiInstanceLoopCharacteristicsInterface && $loop->isExecutable();
            $data = $token->getInstance()->getDataStore()->getData();
            if ($isMulti) {
                $data = array_merge($data, $token->getProperty('data', []));
            }
            $newData = $this->runService($data, $token);
            if (gettype($newData) === 'array') {
                $data = array_merge($data, $newData);
                if ($isMulti) {
                    $token->setProperty('data', $data);
                } else {
                    $token->getInstance()->getDataStore()->setData($data);
                }
            }
        } catch (Exception $e) {
            error_log($e->getMessage());
            $result = false;
        }
        return $result;
    }

    private function runService(array $data, TokenInterface $token)
    {
        $implementation = $this->getImplementation();
        if (class_exists($implementation)) {
            $service = new $implementation();
            return $service->run($data, $token, $this);
        } else {
            throw new Exception("Service implementation not found: $implementation");
        }
    }
}
