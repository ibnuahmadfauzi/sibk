<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tatibColumns = $this->columnsFor('external_tatib_records');
        $relaxOccurredAt = ! $tatibColumns['occurred_at']['nullable'];
        $restoreSyncedAtDefault = ! $this->usesCurrentTimestamp($tatibColumns['synced_at']['default']);

        if ($relaxOccurredAt || $restoreSyncedAtDefault) {
            Schema::table('external_tatib_records', function (Blueprint $table) use ($relaxOccurredAt, $restoreSyncedAtDefault): void {
                if ($relaxOccurredAt) {
                    $table->timestamp('occurred_at')->nullable()->change();
                }
                if ($restoreSyncedAtDefault) {
                    $table->timestamp('synced_at')->useCurrent()->change();
                }
            });
        }

        $coordinationColumns = $this->columnsFor('case_coordinations');
        if (! $coordinationColumns['coordinated_at']['nullable']) {
            $quoteReferencesForeignKey = Schema::getConnection()->getDriverName() === 'sqlite';
            Schema::table('case_coordinations', function (Blueprint $table) use ($quoteReferencesForeignKey): void {
                $table->timestamp('coordinated_at')->nullable()->change();
                if ($quoteReferencesForeignKey) {
                    $table->dropForeign(['status_id']);
                    $table->foreign('status_id')
                        ->references('id')
                        ->on(new Expression('"references"'))
                        ->restrictOnDelete();
                }
            });
        }
    }

    public function down(): void
    {
        // Forward-only compatibility migration: nullable timestamp data must remain recoverable.
    }

    /** @return array<string, array{name: string, nullable: bool, default: mixed}> */
    private function columnsFor(string $table): array
    {
        $columns = [];
        foreach (Schema::getColumns($table) as $column) {
            $columns[$column['name']] = $column;
        }

        return $columns;
    }

    private function usesCurrentTimestamp(mixed $default): bool
    {
        return is_string($default)
            && preg_replace('/[()\\s]/', '', strtoupper($default)) === 'CURRENT_TIMESTAMP';
    }
};
