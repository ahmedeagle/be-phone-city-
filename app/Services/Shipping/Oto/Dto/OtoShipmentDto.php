<?php

namespace App\Services\Shipping\Oto\Dto;

/**
 * Data Transfer Object for OTO shipment response
 */
class OtoShipmentDto
{
    public function __construct(
        public readonly string $shipmentReference,
        public readonly string $trackingNumber,
        public readonly string $trackingUrl,
        public readonly string $status,
        public readonly ?string $eta = null,
        public readonly ?array $rawPayload = null
    ) {
    }

    /**
     * Create DTO from OTO API response
     */
    public static function fromApiResponse(array $data): self
    {
        return new self(
            shipmentReference: $data['shipmentId'] ?? $data['shipment_id'] ?? $data['reference'] ?? '',
            trackingNumber: $data['dcTrackingNumber'] ?? $data['tracking_number'] ?? $data['awb'] ?? '',
            trackingUrl: $data['tracking_url'] ?? $data['tracking_link'] ?? $data['trackingUrl'] ?? '',
            status: $data['status'] ?? 'unknown',
            eta: $data['deliverySlotDate'] ?? $data['estimated_delivery'] ?? $data['eta'] ?? null,
            rawPayload: $data
        );
    }

    /**
     * Convert to array for database storage
     */
    public function toArray(): array
    {
        return [
            'shipment_reference' => $this->shipmentReference,
            'tracking_number' => $this->trackingNumber,
            'tracking_url' => $this->trackingUrl,
            'status' => $this->status,
            'eta' => $this->eta,
            'raw_payload' => $this->rawPayload,
        ];
    }

    /**
     * Check if shipment was successfully created
     */
    public function isValid(): bool
    {
        // Valid if we have tracking info OR if the request was explicitly successful
        return (!empty($this->shipmentReference) && !empty($this->trackingNumber))
               || ($this->rawPayload['success'] ?? false) === true;
    }

    /**
     * Check if tracking information is available
     */
    public function hasTrackingInfo(): bool
    {
        return !empty($this->trackingNumber);
    }
}



