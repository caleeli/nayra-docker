<?php

namespace ProcessMaker\NayraService\Models;

use ProcessMaker\Nayra\Bpmn\ActivitySubProcessTrait;
use ProcessMaker\Nayra\Contracts\Bpmn\CallActivityInterface;

class CallActivity implements CallActivityInterface
{
    use ActivitySubProcessTrait;

    protected function getBpmnEventClasses()
    {
        return [];
    }

    public function getCalledElement()
    {
        $this->getProperty(self::BPMN_PROPERTY_CALLED_ELEMENT);
    }

    public function setCalledElement($callableElement)
    {
        $this->setProperty(self::BPMN_PROPERTY_CALLED_ELEMENT, $callableElement);
    }
}
