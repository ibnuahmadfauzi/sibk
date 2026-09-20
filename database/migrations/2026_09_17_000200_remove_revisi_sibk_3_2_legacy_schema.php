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
        Schema::dropIfExists('consultation_private_notes');
        Schema::dropIfExists('follow_ups');
        Schema::dropIfExists('case_coordinations');

        if (DB::getDriverName() === 'sqlite') {
            $this->removeLegacyConsultationColumnsFromSqlite();
            foreach (['waka_summary', 'final_result', 'continued_plan'] as $column) {
                DB::statement(sprintf('ALTER TABLE "cases" DROP COLUMN "%s"', $column));
            }

            return;
        }

        Schema::table('consultations', function (Blueprint $table): void {
            $table->dropForeign(['case_id']);
            $table->dropForeign(['status_id']);
            $table->dropUnique(['registration_number']);
            $table->dropIndex('consultation_student_date_status_index');
            $table->dropColumn([
                'registration_number', 'case_id', 'status_id', 'topic', 'referral_source',
                'starts_at', 'ends_at', 'follow_up_date', 'general_summary',
            ]);
        });

        Schema::table('cases', fn (Blueprint $table) => $table->dropColumn([
            'waka_summary', 'final_result', 'continued_plan',
        ]));
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            foreach (['waka_summary VARCHAR(500)', 'final_result TEXT', 'continued_plan TEXT'] as $column) {
                DB::statement(sprintf('ALTER TABLE "cases" ADD COLUMN %s NULL', $column));
            }
            $this->restoreLegacyConsultationColumnsToSqlite();
        } else {
            Schema::table('cases', function (Blueprint $table): void {
                $table->string('waka_summary', 500)->nullable();
                $table->text('final_result')->nullable();
                $table->text('continued_plan')->nullable();
            });

            Schema::table('consultations', function (Blueprint $table): void {
                $table->string('registration_number', 30)->nullable()->unique();
                $table->foreignId('case_id')->nullable()->constrained('cases')->restrictOnDelete();
                $table->foreignId('status_id')->nullable()->constrained('references')->restrictOnDelete();
                $table->string('topic', 250)->nullable();
                $table->string('referral_source', 150)->nullable();
                $table->time('starts_at')->nullable();
                $table->time('ends_at')->nullable();
                $table->date('follow_up_date')->nullable();
                $table->text('general_summary')->nullable();
                $table->index(['student_id', 'session_date', 'status_id'], 'consultation_student_date_status_index');
            });
        }

        Schema::create('case_coordinations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->restrictOnDelete();
            $table->foreignId('waka_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('status_id')->constrained('references')->restrictOnDelete();
            $table->text('coordination_need');
            $table->text('result')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('coordinated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['case_id', 'waka_user_id', 'status_id'], 'case_coordination_access_index');
        });

        Schema::create('follow_ups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('case_id')->constrained('cases')->restrictOnDelete();
            $table->foreignId('follow_up_type_id')->constrained('references')->restrictOnDelete();
            $table->foreignId('status_id')->constrained('references')->restrictOnDelete();
            $table->date('planned_date');
            $table->date('execution_date')->nullable();
            $table->text('result')->nullable();
            $table->text('next_plan')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['case_id', 'planned_date', 'status_id'], 'follow_up_case_schedule_index');
            $table->index(['planned_date', 'status_id', 'case_id'], 'follow_up_date_status_case_index');
        });

        Schema::create('consultation_private_notes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('consultation_id')->unique()->constrained()->restrictOnDelete();
            $table->text('internal_note')->nullable();
            $table->text('sensitive_content')->nullable();
            $table->text('conclusion')->nullable();
            $table->text('follow_up_plan')->nullable();
            $table->foreignId('updated_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function removeLegacyConsultationColumnsFromSqlite(): void
    {
        Schema::disableForeignKeyConstraints();
        try {
            DB::statement(<<<'SQL'
                CREATE TABLE "consultations_final" (
                    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    "student_id" INTEGER NULL REFERENCES "students" ("id") ON DELETE RESTRICT,
                    "temporary_student_id" INTEGER NULL REFERENCES "temporary_students" ("id") ON DELETE RESTRICT,
                    "service_field_id" INTEGER NOT NULL REFERENCES "references" ("id") ON DELETE RESTRICT,
                    "session_date" DATE NOT NULL,
                    "counselor_id" INTEGER NOT NULL REFERENCES "users" ("id") ON DELETE RESTRICT,
                    "created_at" DATETIME NULL,
                    "updated_at" DATETIME NULL,
                    "deleted_at" DATETIME NULL,
                    "problem" TEXT NULL,
                    "handling" TEXT NULL,
                    "result" TEXT NULL
                )
                SQL);
            DB::statement(<<<'SQL'
                INSERT INTO "consultations_final" (
                    "id", "student_id", "temporary_student_id", "service_field_id", "session_date",
                    "counselor_id", "created_at", "updated_at", "deleted_at", "problem", "handling", "result"
                )
                SELECT
                    "id", "student_id", "temporary_student_id", "service_field_id", "session_date",
                    "counselor_id", "created_at", "updated_at", "deleted_at", "problem", "handling", "result"
                FROM "consultations"
                SQL);
            DB::statement('DROP TABLE "consultations"');
            DB::statement('ALTER TABLE "consultations_final" RENAME TO "consultations"');
            DB::statement('CREATE INDEX "consultation_temporary_date_index" ON "consultations" ("temporary_student_id", "session_date")');
            DB::statement('CREATE INDEX "consultation_counselor_date_index" ON "consultations" ("counselor_id", "session_date")');
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }

    private function restoreLegacyConsultationColumnsToSqlite(): void
    {
        Schema::disableForeignKeyConstraints();
        try {
            DB::statement(<<<'SQL'
                CREATE TABLE "consultations_legacy" (
                    "id" INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    "registration_number" VARCHAR(30) NULL,
                    "student_id" INTEGER NULL REFERENCES "students" ("id") ON DELETE RESTRICT,
                    "temporary_student_id" INTEGER NULL REFERENCES "temporary_students" ("id") ON DELETE RESTRICT,
                    "case_id" INTEGER NULL REFERENCES "cases" ("id") ON DELETE RESTRICT,
                    "service_field_id" INTEGER NOT NULL REFERENCES "references" ("id") ON DELETE RESTRICT,
                    "status_id" INTEGER NULL REFERENCES "references" ("id") ON DELETE RESTRICT,
                    "topic" VARCHAR(250) NULL,
                    "referral_source" VARCHAR(150) NULL,
                    "session_date" DATE NOT NULL,
                    "starts_at" TIME NULL,
                    "ends_at" TIME NULL,
                    "follow_up_date" DATE NULL,
                    "general_summary" TEXT NULL,
                    "counselor_id" INTEGER NOT NULL REFERENCES "users" ("id") ON DELETE RESTRICT,
                    "created_at" DATETIME NULL,
                    "updated_at" DATETIME NULL,
                    "deleted_at" DATETIME NULL,
                    "problem" TEXT NULL,
                    "handling" TEXT NULL,
                    "result" TEXT NULL
                )
                SQL);
            DB::statement(<<<'SQL'
                INSERT INTO "consultations_legacy" (
                    "id", "student_id", "temporary_student_id", "service_field_id", "session_date",
                    "counselor_id", "created_at", "updated_at", "deleted_at", "problem", "handling", "result"
                )
                SELECT
                    "id", "student_id", "temporary_student_id", "service_field_id", "session_date",
                    "counselor_id", "created_at", "updated_at", "deleted_at", "problem", "handling", "result"
                FROM "consultations"
                SQL);
            DB::statement('DROP TABLE "consultations"');
            DB::statement('ALTER TABLE "consultations_legacy" RENAME TO "consultations"');
            DB::statement('CREATE UNIQUE INDEX "consultations_registration_number_unique" ON "consultations" ("registration_number")');
            DB::statement('CREATE INDEX "consultation_student_date_status_index" ON "consultations" ("student_id", "session_date", "status_id")');
            DB::statement('CREATE INDEX "consultation_temporary_date_index" ON "consultations" ("temporary_student_id", "session_date")');
            DB::statement('CREATE INDEX "consultation_counselor_date_index" ON "consultations" ("counselor_id", "session_date")');
        } finally {
            Schema::enableForeignKeyConstraints();
        }
    }
};
