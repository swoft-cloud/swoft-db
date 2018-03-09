<?php

namespace Swoft\Db\Event\Listeners;

use Swoft\App;
use Swoft\Bean\Annotation\Listener;
use Swoft\Core\RequestContext;
use Swoft\Db\EntityManager;
use Swoft\Event\AppEvent;
use Swoft\Event\EventHandlerInterface;
use Swoft\Event\EventInterface;

/**
 * Resource release listener
 *
 * @Listener(AppEvent::RESOURCE_RELEASE)
 */
class ResourceReleaseListener implements EventHandlerInterface
{
    /**
     * @param \Swoft\Event\EventInterface $event
     */
    public function handle(EventInterface $event)
    {
        $contextConnects = RequestContext::getContextDataByKey(EntityManager::CONTEXT_CONNECTS, []);
        foreach ($contextConnects ?? [] as $key => $contextConnect) {
            if (!($contextConnect instanceof \SplStack) || $contextConnect->isEmpty()) {
                continue;
            }

            list(, $poolId) = explode('-', $key);

            /* @var \Swoft\Pool\PoolInterface $pool */
            $pool = App::getPool($poolId);

            foreach ($contextConnect ?? [] as $connect) {
                $pool->release($connect);
            }
        }
    }
}