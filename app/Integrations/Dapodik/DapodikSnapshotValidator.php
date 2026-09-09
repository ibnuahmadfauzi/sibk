<?php

declare(strict_types=1);

namespace App\Integrations\Dapodik;

use App\Integrations\IntegrationConfigurationException;
use App\Integrations\IntegrationRuntimeConfiguration;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\StudentClassMembership;

final class DapodikSnapshotValidator
{
    /** @param array<string, bool>|null $admittedCompletenessMarkers */
    public function __construct(
        private readonly ?string $admittedContractMarker = null,
        private readonly ?array $admittedCompletenessMarkers = null,
        private readonly ?int $maximumPages = null,
        private readonly ?int $maximumRecords = null,
        private readonly ?int $maximumBytes = null,
    ) {}

    public function validate(DapodikSnapshot $snapshot, IntegrationRuntimeConfiguration $configuration): void
    {
        $this->assertAdmission();
        $evidence = $snapshot->evidence;
        if ($evidence === null
            || ! hash_equals($configuration->expectedSourceIdentifier, $evidence->reportedSourceIdentifier)
        ) {
            throw new IntegrationConfigurationException('source_identity_mismatch');
        }
        if (! hash_equals((string) $this->admittedContractMarker, $evidence->contractMarker)) {
            throw new IntegrationConfigurationException('contract_invalid');
        }
        $full = $this->admittedCompletenessMarkers[$evidence->completenessMarker] ?? null;
        if (! is_bool($full) || $full !== $snapshot->isFullSnapshot) {
            throw new IntegrationConfigurationException('contract_invalid');
        }
        if ($evidence->pageCount < 1
            || $evidence->recordCount !== $snapshot->recordCount()
            || $evidence->processedBytes < 0
        ) {
            throw new IntegrationConfigurationException('contract_invalid');
        }
        if ($evidence->pageCount > $this->maximumPages
            || $evidence->recordCount > $this->maximumRecords
            || $evidence->processedBytes > $this->maximumBytes
        ) {
            throw new IntegrationConfigurationException('response_too_large');
        }

        foreach ([$snapshot->academicYears, $snapshot->classrooms, $snapshot->students, $snapshot->memberships] as $collection) {
            if (! array_is_list($collection)) {
                throw new IntegrationConfigurationException('contract_invalid');
            }
        }

        $yearIds = $this->validateAcademicYears($snapshot->academicYears);
        $classroomYears = $this->validateClassrooms($snapshot->classrooms, $yearIds);
        $studentIds = $this->validateStudents($snapshot->students);
        $this->validateMemberships($snapshot->memberships, $yearIds, $classroomYears, $studentIds);
    }

    private function assertAdmission(): void
    {
        if (! is_string($this->admittedContractMarker) || $this->admittedContractMarker === ''
            || ! is_array($this->admittedCompletenessMarkers) || $this->admittedCompletenessMarkers === []
            || ! is_int($this->maximumPages) || $this->maximumPages < 1
            || ! is_int($this->maximumRecords) || $this->maximumRecords < 0
            || ! is_int($this->maximumBytes) || $this->maximumBytes < 0
        ) {
            throw new IntegrationConfigurationException('contract_invalid');
        }
        foreach ($this->admittedCompletenessMarkers as $marker => $isFull) {
            if (! is_string($marker) || $marker === '' || ! is_bool($isFull)) {
                throw new IntegrationConfigurationException('contract_invalid');
            }
        }
    }

    /** @param list<array<string, mixed>> $items @return array<string, true> */
    private function validateAcademicYears(array $items): array
    {
        $ids = [];
        foreach ($items as $item) {
            $this->assertShape($item, ['source_id' => 'string', 'name' => 'string'], [
                'starts_on' => 'nullable_string', 'ends_on' => 'nullable_string', 'is_active' => 'bool',
            ]);
            $this->rememberUnique($ids, $item['source_id']);
        }

        return $ids;
    }

