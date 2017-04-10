<?php

namespace App\Model\Bitcoin\Dto;

/**
 * Stores transactions and payment Dtos between them
 *
 * Class BitcoinTransactionGraphDto
 * @package App\Model\Bitcoin\Dto
 */
class BitcoinTransactionGraphDto
{
    /**
     * @var array<BitcoinTransactionDto>
     */
    private $transactions;

    /**
     * @var array<BitcoinTransactionPaymentDto>
     */
    private $payments;

    /**
     * @return array
     */
    public function getTransactions()
    {
        return $this->transactions;
    }

    /**
     * @param array $transactions
     */
    public function setTransactions($transactions)
    {
        $this->transactions = $transactions;
    }

    /**
     * @return array
     */
    public function getPayments()
    {
        return $this->payments;
    }

    /**
     * @param array $payments
     */
    public function setPayments($payments)
    {
        $this->payments = $payments;
    }
}