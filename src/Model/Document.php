<?php

namespace BimTheBam\Meilisearch\Model;

use Psr\Container\NotFoundExceptionInterface;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\FieldType\DBBoolean;
use SilverStripe\ORM\FieldType\DBDate;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\ORM\FieldType\DBHTMLVarchar;
use SilverStripe\ORM\FieldType\DBTime;

/**
 * Class Document
 * @package BimTheBam\Meilisearch\Model
 */
class Document
{
    use Injectable;

    /**
     * @var int
     */
    public readonly int $id;

    /**
     * @param DataObject $record
     */
    public function __construct(public readonly DataObject $record)
    {
        $this->id = $this->record->ID;
    }

    /**
     * @param string $class
     * @param bool $fieldNames
     * @return array|null
     * @throws NotFoundExceptionInterface
     */
    public static function get_searchable_fields(string $class, bool $fieldNames = true): ?array
    {
        $searchable_fields = [];

        $classes = [];
        $fields = [];

        foreach (ClassInfo::getValidSubClasses($class) as $subClass) {
            $searchableFields = Config::inst()->get($subClass, 'meilisearch_searchable_fields') ?? [];

            if ($fieldNames) {
                $fields = array_merge($fields, array_keys($searchableFields));
            } else {
                $fields = array_merge($fields, $searchableFields);
            }

            $classes[] = $subClass;
        }

        if (empty($fields)) {
            foreach ($classes as $subClass) {
                /** @var DataObject $sng */
                $sng = Injector::inst()->get($subClass);

                if ($sng->hasField('Title') && !in_array('Title', $fields)) {
                    $fields['Title'] = 'Title';
                }

                if ($sng->hasField('Content') && !in_array('Content', $fields)) {
                    $fields['Content'] = 'Content';
                }

                if (in_array('Title', $fields) && in_array('Content', $fields)) {
                    break;
                }
            }
        }

        if ($fieldNames) {
            $fields = array_values(array_unique($fields));
        }

        $fields = array_filter($fields, fn ($field) => !in_array($field, ['ID', 'ClassName']));

        if (empty($fields)) {
            $fields = null;
        }

        foreach ($classes as $subClass) {
            $searchable_fields[$subClass] = $fields;
        }

        return $searchable_fields[$class];
    }

    /**
     * @param string $class
     * @param bool $fieldNames
     * @return array|null
     */
    public static function get_filterable_fields(string $class, bool $fieldNames = true): ?array
    {
        $filterable_fields = [];

        $classes = [];
        $fields = [];

        foreach (ClassInfo::getValidSubClasses($class) as $subClass) {
            $filterableFields = Config::inst()->get($subClass, 'meilisearch_filterable_fields') ?? [];

            if ($fieldNames) {
                $fields = array_merge($fields, array_keys($filterableFields));
            } else {
                $fields = array_merge($fields, $filterableFields);
            }

            $classes[] = $subClass;
        }

        if ($fieldNames) {
            $fields = array_values(array_unique($fields));
        }

        $fields = array_filter($fields, fn ($field) => !in_array($field, ['ID', 'ClassName']));

        if (empty($fields)) {
            $fields = null;
        }

        foreach ($classes as $subClass) {
            $filterable_fields[$subClass] = $fields;
        }

        return $filterable_fields[$class];
    }

    /**
     * @param string $class
     * @return array|null
     */
    public static function get_sortable_fields(string $class): ?array
    {
        $sortable_fields = [];

        $classes = [];
        $fields = [];

        foreach (ClassInfo::getValidSubClasses($class) as $subClass) {
            $sortableFields = Config::inst()->get($subClass, 'meilisearch_sortable_fields') ?? [];

            $fields = array_merge($fields, $sortableFields);

            $classes[] = $subClass;
        }

        $fields = array_values(array_unique($fields));

        if (empty($fields)) {
            $fields = null;
        }

        foreach ($classes as $subClass) {
            $sortable_fields[$subClass] = $fields;
        }

        return $sortable_fields[$class];
    }

	/**
	 * @param string $class
	 * @param array $settings
	 * @return array|null
	 */
	public static function additional_settings(string $class, array $settings): ?array {
		// add more items if needed
		foreach(['distinctAttribute', 'faceting', 'rankingRules'] as $key) {
			$settings_field = [];
			$classes = [];
			$fields = [];
			foreach (ClassInfo::getValidSubClasses($class) as $subClass) {
				$config = Config::inst()->get($subClass, 'meilisearch_'.$key) ?? null;
				$fields = is_array($config) && is_array($fields) ? array_merge($fields, $config) : $config;
				$classes[] = $subClass;
			}

			$fields = is_array($fields) ? array_unique($fields) : $fields;
			if (empty($fields)) {
				$fields = null;
			}
			foreach ($classes as $subClass) {
				$settings_field[$subClass] = $fields;
			}
			if( $settings_field[$class]) {
				$settings[$key] = $settings_field[$class];
			}
		}
		return $settings;
	}

    /**
     * @return array
     * @throws NotFoundExceptionInterface
     */
    public function toArray(): array
    {
        $fields = array_merge(
            static::get_searchable_fields($this->record::class, false) ?? [],
            static::get_filterable_fields($this->record::class, false) ?? [],
        );

        $fields = array_unique($fields);

        $data = [];

        foreach ($fields as $fieldName => $field) {
            $fieldValue = null;

            if ($this->record->hasMethod($field)) {
                $fieldValue = $this->record->$field();
            } elseif ($this->record->hasMethod('relField')) {
                $fieldValue = $this->record->relField($field);
            }

            if (!is_object($fieldValue)) {
                $fieldValue = Injector::inst()->create($this->record->castingHelper($field), $field)
                    ->setValue($fieldValue, $this->record);
            }

            $fieldObj = $fieldValue;

            if (($fieldObj instanceof DBHTMLVarchar) || ($fieldObj instanceof DBHTMLText)) {
                $value = strip_tags($fieldObj->RAW() ?? '');
            } elseif (($fieldObj instanceof DBBoolean)) {
                $value = (bool)$fieldObj->getValue();
            } elseif (($fieldObj instanceof DBDate) || ($field instanceof DBTime)) {
                $value = $fieldObj->getTimestamp();
            } else {
                $value = $fieldObj->getValue();
            }

            $data[$fieldName] = $value;
        }

        return array_merge(
            [
                'ID' => $this->record->ID,
                'ClassName' => $this->record->ClassName,
            ],
            $data
        );
    }
}
