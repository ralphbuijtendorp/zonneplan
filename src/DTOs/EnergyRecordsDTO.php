<?php
declare(strict_types=1);

namespace App\DTOs;

class EnergyRecordsDTO
{
    public function __construct(
        public readonly EnergyDataDTO $priceLow,
        public readonly EnergyDataDTO $priceHigh,
        public readonly ?EnergyDataDTO $sustainabilityHigh = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            priceLow: EnergyDataDTO::fromArray($data['price_low']),
            priceHigh: EnergyDataDTO::fromArray($data['price_high']),
            sustainabilityHigh: isset($data['sustainability_high']) 
                ? EnergyDataDTO::fromArray($data['sustainability_high']) 
                : null
        );
    }

    public function toArray(): array
    {
        $result = [
            'price_low' => $this->priceLow->toArray(),
            'price_high' => $this->priceHigh->toArray(),
        ];

        if ($this->sustainabilityHigh !== null) {
            $result['sustainability_high'] = $this->sustainabilityHigh->toArray();
        }

        return $result;
    }
}
