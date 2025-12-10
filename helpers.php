<?php

declare(strict_types=1);

function redirect($url)
{
    header("Location: {$url}");
    exit;
}

function toGHLFormat(string $phone): string
{
    $cleanPhone = preg_replace('/[^\d]/', '', $phone);

    if (strlen($cleanPhone) === 10) {
        $cleanPhone = '+1' . $cleanPhone;
    } elseif (!str_starts_with($cleanPhone, '+')) {
        $cleanPhone = '+' . $cleanPhone;
    }

    return $cleanPhone;
}

function toESTConversion($utcTimestamp)
{
    $utcDateTime = new DateTime($utcTimestamp, new DateTimeZone('UTC'));
    $estDateTime = $utcDateTime->setTimezone(new DateTimeZone('America/New_York'));

    return $estDateTime->format('Y-m-d\TH:i:sP');
}

function dollarsToCents($dollars)
{
    return $dollars * 100;
}

function centsToDollars($cents)
{
    $dollars =  $cents / 100;

    return round($dollars, 2);
}
