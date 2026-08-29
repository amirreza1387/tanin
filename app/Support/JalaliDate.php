<?php

namespace App\Support;

use DateTimeInterface;
use IntlDateFormatter;

final class JalaliDate
{
    public static function format(?DateTimeInterface $date, string $pattern = 'yyyy/MM/dd HH:mm'): ?string
    {
        if ($date === null) {
            return null;
        }

        if (class_exists(IntlDateFormatter::class)) {
            $formatter = new IntlDateFormatter(
                'fa_IR@calendar=persian',
                IntlDateFormatter::NONE,
                IntlDateFormatter::NONE,
                $date->getTimezone()->getName(),
                IntlDateFormatter::TRADITIONAL,
                $pattern,
            );

            $formatted = $formatter->format($date);
            if ($formatted !== false) {
                return $formatted;
            }
        }

        [$year, $month, $day] = self::toJalali(
            (int) $date->format('Y'),
            (int) $date->format('m'),
            (int) $date->format('d'),
        );

        return sprintf('%04d/%02d/%02d %s', $year, $month, $day, $date->format('H:i'));
    }

    private static function toJalali(int $year, int $month, int $day): array
    {
        $gregorianMonthDays = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
        $gregorianYear = $year - 1600;
        $gregorianMonth = $month - 1;
        $gregorianDay = $day - 1;

        $days = 365 * $gregorianYear
            + intdiv($gregorianYear + 3, 4)
            - intdiv($gregorianYear + 99, 100)
            + intdiv($gregorianYear + 399, 400)
            - 80
            + $gregorianDay
            + $gregorianMonthDays[$gregorianMonth];

        if ($gregorianMonth > 1 && (($gregorianYear % 4 === 0 && $gregorianYear % 100 !== 0) || $gregorianYear % 400 === 0)) {
            $days++;
        }

        $jalaliYear = 979 + 33 * intdiv($days, 12053);
        $days %= 12053;
        $jalaliYear += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $jalaliYear += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        if ($days < 186) {
            $jalaliMonth = 1 + intdiv($days, 31);
            $jalaliDay = 1 + ($days % 31);
        } else {
            $jalaliMonth = 7 + intdiv($days - 186, 30);
            $jalaliDay = 1 + (($days - 186) % 30);
        }

        return [$jalaliYear, $jalaliMonth, $jalaliDay];
    }
}
