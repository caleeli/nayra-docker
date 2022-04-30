<?php

namespace ProcessMaker\NayraService;

use Jchook\Uuid;
use ProcessMaker\Nayra\Bpmn\Collection;
use ProcessMaker\Nayra\Contracts\Bpmn\ActivityInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\CatchEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\CollectionInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\EventBasedGatewayInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\GatewayInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ParticipantInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\StartEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\ThrowEventInterface;
use ProcessMaker\Nayra\Contracts\Bpmn\TokenInterface;
use ProcessMaker\Nayra\Contracts\Engine\ExecutionInstanceInterface;
use ProcessMaker\Nayra\Contracts\Repositories\ExecutionInstanceRepositoryInterface;
use ProcessMaker\Nayra\Contracts\Repositories\StorageInterface;
use ProcessMaker\Nayra\Contracts\Repositories\TokenRepositoryInterface;
use ProcessMaker\Nayra\Contracts\RepositoryInterface;
use ProcessMaker\Nayra\Engine\ExecutionInstanceTrait;
use ProcessMaker\Nayra\RepositoryTrait;
use ProcessMaker\NayraService\Models\CallActivity;
use ProcessMaker\NayraService\Models\FormalExpression;

class Repository implements RepositoryInterface, ExecutionInstanceInterface, ExecutionInstanceRepositoryInterface, TokenRepositoryInterface
{
    use RepositoryTrait;
    use ExecutionInstanceTrait;

    public function createExecutionInstance()
    {
        $request = new Instance();
        $request->setId(Uuid::v4());
        return $request;
    }

    public function createTokenInstance()
    {
        $token = new Token();
        $token->setId(Uuid::v4());
        return $token;
    }

    public function loadTokenByUid($uid)
    {
    }

    public function store(TokenInterface $token, $saveChildElements = false)
    {
    }

    public function persistActivityActivated(ActivityInterface $activity, TokenInterface $token)
    {
        $instance = $token->getInstance();
        $instance->getProcess()->getEngine()->transactions[] = [
            'type' => 'create',
            'entity' => 'task',
            'properties' => [
                'id' => $token->getId(),
                'request_id' => $instance->getId(),
                'element_id' => $token->getOwnerElement()->getId(),
                'status' => $token->getStatus(),
            ],
        ];
    }

    public function persistActivityException(ActivityInterface $activity, TokenInterface $token)
    {
        $instance = $token->getInstance();
        $instance->getProcess()->getEngine()->transactions[] = [
            'type' => 'update',
            'entity' => 'task',
            'id' => $token->getId(),
            'properties' => [
                'element_id' => $token->getOwnerElement()->getId(),
                'status' => $token->getStatus(),
            ],
        ];
    }

    public function persistActivityCompleted(ActivityInterface $activity, TokenInterface $token)
    {
        $instance = $token->getInstance();
        $instance->getProcess()->getEngine()->transactions[] = [
            'type' => 'update',
            'entity' => 'task',
            'id' => $token->getId(),
            'properties' => [
                'element_id' => $token->getOwnerElement()->getId(),
                'status' => $token->getStatus(),
            ],
        ];
        $instance->getProcess()->getEngine()->transactions[] = [
            'type' => 'update',
            'entity' => 'request',
            'id' => $instance->getId(),
            'properties' => [
                'data' => $instance->getDataStore()->getData(),
            ],
        ];
    }

    public function persistActivityClosed(ActivityInterface $activity, TokenInterface $token)
    {
        $instance = $token->getInstance();
        $instance->getProcess()->getEngine()->transactions[] = [
            'type' => 'update',
            'entity' => 'task',
            'id' => $token->getId(),
            'properties' => [
                'element_id' => $token->getOwnerElement()->getId(),
                'status' => $token->getStatus(),
            ],
        ];
    }

    public function persistThrowEventTokenArrives(ThrowEventInterface $event, TokenInterface $token)
    {
    }

    public function persistThrowEventTokenConsumed(ThrowEventInterface $endEvent, TokenInterface $token)
    {
    }

    public function persistThrowEventTokenPassed(ThrowEventInterface $endEvent, TokenInterface $token)
    {
    }

    public function persistGatewayTokenArrives(GatewayInterface $exclusiveGateway, TokenInterface $token)
    {
    }

    public function persistGatewayTokenConsumed(GatewayInterface $exclusiveGateway, TokenInterface $token)
    {
    }

    public function persistGatewayTokenPassed(GatewayInterface $exclusiveGateway, TokenInterface $token)
    {
    }

    public function persistCatchEventTokenArrives(CatchEventInterface $intermediateCatchEvent, TokenInterface $token)
    {
    }

    public function persistCatchEventTokenConsumed(CatchEventInterface $intermediateCatchEvent, TokenInterface $token)
    {
    }

    public function persistCatchEventTokenPassed(CatchEventInterface $intermediateCatchEvent, Collection $consumedTokens)
    {
    }

    public function persistCatchEventMessageArrives(CatchEventInterface $intermediateCatchEvent, TokenInterface $token)
    {
    }

    public function persistCatchEventMessageConsumed(CatchEventInterface $intermediateCatchEvent, TokenInterface $token)
    {
    }

    public function persistStartEventTriggered(StartEventInterface $startEvent, CollectionInterface $tokens)
    {
    }

    public function persistEventBasedGatewayActivated(EventBasedGatewayInterface $eventBasedGateway, TokenInterface $passedToken, CollectionInterface $consumedTokens)
    {
    }

    public function loadExecutionInstanceByUid($uid, StorageInterface $storage)
    {
    }

    public function persistInstanceCreated(ExecutionInstanceInterface $instance)
    {
        $instance->getProcess()->getEngine()->transactions[] = [
            'type' => 'create',
            'entity' => 'request',
            'properties' => [
                'id' => $instance->getId(),
                'process_id' => $instance->getProcess()->getId(),
                'data' => $instance->getDataStore()->getData(),
                'status' => 'ACTIVE',
            ],
        ];
    }

    public function persistInstanceCompleted(ExecutionInstanceInterface $instance)
    {
        $instance->getProcess()->getEngine()->transactions[] = [
            'type' => 'update',
            'entity' => 'request',
            'id' => $instance->getId(),
            'properties' => [
                'data' => $instance->getDataStore()->getData(),
                'status' => 'COMPLETED',
            ],
        ];
    }

    public function persistInstanceCollaboration(ExecutionInstanceInterface $target, ParticipantInterface $targetParticipant, ExecutionInstanceInterface $source, ParticipantInterface $sourceParticipant)
    {
    }

    public function createCallActivity()
    {
        return new CallActivity();
    }

    public function createFormalExpression()
    {
        return new FormalExpression();
    }

    public function createExecutionInstanceRepository()
    {
        return $this;
    }
    public function getTokenRepository()
    {
        return $this;
    }
}
