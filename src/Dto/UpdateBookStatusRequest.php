<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use function is_bool;
use function is_scalar;

final class UpdateBookStatusRequest
{
    public function __construct(
        #[Assert\NotNull]
        public ?bool $borrowed,
        #[Assert\Regex(pattern: '/^\d{6}$/', message: 'Library card number must be a 6-digit number.')]
        public ?string $cardNumber,
    ) {
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $borrowed = $data['borrowed'] ?? null;
        $cardNumber = $data['cardNumber'] ?? null;

        return new self(
            is_bool($borrowed) ? $borrowed : null,
            is_scalar($cardNumber) ? (string) $cardNumber : null,
        );
    }

    #[Assert\Callback]
    public function validateCardNumberIsPresentToBorrow(ExecutionContextInterface $context): void
    {
        if ($this->borrowed === true && $this->cardNumber === null) {
            $context->buildViolation('A library card number is required to borrow a book.')
                ->atPath('cardNumber')
                ->addViolation();
        }
    }
}
