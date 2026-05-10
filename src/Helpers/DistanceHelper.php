<?php

namespace App\Helpers;

class DistanceHelper
{
    /**
     * Рассчитывает расстояние между двумя точками по координатам
     * @param float $lat1 Широта первой точки
     * @param float $lng1 Долгота первой точки
     * @param float $lat2 Широта второй точки
     * @param float $lng2 Долгота второй точки
     * @return float Расстояние в километрах
     */
    public static function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // радиус Земли в км
        
        $lat1 = deg2rad($lat1);
        $lng1 = deg2rad($lng1);
        $lat2 = deg2rad($lat2);
        $lng2 = deg2rad($lng2);
        
        $latDelta = $lat2 - $lat1;
        $lngDelta = $lng2 - $lng1;
        
        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($lat1) * cos($lat2) *
             sin($lngDelta / 2) * sin($lngDelta / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }
}