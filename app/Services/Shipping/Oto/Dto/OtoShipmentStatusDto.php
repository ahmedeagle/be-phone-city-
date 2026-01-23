<?php

namespace App\Services\Shipping\Oto\Dto;

use Carbon\Carbon;

/**
 * Data Transfer Object for OTO shipment status updates
 */
class OtoShipmentStatusDto
{
    public function __construct(
        public readonly string $trackingNumber,
        public readonly string $status,
        public readonly ?string $statusDescription = null,
        public readonly ?string $eta = null,
        public readonly ?Carbon $updatedAt = null,
        public readonly ?array $events = null,
        public readonly ?array $rawPayload = null
    ) {
    }

    /**
     * Create DTO from OTO API response
     */
    public static function fromApiResponse(array $data): self
    {
        return new self(
            trackingNumber: $data['tracking_number'] ?? $data['awb'] ?? '',
            status: $data['status'] ?? 'unknown',
            statusDescription: $data['status_description'] ?? $data['status_message'] ?? null,
            eta: $data['estimated_delivery'] ?? $data['eta'] ?? null,
            updatedAt: isset($data['updated_at']) ? Carbon::parse($data['updated_at']) : Carbon::now(),
            events: $data['events'] ?? $data['tracking_events'] ?? null,
            rawPayload: $data
        );
    }

    /**
     * Create DTO from webhook payload
     */
    public static function fromWebhook(array $data): self
    {
        return self::fromApiResponse($data);
    }

    /**
     * Convert to array for database storage
     */
    public function toArray(): array
    {
        return [
            'tracking_number' => $this->trackingNumber,
            'status' => $this->status,
            'status_description' => $this->statusDescription,
            'eta' => $this->eta,
            'updated_at' => $this->updatedAt?->toDateTimeString(),
            'events' => $this->events,
            'raw_payload' => $this->rawPayload,
        ];
    }

    /**
     * Check if status has changed
     */
    public function isDifferentFrom(?string $currentStatus): bool
    {
        return $currentStatus !== $this->status;
    }
}



