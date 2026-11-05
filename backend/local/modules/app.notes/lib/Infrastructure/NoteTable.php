<?php

declare(strict_types=1);

namespace App\Notes\Infrastructure;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;

final class NoteTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'app_notes_note';
    }

    /**
     * @return list<\Bitrix\Main\ORM\Fields\ScalarField>
     */
    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))->configurePrimary()->configureAutocomplete(),
            new StringField('TITLE'),
            new TextField('CONTENT'),
            new DatetimeField('CREATED_AT'),
            new DatetimeField('UPDATED_AT'),
        ];
    }
}
