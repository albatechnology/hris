<?php

namespace App\Enums;

use Illuminate\Support\Str;

trait BaseEnum
{
    public function getLabel(): string
    {
        return Str::of($this->name)
            ->lower()
            ->pipe(fn ($str) => Str::headline($str))
            ->when($this->convertSymbols(), fn ($str) => $str->replace(' Or ', '/'))
            ->when($this->convertSymbols(), fn ($str) => $str->replace(' And ', ' & '))
            ->value();
    }

    public static function getValues(): array
    {
        return collect(self::cases())->map(fn ($enum) => $enum->value)->all();
    }

    public static function getValue(?string $value = null): ?self
    {
        if (empty($value)) {
            return null;
        }

        return collect(self::cases())->first(fn ($enum) => $enum->name == $value || $enum->value == $value);
    }

    public function getDescription(): string
    {
        return $this->getLabel();
    }

    public static function all(): array
    {
        $return = [];
        foreach (self::cases() as $enum) {
            $return[$enum->value] = $enum->getLabel();
        }

        return $return;
    }

    public function is(int|string|\BackedEnum $value): bool
    {
        if ($value instanceof \BackedEnum) {
            return $this === $value;
        }

        return $this->value === $value;
    }

    public function in(array $values): bool
    {
        return in_array($this, $values);
        // return in_array(str_replace('-', '_', $value), self::getValues());
    }

    protected function convertSymbols(): bool
    {
        return true;
    }
}
