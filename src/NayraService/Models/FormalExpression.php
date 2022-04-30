<?php

namespace ProcessMaker\NayraService\Models;

use ProcessMaker\Nayra\Bpmn\FormalExpressionTrait;
use ProcessMaker\Nayra\Contracts\Bpmn\FormalExpressionInterface;

class FormalExpression implements FormalExpressionInterface
{
    use FormalExpressionTrait;

    public function getLanguage()
    {
        return $this->getProperty(self::BPMN_PROPERTY_LANGUAGE);
    }

    public function getEvaluatesToType()
    {
        return $this->getProperty(self::BPMN_PROPERTY_EVALUATES_TO_TYPE_REF);
    }

    public function getBody()
    {
        return $this->getProperty(self::BPMN_PROPERTY_BODY);
    }

    public function __invoke($data)
    {
        extract($data);
        return eval('return ' . $this->getBody() . ';');
    }
}
