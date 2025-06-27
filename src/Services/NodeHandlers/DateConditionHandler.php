<?php

namespace Mariojgt\Witchcraft\Services\NodeHandlers;

use DateTime;
use Carbon\Carbon;
use Exception; // Import Exception for clarity

class DateConditionHandler extends BaseNodeHandler
{
    /**
     * Handles the date comparison logic based on node configuration and variables.
     *
     * @param array $node The node configuration.
     * @param array $variables The variables passed to the handler, including 'extractedValue' or 'dateValue'.
     * @return array The result of the comparison, including success/error status and data.
     */
    public function handle(array $node, array $variables): array
    {
        $inputValue = $variables['extractedValue'] ?? $variables['dateValue'] ?? null;
        $comparisonType = $this->getData($node, 'comparisonType', 'equals');
        $dateUnit = $this->getData($node, 'dateUnit', 'exact');
        $expectedValue = $this->getData($node, 'expectedValue');
        $endValue = $this->getData($node, 'endValue');
        $useCurrentDate = $this->getData($node, 'useCurrentDate', false);

        try {
            // Parse input date and set to UTC for consistent internal handling
            $inputDate = $this->parseDate($inputValue);
            if (!$inputDate) {
                return $this->error("Invalid input date provided: '{$inputValue}'. Please ensure it's a valid date or timestamp.");
            }
            $inputDate->setTimezone('UTC');

            // Handle special comparison types that don't need expected values or have specific logic
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
                'inputDate' => $inputDate->toISOString(), // Output ISO 8601 for consistency
                'comparisonType' => $comparisonType,
                'dateUnit' => $dateUnit,
                'expectedValue' => $expectedValue,
                'endValue' => $endValue, // Include endValue in success data
                'extractedValue' => $inputValue // Original input value for potential debugging/routing
            ], $message) + ['conditionResult' => $result]; // Add for edge routing in workflows
        } catch (Exception $e) { // Catch the general Exception for all parsing/comparison failures
            // Log the error for internal debugging purposes
            // \Log::error("Date comparison failed in DateConditionHandler: " . $e->getMessage(), [
            //     'node' => $node,
            //     'variables' => $variables
            // ]);
            return $this->error("Date comparison failed: " . $e->getMessage());
        }
    }

    /**
     * Parses a given value into a Carbon instance.
     * Tries various common formats and falls back to Carbon's intelligent parser.
     * All parsed dates are normalized to UTC.
     *
     * @param mixed $value The date value to parse (string or numeric timestamp).
     * @return Carbon|null A Carbon instance if successful, null otherwise.
     */
    private function parseDate($value): ?Carbon
    {
        // Parse the value into a Carbon instance
        $data = Carbon::parse($value, 'UTC'); // Parse directly to UTC
        if ($data instanceof Carbon) {
            return $data; // Return the Carbon instance if parsing was successful
        } else {
            // If parsing fails, return null
            return null;
        }
    }

    /**
     * Compares an input date with an expected date based on a comparison type and date unit.
     * All dates are internally converted to UTC for consistent comparison.
     *
     * @param Carbon $inputDate The date to compare.
     * @param mixed $expectedValue The value to compare against (string, numeric, or Carbon).
     * @param string $comparisonType The type of comparison (e.g., 'equals', 'greaterThan').
     * @param string $dateUnit The unit to compare by (e.g., 'exact', 'date', 'year').
     * @param bool $useCurrentDate Whether to compare against the current date/time instead of expectedValue.
     * @return bool The result of the comparison.
     * @throws Exception If expected date value is invalid.
     */
    private function compareDate(Carbon $inputDate, $expectedValue, string $comparisonType, string $dateUnit, bool $useCurrentDate): bool
    {
        // $inputDate is already set to UTC in handle() method

        if ($useCurrentDate) {
            $compareDate = Carbon::now('UTC'); // Use current time in UTC
        } else {
            $compareDate = $this->parseDate($expectedValue);
            if (!$compareDate) {
                throw new Exception("Invalid expected date value '{$expectedValue}' for comparison. Please provide a valid date format.");
            }
            // $compareDate is already set to UTC by parseDate()
        }

        // Extract the relevant parts based on dateUnit for comparison
        $inputValueExtracted = $this->extractDateUnit($inputDate, $dateUnit);
        $expectedValueExtracted = $this->extractDateUnit($compareDate, $dateUnit);

        return match ($comparisonType) {
            'equals' => $inputValueExtracted == $expectedValueExtracted,
            'notEquals' => $inputValueExtracted != $expectedValueExtracted,
            'greaterThan' => $inputValueExtracted > $expectedValueExtracted,
            'greaterThanOrEqual' => $inputValueExtracted >= $expectedValueExtracted,
            'lessThan' => $inputValueExtracted < $expectedValueExtracted,
            'lessThanOrEqual' => $inputValueExtracted <= $expectedValueExtracted,
            default => false // Should not happen with defined comparison types
        };
    }

    /**
     * Extracts a specific unit from a Carbon date.
     *
     * @param Carbon $date The Carbon instance.
     * @param string $unit The unit to extract (e.g., 'year', 'month', 'timestamp').
     * @return mixed The extracted value.
     */
    private function extractDateUnit(Carbon $date, string $unit)
    {
        // Carbon instance is expected to be in UTC here
        return match ($unit) {
            'exact' => $date->timestamp, // Unix timestamp for exact comparison
            'date' => $date->format('Y-m-d'), // Date part only
            'time' => $date->format('H:i:s'), // Time part only
            'year' => $date->year,
            'month' => $date->month,
            'week' => $date->weekOfYear, // Week of the year (1-53)
            'day' => $date->day,         // Day of the month (1-31)
            'hour' => $date->hour,
            'minute' => $date->minute,
            default => $date->timestamp // Fallback to timestamp for safety
        };
    }

    /**
     * Checks if the given date is today (in the current system's local timezone for practical meaning,
     * but the input $date itself is assumed to be UTC).
     *
     * @param Carbon $date The Carbon instance (assumed UTC from parseDate).
     * @return bool True if the date is today, false otherwise.
     */
    private function isToday(Carbon $date): bool
    {
        // For 'isToday', 'isWeekend', 'isWeekday', Carbon's methods implicitly
        // consider the application's default timezone if not explicitly set on the Carbon instance.
        // If $date is already UTC, comparing against Carbon::now() (which uses default timezone)
        // might lead to off-by-one day issues around midnight.
        // It's safer to convert $date to the *application's default timezone* for these specific checks.
        $dateInAppTimezone = $date->copy()->setTimezone(config('app.timezone'));
        return $dateInAppTimezone->isToday();
    }

    /**
     * Checks if the given date is a weekend.
     *
     * @param Carbon $date The Carbon instance (assumed UTC from parseDate).
     * @return bool True if the date is a weekend, false otherwise.
     */
    private function isWeekend(Carbon $date): bool
    {
        $dateInAppTimezone = $date->copy()->setTimezone(config('app.timezone'));
        return $dateInAppTimezone->isWeekend();
    }

    /**
     * Checks if the given date is a weekday.
     *
     * @param Carbon $date The Carbon instance (assumed UTC from parseDate).
     * @return bool True if the date is a weekday, false otherwise.
     */
    private function isWeekday(Carbon $date): bool
    {
        $dateInAppTimezone = $date->copy()->setTimezone(config('app.timezone'));
        return $dateInAppTimezone->isWeekday();
    }

    /**
     * Checks if the input date is between a start and end date (inclusive).
     * All dates are internally converted to UTC for consistent comparison.
     *
     * @param Carbon $inputDate The date to check.
     * @param mixed $startValue The start date value.
     * @param mixed $endValue The end date value.
     * @param string $dateUnit The unit to compare by.
     * @return bool The result of the comparison.
     * @throws Exception If start or end date values are invalid.
     */
    private function isBetween(Carbon $inputDate, $startValue, $endValue, string $dateUnit): bool
    {
        // $inputDate is already set to UTC in handle() method

        $startDate = $this->parseDate($startValue);
        $endDate = $this->parseDate($endValue);

        if (!$startDate) {
            throw new Exception("Invalid start date value '{$startValue}' for 'between' comparison.");
        }
        if (!$endDate) {
            throw new Exception("Invalid end date value '{$endValue}' for 'between' comparison.");
        }

        // $startDate and $endDate are already set to UTC by parseDate()

        // Extract the relevant parts based on dateUnit for comparison
        $inputValueExtracted = $this->extractDateUnit($inputDate, $dateUnit);
        $startValueExtracted = $this->extractDateUnit($startDate, $dateUnit);
        $endValueExtracted = $this->extractDateUnit($endDate, $dateUnit);

        // Ensure start and end are in the correct order for comparison
        if ($startValueExtracted > $endValueExtracted) {
            [$startValueExtracted, $endValueExtracted] = [$endValueExtracted, $startValueExtracted];
        }

        return $inputValueExtracted >= $startValueExtracted && $inputValueExtracted <= $endValueExtracted;
    }

    /**
     * Builds a human-readable message based on the comparison result.
     *
     * @param string $comparisonType The type of comparison performed.
     * @param bool $result The boolean result of the comparison.
     * @param Carbon $inputDate The input date used for comparison (assumed UTC, but displayed in a readable format).
     * @param mixed $expectedValue The expected value used for comparison.
     * @param mixed $endValue The end value used for 'between' comparison.
     * @param string $dateUnit The unit of comparison.
     * @return string The generated message.
     */
    private function buildMessage(string $comparisonType, bool $result, Carbon $inputDate, $expectedValue, $endValue, string $dateUnit): string
    {
        // Display the input date in a user-friendly format, potentially adjusting to app timezone
        $displayInputDate = $inputDate->copy()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s');
        $resultStr = $result ? 'true' : 'false';
        $unitDesc = $dateUnit === 'exact' ? 'exact datetime' : $dateUnit;

        // Try to format expected and end values if they are valid dates, otherwise use raw value
        $formattedExpected = $this->tryFormatValue($expectedValue, $dateUnit);
        $formattedEnd = $this->tryFormatValue($endValue, $dateUnit);


        return match ($comparisonType) {
            'isToday' => "Date {$displayInputDate} is today: {$resultStr}",
            'isWeekend' => "Date {$displayInputDate} is a weekend: {$resultStr}",
            'isWeekday' => "Date {$displayInputDate} is a weekday: {$resultStr}",
            'between' => "Date {$displayInputDate} ({$unitDesc}) is between {$formattedExpected} and {$formattedEnd}: {$resultStr}",
            'equals' => "Date {$displayInputDate} ({$unitDesc}) equals {$formattedExpected}: {$resultStr}",
            'notEquals' => "Date {$displayInputDate} ({$unitDesc}) not equals {$formattedExpected}: {$resultStr}",
            'greaterThan' => "Date {$displayInputDate} ({$unitDesc}) is after {$formattedExpected}: {$resultStr}",
            'greaterThanOrEqual' => "Date {$displayInputDate} ({$unitDesc}) is after or equal {$formattedExpected}: {$resultStr}",
            'lessThan' => "Date {$displayInputDate} ({$unitDesc}) is before {$formattedExpected}: {$resultStr}",
            'lessThanOrEqual' => "Date {$displayInputDate} ({$unitDesc}) is before or equal {$formattedExpected}: {$resultStr}",
            default => "Date condition evaluated: {$resultStr}"
        };
    }

    /**
     * Helper to attempt formatting a value for display in the message.
     *
     * @param mixed $value The value to format.
     * @param string $dateUnit The unit of comparison.
     * @return string The formatted value or the original value if parsing fails.
     */
    private function tryFormatValue($value, string $dateUnit): string
    {
        if (is_numeric($value) && in_array($dateUnit, ['year', 'month', 'week', 'day', 'hour', 'minute'])) {
            return (string) $value; // Return numeric as string
        }

        $date = $this->parseDate($value); // Attempt to parse for formatting
        if (!$date) {
            return (string) $value; // Return original if cannot parse
        }

        // Format based on date unit for better readability in the message
        return match ($dateUnit) {
            'exact' => $date->copy()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
            'date' => $date->copy()->setTimezone(config('app.timezone'))->format('Y-m-d'),
            'time' => $date->copy()->setTimezone(config('app.timezone'))->format('H:i:s'),
            'year' => (string) $date->year,
            'month' => (string) $date->month,
            'week' => (string) $date->weekOfYear,
            'day' => (string) $date->day,
            'hour' => (string) $date->hour,
            'minute' => (string) $date->minute,
            default => $date->copy()->setTimezone(config('app.timezone'))->format('Y-m-d H:i:s'),
        };
    }
}
