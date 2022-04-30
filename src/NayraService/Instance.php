<?php

namespace ProcessMaker\NayraService;

use App\Models\Request as ModelsRequest;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;
use ProcessMaker\Nayra\Engine\ExecutionInstanceTrait;

class Instance implements ExecutionInstanceInterface
{
    use ExecutionInstanceTrait;

    public function getModel(): ModelsRequest
    {
        $model = new ModelsRequest();
        $tokens = [];
        foreach($this->getTokens() as $token) {
            $tokens[] = [
                'id' => $token->getId(),
                'element_id' => $token->getOwnerElement()->getId(),
                'index' => $token->getIndex(),
                'status' => $token->getStatus(),
            ];
        }
        $model->data = (object) $this->getDataStore()->getData();
        $model->tokens = $tokens;
        return $model;
    }
}
