<?php

namespace ProcessMaker\NayraService;

use ProcessMaker\Nayra\Bpmn\DataStoreTrait;
use ProcessMaker\Nayra\Contracts\Bpmn\DataStoreInterface;

class GlobalDataStore implements DataStoreInterface
{
    use DataStoreTrait;
}
