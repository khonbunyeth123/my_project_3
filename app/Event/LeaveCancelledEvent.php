<?php
declare(strict_types=1);
namespace App\Event;
/**
 * Event dispatched when an approved leave application is cancelled.
 */
class LeaveCancelledEvent
{
    /**
     * @param array $leave The leave entity data
     */
    public function __construct(
        public readonly array $leave
    ) {}
}
