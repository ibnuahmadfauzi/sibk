<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('external_tatib_records', function (Blueprint $table): void {
            $table->timestamp('occurred_at')->nullable()->change();
            $table->timestamp('synced_at')->useCurrent()->change();
        });

        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->rebuildSqliteCaseCoordinations(nullable: true);

            return;
        }

        Schema::table('case_coordinations', function (Blueprint $table): void {
            $table->timestamp('coordinated_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('external_tatib_records', function (Blueprint $table): void {
            $table->timestamp('occurred_at')->nullable(false)->change();
            $table->timestamp('synced_at')->change();
        });

        if (DB::connection()->getDriverName() === 'sqlite') {
            $this->rebuildSqliteCaseCoordinations(nullable: false);

            return;
        }

        Schema::table('case_coordinations', function (Blueprint $table): void {
            $table->timestamp('coordinated_at')->nullable(false)->change();
        });
    }

    private function rebuildSqliteCaseCoordinations(bool $nullable): void
    {
        $coordinatedAt = $nullable
            ? '"coordinated_at" datetime'
            : '"coordinated_at" datetime not null';

        DB::statement(
            'create table "__temp__case_coordinations" (
                "id" integer primary key autoincrement not null,
                "case_id" integer not null,
                "waka_user_id" integer not null,
                "status_id" integer not null,
                "coordination_need" text not null,
                "result" text,
                "recorded_by" integer not null,
                '.$coordinatedAt.',
                "created_at" datetime,
                "updated_at" datetime,
                "deleted_at" datetime,
                foreign key("recorded_by") references "users"("id") on delete restrict on update no action,
                foreign key("status_id") references "references"("id") on delete restrict on update no action,
                foreign key("waka_user_id") references "users"("id") on delete restrict on update no action,
                foreign key("case_id") references "cases"("id") on delete restrict on update no action
            )'
        );

        DB::statement(
            'insert into "__temp__case_coordinations" (
                "id",
                "case_id",
                "waka_user_id",
                "status_id",
                "coordination_need",
                "result",
                "recorded_by",
                "coordinated_at",
                "created_at",
                "updated_at",
                "deleted_at"
            )
            select
                "id",
                "case_id",
                "waka_user_id",
                "status_id",
                "coordination_need",
                "result",
                "recorded_by",
                "coordinated_at",
                "created_at",
                "updated_at",
                "deleted_at"
            from "case_coordinations"'
        );

        DB::statement('drop table "case_coordinations"');

        DB::statement(
            'alter table "__temp__case_coordinations"
            rename to "case_coordinations"'
        );

        DB::statement(
            'create index "case_coordination_access_index"
            on "case_coordinations" ("case_id", "waka_user_id", "status_id")'
        );
    }
};
