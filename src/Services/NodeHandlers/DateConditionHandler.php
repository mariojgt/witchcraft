<?php

namespace Mariojgt\Witchcraft\Services\NodeHandlers;

use DateTime;
use Carbon\Carbon;

class DateConditionHandler extends BaseNodeHandler
{
    public function handle(array $node, array $variables): array
    {
        $inputValue = $variables['extractedValue'] ?? $variables['dateValue'] ?? null;
        $comparisonType = $this->getData($node, 'comparisonType', 'equals');
        $dateUnit = $this->getData($node, 'dateUnit', 'exact');
        $expectedValue = $this->getData($node, 'expectedValue');
        $endValue = $this->getData($node, 'endValue');
        $useCurrentDate = $this->getData($node, 'useCurrentDate', false);

        try {
            // Parse input date
            $inputDate = $this->parseDate($inputValue);
            if (!$inputDate) {
                return $this->error("Invalid input date: {$inputValue}");
            }

            // Handle special comparison types that don't need expected values
            $result = match ($comparisonType) {
                'isToday' => $this->isToday($inputDate),
                'isWeekend' => $this->isWeekend($inputDate),
                'isWeekday' => $this->isWeekday($inputDate),
                'between' => $this->isBetween($inputDate, $expectedValue, $endValue, $dateUnit),
                default => $this->compareDate($inputDate, $expectedValue, $comparisonType, $dateUnit, $useCurrentDate)
            };

            $message = $this->buildMessage($comparisonType, $result, $inputDate, $expectedValue, $endValue, $dateUnit);

            return $this->success([
                'result' => $result,
                'inputDate' => $inputDate->toISOString(),
                'comparisonType' => $comparisonType,
                'dateUnit' => $dateUnit,
                'expectedValue' => $expectedValue,
                'extractedValue' => $inputValue // For edge routing
            ], $message) + ['conditionResult' => $result]; // Add for edge routing
        } catch (\Exception $e) {
            return $this->error("Date comparison failed: " . $e->getMessage());
        }
    }

    private function parseDate($value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            // Handle various date formats
            if (is_string($value)) {
                // Try common formats
                $formats = [
                    'Y-m-d H:i:s',
                    'Y-m-d\TH:i:s',
                    'Y-m-d\TH:i:s.u\Z',
                    'Y-m-d',
                    'H:i:s',
                    'H:i',
                    'd/m/Y',
                    'm/d/Y',
                    'U' // Unix timestamp
                ];

                foreach ($formats as $format) {
                    $date = DateTime::createFromFormat($format, $value);
                    if ($date) {
                        return Carbon::instance($date);
                    }
                }

                // Try Carbon's flexible parsing
                return Carbon::parse($value);
            }

            // Handle numeric timestamps
            if (is_numeric($value)) {
                return Carbon::createFromTimestamp($value);
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    private function compareDate(Carbon $inputDate, $expectedValue, string $comparisonType, string $dateUnit, bool $useCurrentDate): bool
    {
        if ($useCurrentDate) {
            $compareDate = Carbon::now();
        } else {
            $compareDate = $this->parseDate($expectedValue);
            if (!$compareDate) {
                throw new \Exception("Invalid expected date: {$expectedValue}");
            }
        }

        // Extract the relevant parts based on dateUnit
        $inputValue = $this->extractDateUnit($inputDate, $dateUnit);
        $expectedValue = $this->extractDateUnit($compareDate, $dateUnit);

        return match ($comparisonType) {
            'equals' => $inputValue == $expectedValue,
            'notEquals' => $inputValue != $expectedValue,
            'greaterThan' => $inputValue > $expectedValue,
            'greaterThanOrEqual' => $inputValue >= $expectedValue,
            'lessThan' => $inputValue < $expectedValue,
            'lessThanOrEqual' => $inputValue <= $expectedValue,
            default => false
        };
    }

    private function extractDateUnit(Carbon $date, string $unit)
    {
        return match ($unit) {
            'exact' => $date->timestamp,
            'date' => $date->format('Y-m-d'),
            'time' => $date->format('H:i:s'),
            'year' => $date->year,
            'month' => $date->month,
            'week' => $date->weekOfYear,
            'day' => $date->day,
            'hour' => $date->hour,
            'minute' => $date->minute,
            default => $date->timestamp
        };
    }

    private function isToday(Carbon $date): bool
    {
        return $date->isToday();
    }

    private function isWeekend(Carbon $date): bool
    {
        return $date->isWeekend();
    }

    private function isWeekday(Carbon $date): bool
    {
        return $date->isWeekday();
    }

    private function isBetween(Carbon $inputDate, $startValue, $endValue, string $dateUnit): bool
    {
        $startDate = $this->parseDate($startValue);
        $endDate = $this->parseDate($endValue);

        if (!$startDate || !$endDate) {
            throw new \Exception("Invalid start or end date for between comparison");
        }

        $inputValue = $this->extractDateUnit($inputDate, $dateUnit);
        $startValue = $this->extractDateUnit($startDate, $dateUnit);
        $endValue = $this->extractDateUnit($endDate, $dateUnit);

        return $inputValue >= $startValue && $inputValue <= $endValue;
    }

    private function buildMessage(string $comparisonType, bool $result, Carbon $inputDate, $expectedValue, $endValue, string $dateUnit): string
    {
        $dateStr = $inputDate->format('Y-m-d H:i:s');
        $resultStr = $result ? 'true' : 'false';

        return match ($comparisonType) {
            'isToday' => "Date {$dateStr} is today: {$resultStr}",
            'isWeekend' => "Date {$dateStr} is weekend: {$resultStr}",
            'isWeekday' => "Date {$dateStr} is weekday: {$resultStr}",
            'between' => "Date {$dateStr} is between {$expectedValue} and {$endValue} ({$dateUnit}): {$resultStr}",
            'equals' => "Date {$dateStr} equals {$expectedValue} ({$dateUnit}): {$resultStr}",
            'notEquals' => "Date {$dateStr} not equals {$expectedValue} ({$dateUnit}): {$resultStr}",
            'greaterThan' => "Date {$dateStr} after {$expectedValue} ({$dateUnit}): {$resultStr}",
            'greaterThanOrEqual' => "Date {$dateStr} after or equal {$expectedValue} ({$dateUnit}): {$resultStr}",
            'lessThan' => "Date {$dateStr} before {$expectedValue} ({$dateUnit}): {$resultStr}",
            'lessThanOrEqual' => "Date {$dateStr} before or equal {$expectedValue} ({$dateUnit}): {$resultStr}",
            default => "Date condition evaluated: {$resultStr}"
        };
    }
}
