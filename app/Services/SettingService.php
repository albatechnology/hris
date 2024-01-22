<?php

namespace App\Services;

use App\Enums\SettingKey;
use App\Enums\SettingValueType;
use App\Models\Setting;

class SettingService
{

    public static function getFileTypes()
    {
        $fileTypes = self::getValueOf(SettingKey::FILE_TYPE);
        if (is_array($fileTypes) && count($fileTypes) > 0) {
            return '(' . implode(', ', $fileTypes) . ')';
        }
        return '';
    }
    /**
     * @param SettingKey|string $key
     * @return mixed
     */
    public static function getValueOf(SettingKey|string $key, $expectedValue = null): mixed
    {
        if ($key instanceof SettingKey) {
            $key = $key->value;
        }

        $setting = Setting::firstWhere('key', $key);
        if (!$setting) return $expectedValue;
        return self::get($setting);
    }

    public static function get(Setting $setting, $value = null)
    {
        $value = is_null($value) ? $setting->value : $value;
        if ($setting->value_type->is(SettingValueType::STRING)) {
            return (string) $value;
        } elseif ($setting->value_type->is(SettingValueType::ARRAY) && $setting->key->is(SettingKey::FILE_TYPE)) {
            return json_decode($value ?? []);
        } elseif ($setting->value_type->is(SettingValueType::BOOL)) {
            return (bool) $value;
        } elseif ($setting->value_type->is(SettingValueType::ARRAY)) {
            return json_decode($value ?? []);
        } elseif ($setting->value_type->is(SettingValueType::OPTIONS)) {
            return $value;
        }
    }

    public static function set(Setting $setting, $value = null)
    {
        $value = is_null($value) ? $setting->value : $value;
        if ($setting->value_type->is(SettingValueType::STRING)) {
            return (string) $value;
        } elseif ($setting->value_type->is(SettingValueType::ARRAY) && $setting->key->is(SettingKey::FILE_TYPE)) {
            return json_encode($value ?? []);
        } elseif ($setting->value_type->is(SettingValueType::BOOL)) {
            return (bool) $value;
        } elseif ($setting->value_type->is(SettingValueType::ARRAY)) {
            return json_encode($value ?? []);
        } elseif ($setting->value_type->is(SettingValueType::OPTIONS)) {
            return $value;
        }
    }

    public static function buildForm(Setting $setting)
    {
        if ($setting->value_type->is(SettingValueType::STRING)) {
            return self::string($setting);
        } elseif ($setting->value_type->is(SettingValueType::ARRAY) && $setting->key->is(SettingKey::FILE_TYPE)) {
            return self::multipleSelectTags($setting);
        } elseif ($setting->value_type->is(SettingValueType::BOOL)) {
            return self::toggle($setting);
        } elseif ($setting->value_type->is(SettingValueType::ARRAY)) {
            return self::multipleSelect($setting);
        } elseif ($setting->value_type->is(SettingValueType::OPTIONS)) {
            return self::singleSelect($setting);
        }
    }

    public static function string(Setting $setting)
    {
        return '<input type="text" name="value" class="form-control" value="' . $setting->value . '" required>';
    }

    public static function toggle(Setting $setting)
    {
        return '<div class="form-group clearfix">
        <div class="icheck-primary d-inline">
        <input type="radio" id="' . $setting->key->value . '-1" name="value" value="1" ' . ((bool) $setting->value == true ? 'checked' : '') . '>
        <label for="' . $setting->key->value . '-1">ON</label>
        </div>
        </br>
        <div class="icheck-primary d-inline">
        <input type="radio" id="' . $setting->key->value . '-2" name="value" value="0" ' . ((bool) $setting->value == false ? 'checked' : '') . '>
        <label for="' . $setting->key->value . '-2">OFF</label>
        </div>';
        // return '<input name="value" type="checkbox" ' . $checked . '>';
    }

    public static function singleSelect(Setting $setting)
    {
        $options = ["a1", "a2", "a3", "s4"];
        $html = '<select name="value" class="form-control select2" required>';
        foreach ($options as $value) {
            $checked = $setting->value == $value ? 'selected' : '';
            $html .= '<option value="' . $value . '" ' . $checked . '>' . $value . '</option>';
        }
        $html .= '</select>';
        return $html;
    }

    public static function multipleSelect(Setting $setting)
    {
        $html = '<select name="value[]" class="form-control select2" multiple required>';
        // foreach ($setting->value as $value) {
        //     $html .= '<option value="' . $value . '">' . $value . '</option>';
        // }
        $html .= '</select>';
        return $html;
    }

    public static function multipleSelectTags(Setting $setting)
    {
        $html = '<select name="value[]" class="form-control select2Tags" multiple required style="width: 100%">';
        foreach (json_decode($setting->value ?? "[]") as $value) {
            $html .= '<option value="' . $value . '" selected>' . $value . '</option>';
        }
        $html .= '</select>';
        return $html;
    }
}
