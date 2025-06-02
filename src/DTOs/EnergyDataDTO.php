<?php
declare(strict_types=1);

namespace App\DTOs;

class EnergyDataDTO
{
    public function __construct(
        public readonly float $totalPriceTaxIncluded,
        public readonly ?float $sustainabilityScore = null,
        public readonly ?int $rankTotalPrice = null,
        public readonly ?int $rankSustainabilityScore = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            totalPriceTaxIncluded: $data['total_price_tax_included'],
            sustainabilityScore: $data['sustainability_score'] ?? null,
            rankTotalPrice: $data['rank_total_price'] ?? null,
            rankSustainabilityScore: $data['rank_sustainability_score'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'total_price_tax_included' => $this->totalPriceTaxIncluded,
            'sustainability_score' => $this->sustainabilityScore,
            'rank_total_price' => $this->rankTotalPrice,
            'rank_sustainability_score' => $this->rankSustainabilityScore
        ];
    }
}
