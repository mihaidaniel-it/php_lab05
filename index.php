<?php

declare(strict_types=1);

class Transaction
{
    private int $id;
    private DateTime $date;
    private float $amount;
    private string $description;
    private string $merchant;

    public function __construct(
        int $id,
        DateTime $date,
        float $amount,
        string $description,
        string $merchant
    ) {
        $this->id = $id;
        $this->date = $date;
        $this->amount = $amount;
        $this->description = $description;
        $this->merchant = $merchant;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getDate(): DateTime
    {
        return $this->date;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getMerchant(): string
    {
        return $this->merchant;
    }

    public function getDaysSinceTransaction(): int
    {
        $now = new DateTime();
        $diff = $this->date->diff($now);
        return $diff->days;
    }
}


interface TransactionStorageInterface
{
    public function addTransaction(Transaction $transaction): void;

    public function removeTransactionById(int $id): void;

    public function getAllTransactions(): array;

    public function findById(int $id): ?Transaction;
}

class TransactionRepository implements TransactionStorageInterface
{
    private array $transactions = [];

    public function addTransaction(Transaction $transaction): void
    {
        $this->transactions[] = $transaction;
    }

    public function removeTransactionById(int $id): void
    {
        $this->transactions = array_filter(
            $this->transactions,
            fn($t) => $t->getId() !== $id
        );
    }

    public function getAllTransactions(): array
    {
        return $this->transactions;
    }

    public function findById(int $id): ?Transaction
    {
        foreach ($this->transactions as $transaction) {
            if ($transaction->getId() === $id) {
                return $transaction;
            }
        }
        return null;
    }
}


class TransactionManager
{
    public function __construct(
        private TransactionStorageInterface $repository
    ) {}


    public function calculateTotalAmount(): float
    {
        $total = 0;
        foreach ($this->repository->getAllTransactions() as $t) {
            $total += $t->getAmount();
        }
        return $total;
    }


    public function calculateTotalAmountByDateRange(string $startDate, string $endDate): float
    {
        $start = new DateTime($startDate);
        $end = new DateTime($endDate);

        $total = 0;

        foreach ($this->repository->getAllTransactions() as $t) {
            if ($t->getDate() >= $start && $t->getDate() <= $end) {
                $total += $t->getAmount();
            }
        }

        return $total;
    }


    public function countTransactionsByMerchant(string $merchant): int
    {
        $count = 0;

        foreach ($this->repository->getAllTransactions() as $t) {
            if ($t->getMerchant() === $merchant) {
                $count++;
            }
        }

        return $count;
    }


    public function sortTransactionsByDate(): array
    {
        $transactions = $this->repository->getAllTransactions();

        usort($transactions, function ($a, $b) {
            return $a->getDate() <=> $b->getDate();
        });

        return $transactions;
    }

    public function sortTransactionsByAmountDesc(): array
    {
        $transactions = $this->repository->getAllTransactions();

        usort($transactions, function ($a, $b) {
            return $b->getAmount() <=> $a->getAmount();
        });

        return $transactions;
    }
}

final class TransactionTableRenderer
{
    public function render(array $transactions): string
    {
        $html = "<h2>Список транзакций</h2>";
        $html .= "<table border='1' cellpadding='5' cellspacing='0'>";
        $html .= "<tr>
                    <th>ID</th>
                    <th>Дата</th>
                    <th>Сумма</th>
                    <th>Описание</th>
                    <th>Получатель</th>
                    <th>Категория</th>
                    <th>Дней назад</th>
                  </tr>";

        foreach ($transactions as $t) {
            $html .= "<tr>
                        <td>{$t->getId()}</td>
                        <td>{$t->getDate()->format('Y-m-d')}</td>
                        <td>{$t->getAmount()}</td>
                        <td>{$t->getDescription()}</td>
                        <td>{$t->getMerchant()}</td>
                        <td>{$t->getMerchant()}</td>
                        <td>{$t->getDaysSinceTransaction()}</td>
                      </tr>";
        }

        $html .= "</table>";

        return $html;
    }
}


$repository = new TransactionRepository();

$transactions = [
    new Transaction(1, new DateTime('2024-01-10'), 120.5, 'Продукты', 'Lidl'),
    new Transaction(2, new DateTime('2024-02-15'), 250, 'Одежда', 'Zara'),
    new Transaction(3, new DateTime('2024-03-05'), 80, 'Кофе', 'Starbucks'),
    new Transaction(4, new DateTime('2024-01-20'), 300, 'Техника', 'Amazon'),
    new Transaction(5, new DateTime('2024-02-01'), 45, 'Такси', 'Yandex'),
    new Transaction(6, new DateTime('2024-03-10'), 150, 'Ресторан', 'McDonalds'),
    new Transaction(7, new DateTime('2024-01-25'), 500, 'Телефон', 'Apple'),
    new Transaction(8, new DateTime('2024-02-18'), 60, 'Подписка', 'Netflix'),
    new Transaction(9, new DateTime('2024-03-01'), 90, 'Игры', 'Steam'),
    new Transaction(10, new DateTime('2024-03-12'), 200, 'Обучение', 'Udemy'),
];

foreach ($transactions as $transaction) {
    $repository->addTransaction($transaction);
}


$manager = new TransactionManager($repository);

$total = $manager->calculateTotalAmount();
$rangeTotal = $manager->calculateTotalAmountByDateRange('2024-01-01', '2024-12-31');
$countAmazon = $manager->countTransactionsByMerchant('Amazon');

$sortedByDate = $manager->sortTransactionsByDate();


echo "<h3>Общая сумма: $total</h3>";
echo "<h3>Сумма за 2024: $rangeTotal</h3>";
echo "<h3>Транзакции Amazon: $countAmazon</h3>";

$renderer = new TransactionTableRenderer();
echo $renderer->render($sortedByDate);
