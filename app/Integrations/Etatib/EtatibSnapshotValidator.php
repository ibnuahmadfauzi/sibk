<?php

declare(strict_types=1);

namespace App\Integrations\Etatib;

use App\Integrations\IntegrationConfigurationException;
use App\Integrations\IntegrationRuntimeConfiguration;
use App\Models\ExternalTatibRecord;
use DateTimeImmutable;

final class EtatibSnapshotValidator
{
    private const array FIELDS = [
        'source_id', 'nisn', 'occurred_at', 'violation_type', 'category', 'points',
        'source_status', 'source_synced_at',
    ];

    /**
     * @param  array<string, bool>|null  $admittedCompletenessMarkers
     * @param  list<string>|null  $immutableFields
     * @param  list<string>|null  $mutableFields
     */
    public function __construct(
        private readonly ?string $admittedContractMarker = null,
        private readonly ?array $admittedCompletenessMarkers = null,
        private readonly ?int $maximumPages = null,
        private readonly ?int $maximumRecords = null,
        private readonly ?int $maximumBytes = null,
        private readonly ?array $immutableFields = null,
        private readonly ?array $mutableFields = null,
        private readonly ?string $revisionStrategy = null,
    ) {}

    public function validate(EtatibSnapshot $snapshot, IntegrationRuntimeConfiguration $configuration): void
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
        if (! is_bool($full) || $full !== $snapshot->isFullSnapshot
            || $evidence->pageCount < 1
            || $evidence->recordCount !== count($snapshot->records)
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
        if (! array_is_list($snapshot->records)) {
            throw new IntegrationConfigurationException('contract_invalid');
        }

        $sourceIds = [];
        foreach ($snapshot->records as $item) {
            if (! is_array($item)) {
                throw new IntegrationConfigurationException('contract_invalid');
            }
            $this->assertShape($item);
            if (isset($sourceIds[$item['source_id']])) {
                throw new IntegrationConfigurationException('source_identity_mismatch');
            }
            $sourceIds[$item['source_id']] = true;
            $existing = ExternalTatibRecord::query()
                ->where('source_identifier', $item['source_id'])
                ->first();
            if ($existing !== null && $existing->nisn !== $item['nisn']) {
                throw new IntegrationConfigurationException('source_identity_mismatch');
            }
            $incomingRevision = $this->normalizedRevision($item['source_synced_at'] ?? null);
            if ($incomingRevision === null) {
                throw new IntegrationConfigurationException('contract_invalid');
            }
            if ($existing !== null) {
                $this->validateExistingRecord($item, $existing, $incomingRevision);
            }
        }
    }

    private function assertAdmission(): void
    {
        $fields = array_merge($this->immutableFields ?? [], $this->mutableFields ?? []);
        if (! is_string($this->admittedContractMarker) || $this->admittedContractMarker === ''
            || ! is_array($this->admittedCompletenessMarkers) || $this->admittedCompletenessMarkers === []
            || ! is_int($this->maximumPages) || $this->maximumPages < 1
            || ! is_int($this->maximumRecords) || $this->maximumRecords < 0
            || ! is_int($this->maximumBytes) || $this->maximumBytes < 0
            || ! is_array($this->immutableFields) || ! in_array('source_id', $this->immutableFields, true)
            || ! in_array('nisn', $this->immutableFields, true)
            || ! is_array($this->mutableFields)
            || array_intersect($this->immutableFields, $this->mutableFields) !== []
            || array_diff(self::FIELDS, $fields) !== []
            || array_diff($fields, self::FIELDS) !== []
            || $this->revisionStrategy !== 'source_synced_at_timestamp'
            || ! in_array('source_synced_at', $this->mutableFields, true)
        ) {
            throw new IntegrationConfigurationException('contract_invalid');
        }
        foreach ($this->admittedCompletenessMarkers as $marker => $isFull) {
            if (! is_string($marker) || $marker === '' || ! is_bool($isFull)) {
                throw new IntegrationConfigurationException('contract_invalid');
            }
        }
    }

    /** @param array<string, mixed> $item */
    private function validateExistingRecord(
        array $item,
        ExternalTatibRecord $existing,
        DateTimeImmutable $incomingRevision,
    ): void {
        foreach ($this->immutableFields ?? [] as $field) {
            if (! $this->fieldMatches($field, $item[$field] ?? null, $existing)) {
                throw new IntegrationConfigurationException('source_identity_mismatch');
            }
        }

        $storedRevision = $this->normalizedRevision($existing->getRawOriginal('source_synced_at'));
        if ($storedRevision === null || $incomingRevision < $storedRevision) {
            throw new IntegrationConfigurationException('contract_invalid');
        }

        $mutableChanged = collect($this->mutableFields ?? [])
            ->reject(static fn (string $field): bool => $field === 'source_synced_at')
            ->contains(fn (string $field): bool => ! $this->fieldMatches($field, $item[$field] ?? null, $existing));
        if ($mutableChanged && $incomingRevision <= $storedRevision) {
            throw new IntegrationConfigurationException('contract_invalid');
        }
    }

    private function fieldMatches(string $field, mixed $incoming, ExternalTatibRecord $existing): bool
    {
        $stored = $field === 'source_id'
            ? $existing->source_identifier
            : $existing->getRawOriginal($field);

        if ($field === 'occurred_at') {
            $incomingTimestamp = $this->normalizedTimestamp($incoming);
            $storedTimestamp = $this->normalizedTimestamp($stored);

            return $incomingTimestamp !== null
                && $storedTimestamp !== null
                && $incomingTimestamp->getTimestamp() === $storedTimestamp->getTimestamp();
        }
        if ($field === 'points') {
            return is_int($incoming) && $incoming === (int) $stored;
        }

        return $incoming === $stored;
    }

    private function normalizedRevision(mixed $value): ?DateTimeImmutable
    {
        return $this->normalizedTimestamp($value);
    }

    private function normalizedTimestamp(mixed $value): ?DateTimeImmutable
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        $timestamp = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value);
        $errors = DateTimeImmutable::getLastErrors();

        return $timestamp !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $timestamp->format('Y-m-d H:i:s') === $value
                ? $timestamp
                : null;
    }

    /** @param array<string, mixed> $item */
    private function assertShape(array $item): void
    {
        if (array_diff(array_keys($item), self::FIELDS) !== []) {
            throw new IntegrationConfigurationException('contract_invalid');
        }
        foreach (['source_id', 'nisn', 'occurred_at', 'violation_type', 'category'] as $field) {
            if (! isset($item[$field]) || ! is_string($item[$field]) || $item[$field] === '') {
                throw new IntegrationConfigurationException('contract_invalid');
            }
        }
        if (! preg_match('/^\d{10}$/D', $item['nisn']) || ! isset($item['points']) || ! is_int($item['points'])) {
            throw new IntegrationConfigurationException('contract_invalid');
        }
        foreach (['source_status', 'source_synced_at'] as $field) {
            if (array_key_exists($field, $item) && $item[$field] !== null && ! is_string($item[$field])) {
                throw new IntegrationConfigurationException('contract_invalid');
            }
        }
    }
}