    /** @param list<array<string, mixed>> $items @param array<string, true> $yearIds @return array<string, string> */
    private function validateClassrooms(array $items, array $yearIds): array
    {
        $years = [];
        foreach ($items as $item) {
            $this->assertShape($item, [
                'source_id' => 'string', 'academic_year_source_id' => 'string', 'name' => 'string',
            ], ['grade_level' => 'nullable_int', 'major' => 'nullable_string', 'is_active' => 'bool']);
            if (! isset($yearIds[$item['academic_year_source_id']]) || isset($years[$item['source_id']])) {
                throw new IntegrationConfigurationException('contract_invalid');
            }
            $existing = Classroom::query()
                ->with('academicYear:id,dapodik_id')
                ->where('dapodik_id', $item['source_id'])
                ->first();
            if ($existing !== null
                && $existing->academicYear?->dapodik_id !== $item['academic_year_source_id']
            ) {
                throw new IntegrationConfigurationException('source_identity_mismatch');
            }
            $years[$item['source_id']] = $item['academic_year_source_id'];
        }

        return $years;
    }

    /** @param list<array<string, mixed>> $items @return array<string, true> */
    private function validateStudents(array $items): array
    {
        $ids = [];
        $nisns = [];
        foreach ($items as $item) {
            $this->assertShape($item, ['source_id' => 'string', 'nisn' => 'string', 'name' => 'string'], ['is_active' => 'bool']);
            if (! preg_match('/^\d{10}$/D', $item['nisn']) || isset($nisns[$item['nisn']])) {
                throw new IntegrationConfigurationException('source_identity_mismatch');
            }
            $existing = Student::query()->where('dapodik_id', $item['source_id'])->first();
            if ($existing !== null && $existing->nisn !== $item['nisn']) {
                throw new IntegrationConfigurationException('source_identity_mismatch');
            }
            $this->rememberUnique($ids, $item['source_id']);
            $nisns[$item['nisn']] = true;
        }

        return $ids;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @param  array<string, true>  $yearIds
     * @param  array<string, string>  $classroomYears
     * @param  array<string, true>  $studentIds
     */
    private function validateMemberships(array $items, array $yearIds, array $classroomYears, array $studentIds): void
    {
        $ids = [];
        foreach ($items as $item) {
            $this->assertShape($item, [
                'source_id' => 'string', 'student_source_id' => 'string', 'classroom_source_id' => 'string',
                'academic_year_source_id' => 'string', 'effective_from' => 'string',
            ], ['effective_until' => 'nullable_string', 'is_active' => 'bool']);
            if (! isset($studentIds[$item['student_source_id']], $classroomYears[$item['classroom_source_id']], $yearIds[$item['academic_year_source_id']])
                || $classroomYears[$item['classroom_source_id']] !== $item['academic_year_source_id']
            ) {
                throw new IntegrationConfigurationException('contract_invalid');
            }
            $existing = StudentClassMembership::query()
                ->with(['student:id,dapodik_id', 'classroom:id,dapodik_id', 'academicYear:id,dapodik_id'])
                ->where('dapodik_id', $item['source_id'])
                ->first();
            if ($existing !== null
                && ($existing->student?->dapodik_id !== $item['student_source_id']
                    || $existing->classroom?->dapodik_id !== $item['classroom_source_id']
                    || $existing->academicYear?->dapodik_id !== $item['academic_year_source_id'])
            ) {
                throw new IntegrationConfigurationException('source_identity_mismatch');
            }
            $this->rememberUnique($ids, $item['source_id']);
        }
    }

    /** @param array<string, mixed> $item @param array<string, string> $required @param array<string, string> $optional */
    private function assertShape(array $item, array $required, array $optional): void
    {
        if (array_diff(array_keys($item), [...array_keys($required), ...array_keys($optional)]) !== []) {
            throw new IntegrationConfigurationException('contract_invalid');
        }
        foreach ($required as $field => $type) {
            if (! array_key_exists($field, $item) || ! $this->matches($item[$field], $type)) {
                throw new IntegrationConfigurationException('contract_invalid');
            }
        }
        foreach ($optional as $field => $type) {
            if (array_key_exists($field, $item) && ! $this->matches($item[$field], $type)) {
                throw new IntegrationConfigurationException('contract_invalid');
            }
        }
    }

    private function matches(mixed $value, string $type): bool
    {
        return match ($type) {
            'string' => is_string($value) && $value !== '',
            'nullable_string' => $value === null || is_string($value),
            'nullable_int' => $value === null || is_int($value),
            'bool' => is_bool($value),
            default => false,
        };
    }

    /** @param array<string, true> $ids */
    private function rememberUnique(array &$ids, string $id): void
    {
        if (isset($ids[$id])) {
            throw new IntegrationConfigurationException('source_identity_mismatch');
        }
        $ids[$id] = true;
    }
}
