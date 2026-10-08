<?php

namespace App\Services\Content;

/**
 * Shared order-column shifting for admin Kelola Kurikulum CRUD (Modules,
 * Units, ChallengeSteps, and — Fase 4 Batch 3 — UnitEvaluation) — picking a
 * position already in use shifts everything from that position onward
 * instead of being rejected as a validation error. $scope narrows the
 * affected rows (e.g. `module_id` for Unit, so shifting one module's units
 * never touches another module's).
 *
 * $column defaults to `order_number` (every caller before Fase 4 Batch 3
 * used that name) — UnitEvaluation's column is named `sort_order` instead,
 * so it's the first caller to pass $column explicitly. Reused rather than
 * duplicated: the shifting logic itself is identical regardless of what the
 * column is called.
 */
class CurriculumReorderService
{
    /**
     * For inserting a brand-new row: every existing row at or after
     * $position moves one step further down the list.
     */
    public function makeRoomForNewPosition(string $modelClass, array $scope, int $position, string $column = 'order_number'): void
    {
        $modelClass::query()
            ->where($scope)
            ->where($column, '>=', $position)
            ->increment($column);
    }

    /**
     * For repositioning an EXISTING row (excluded from the shift itself)
     * from $oldPosition to $newPosition, within the same $scope. Handles
     * both directions: moving further down the list (newPosition > old)
     * pulls everything in between back by one; moving earlier (newPosition
     * < old) pushes everything in between forward by one.
     */
    public function moveToPosition(string $modelClass, array $scope, string $excludeId, int $oldPosition, int $newPosition, string $column = 'order_number'): void
    {
        if ($oldPosition === $newPosition) {
            return;
        }

        $query = $modelClass::query()->where($scope)->where('id', '!=', $excludeId);

        if ($newPosition > $oldPosition) {
            $query->whereBetween($column, [$oldPosition + 1, $newPosition])->decrement($column);
        } else {
            $query->whereBetween($column, [$newPosition, $oldPosition - 1])->increment($column);
        }
    }
}
