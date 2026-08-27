<?php
declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\LeaveApprovedEvent;
use App\Event\LeaveRejectedEvent;
use App\Event\LeaveCancelledEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Subscriber to invalidate dashboard metrics cache when leave status changes.
 */
class DashboardCacheSubscriber implements EventSubscriberInterface
{
    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LeaveApprovedEvent::class => 'onLeaveStatusChanged',
            LeaveRejectedEvent::class => 'onLeaveStatusChanged',
            LeaveCancelledEvent::class => 'onLeaveStatusChanged',
        ];
    }

    public function onLeaveStatusChanged(LeaveApprovedEvent|LeaveRejectedEvent|LeaveCancelledEvent $event): void
    {
        $this->cache->delete('dashboard.pending_leaves_count');
        $this->cache->delete('dashboard.on_leave_today_count');
    }
}
