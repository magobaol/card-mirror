<?php

namespace Model;

class CardName
{
    public static function isValid($name): bool
    {
        return preg_match('/^PIC-[0-9]{4}$/', $name);
    }

    public static function getSample(): string
    {
        return 'PIC-0001';
    }
}