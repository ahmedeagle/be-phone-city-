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
     * Handles both orderStatus and trackShipment response formats
     */
    public static function fromApiResponse(array $data): self
    {
        // Extract tracking number from various possible fields
        $trackingNumber = $data['dcTrackingNumber']
            ?? $data['tracking_number']
            ?? $data['awb']
            ?? '';

        // Extract status
        $status = $data['status'] ?? 'unknown';

        // Extract status description
        $statusDescription = $data['status_description']
            ?? $data['status_message']
            ?? $data['note']
            ?? null;

        // Extract ETA/delivery date
        $eta = $data['deliverySlotDate']
            ?? $data['estimated_delivery']
            ?? $data['eta']
            ?? null;

        // Extract updated date
        $updatedAt = null;
        if (isset($data['updated_at'])) {
            try {
                $updatedAt = Carbon::parse($data['updated_at']);
            } catch (\Exception $e) {
                // Try alternative format
                if (isset($data['date'])) {
                    try {
                        $updatedAt = Carbon::createFromFormat('d/m/Y H:i:s', $data['date']);
                    } catch (\Exception $e2) {
                        $updatedAt = Carbon::now();
                    }
                } else {
                    $updatedAt = Carbon::now();
                }
            }
        } elseif (isset($data['date'])) {
            try {
                $updatedAt = Carbon::createFromFormat('d/m/Y H:i:s', $data['date']);
            } catch (\Exception $e) {
                $updatedAt = Carbon::now();
            }
        } else {
            $updatedAt = Carbon::now();
        }

        // Extract events/history
        $events = $data['events']
            ?? $data['tracking_events']
            ?? $data['history']
            ?? null;

        return new self(
            trackingNumber: $trackingNumber,
            status: $status,
            statusDescription: $statusDescription,
            eta: $eta,
            updatedAt: $updatedAt,
            events: $events,
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
        return $currentStatus !== \App\Services\Shipping\Oto\OtoStatusMapper::normalize($this->status);
    }
}
