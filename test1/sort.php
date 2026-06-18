<?php

declare(strict_types=1);

function generateNumbers(int $count): array
{
    mt_srand(42);

    $data = array_fill(0, $count, 0);

    for ($i = 0; $i < $count; $i++) {
        $data[$i] = mt_rand(-1_000_000, 1_000_000);
    }

    return $data;
}

function swapValues(mixed &$a, mixed &$b): void
{
    $tmp = $a;
    $a = $b;
    $b = $tmp;
}

function insertionSort(array &$data, int $left, int $right): void
{
    for ($i = $left + 1; $i <= $right; $i++) {
        $current = $data[$i];
        $j = $i - 1;

        while ($j >= $left && $data[$j] > $current) {
            $data[$j + 1] = $data[$j];
            $j--;
        }

        $data[$j + 1] = $current;
    }
}

function quickSort(array &$data): void
{
    $count = count($data);

    if ($count < 2) {
        return;
    }

    $threshold = 24;

    $leftStack = [0];
    $rightStack = [$count - 1];

    while (!empty($leftStack)) {
        $left = array_pop($leftStack);
        $right = array_pop($rightStack);

        while ($right - $left > $threshold) {
            $middle = $left + intdiv($right - $left, 2);

            if ($data[$left] > $data[$middle]) {
                swapValues($data[$left], $data[$middle]);
            }

            if ($data[$left] > $data[$right]) {
                swapValues($data[$left], $data[$right]);
            }

            if ($data[$middle] > $data[$right]) {
                swapValues($data[$middle], $data[$right]);
            }

            $pivot = $data[$middle];

            $i = $left;
            $j = $right;

            while ($i <= $j) {
                while ($data[$i] < $pivot) {
                    $i++;
                }

                while ($data[$j] > $pivot) {
                    $j--;
                }

                if ($i <= $j) {
                    swapValues($data[$i], $data[$j]);
                    $i++;
                    $j--;
                }
            }

            $leftSize = $j - $left;
            $rightSize = $right - $i;

            if ($leftSize < $rightSize) {
                if ($i < $right) {
                    $leftStack[] = $i;
                    $rightStack[] = $right;
                }

                $right = $j;
            } else {
                if ($left < $j) {
                    $leftStack[] = $left;
                    $rightStack[] = $j;
                }

                $left = $i;
            }
        }

        if ($left < $right) {
            insertionSort($data, $left, $right);
        }
    }
}

function isSortedAsc(array $data): bool
{
    $count = count($data);

    for ($i = 1; $i < $count; $i++) {
        if ($data[$i - 1] > $data[$i]) {
            return false;
        }
    }

    return true;
}

function benchmark(string $title, callable $sortFunction, array $originalData): void
{
    echo "\n=== {$title} ===\n";

    memory_reset_peak_usage();

    $data = $originalData;

    $memoryBefore = memory_get_usage(true);
    $start = hrtime(true);

    $sortFunction($data);

    $end = hrtime(true);
    $memoryAfter = memory_get_usage(true);
    $memoryPeak = memory_get_peak_usage(true);

    $timeMs = ($end - $start) / 1_000_000;

    echo 'Check sorted: ' . (isSortedAsc($data) ? 'OK' : 'FAILED') . PHP_EOL;
    echo 'First 10: ' . implode(', ', array_slice($data, 0, 10)) . PHP_EOL;
    echo 'Last 10: ' . implode(', ', array_slice($data, -10)) . PHP_EOL;
    echo 'Time: ' . round($timeMs, 2) . ' ms' . PHP_EOL;
    echo 'Memory before: ' . round($memoryBefore / 1024 / 1024, 2) . ' MB' . PHP_EOL;
    echo 'Memory after: ' . round($memoryAfter / 1024 / 1024, 2) . ' MB' . PHP_EOL;
    echo 'Peak memory: ' . round($memoryPeak / 1024 / 1024, 2) . ' MB' . PHP_EOL;
}

$count = isset($argv[1]) ? (int) $argv[1] : 200_000;

if ($count < 1) {
    fwrite(STDERR, "Count must be a positive integer.\n");
    exit(1);
}

echo "Generating {$count} numbers..." . PHP_EOL;

$originalData = generateNumbers($count);

echo 'Source first 10: ' . implode(', ', array_slice($originalData, 0, 10)) . PHP_EOL;

benchmark(
    'PHP built-in sort (recommended)',
    function (array &$data): void {
        sort($data, SORT_NUMERIC);
    },
    $originalData
);

benchmark(
    'Custom iterative QuickSort',
    function (array &$data): void {
        quickSort($data);
    },
    $originalData
);
