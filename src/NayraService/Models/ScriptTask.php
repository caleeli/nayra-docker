<?php

namespace ProcessMaker\NayraService\Models;

use Exception;
use ProcessMaker\Nayra\Bpmn\Models\ScriptTask as ModelsScriptTask;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\MultiInstanceLoopCharacteristicsInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;

class ScriptTask extends ModelsScriptTask
{
    /**
     * Runs the ScriptTask
     * @param TokenInterface $token
     */
    public function runScript(TokenInterface $token)
    {
        //if the script runs correctly complete te activity, otherwise set the token to failed state
        if ($this->executeScript($token, $this->getScript())) {
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
    private function executeScript(TokenInterface $token, $script)
    {
        $result = true;
        try {
            $element = $token->getOwnerElement();
            $loop = $element->getLoopCharacteristics();
            $isMulti = $loop instanceof MultiInstanceLoopCharacteristicsInterface && $loop->isExecutable();
            if ($isMulti) {
                $data = $token->getProperty('data', []);
            } else {
                $data = $token->getInstance()->getDataStore()->getData();
            }
            if (substr($script, 0, 5)==='<?php') {
                $newData = eval('?>' . $script);
            } else {
                $newData = eval($script);
            }
            if (gettype($newData) === 'array') {
                $data = array_merge($data, $newData);
                if ($isMulti) {
                    $token->setProperty('data', $data);
                } else {
                    $token->getInstance()->getDataStore()->setData($data);
                }
            }
        } catch (Exception $e) {
            $result = false;
        }
        return $result;
    }
}
