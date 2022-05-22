<?php

namespace NovableResource\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo as EloquentBelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo as NovaBelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Nova;

/**
 * Trait NovableModel
 * @package App\Traits
 * @see \Laravel\Nova\Resource
 */
trait NovableResource
{
    protected static string $appNameSpace = 'App\\';
    protected static string $novaNameSpace = 'App\\Nova\\';
    protected static string $castsDefault = Text::class;
    protected static string $labelTranslationMany = 'model_title_many';

    protected array $castsCommon = [
        'bool' => Boolean::class,
        'boolean' => Boolean::class,
        'date' => Date::class,
        'datetime' => DateTime::class,
        'double' => Number::class,
        'float' => Number::class,
        'real' => Number::class,
        'int' => Number::class,
        'integer' => Number::class,
        'string' => Text::class,
        'timestamp' => DateTime::class,
    ];

    protected array $castsRelation = [
        EloquentBelongsTo::class => NovaBelongsTo::class,
    ];

    public function title(): string
    {
        $field = self::getNovableModelClassObject()->hasCast('name') ? 'name' : 'title';

        return (string)data_get($this, $field);
    }

    /**
     * @return array
     */
    public static function searchableColumns(): array
    {
        $columns = [];
        $variants = ['id', 'name', 'title'];
        foreach ($variants as $variant) {
            if (self::isNovableModelHasAttribute($variant)) {
                $columns[] = $variant;
            }
        }

        return $columns;
    }

    public static function label(): string
    {
        return __(self::getNovableModelTranslationPrefix() . self::$labelTranslationMany);
    }

    /**
     * Get the fields displayed by the resource.
     *
     * @param Request $request
     * @return array
     */
    public function fields(Request $request): array
    {
        $fields = [];
        foreach (self::getNovableModelClassObject()->getCasts() as $attribute => $type) {
            $field = $this->makeNovaFieldRelation($attribute)
                ?? $this->makeNovaFieldCommon($attribute, $type);

            $field->sortable();

            $fields[] = $field;
        }

        return $fields;
    }

    private function makeNovaFieldCommon(string $attribute, string $type): Field
    {
        /** @var Field $className */
        $className = $this->castsCommon[$type] ?? self::$castsDefault;
        $title = self::getNovableModelTranslationPrefix() . $attribute;

        return $className::make(__($title), $attribute);
    }

    private function makeNovaFieldRelation(string $attribute): ?Field
    {
        if (strpos($attribute, '_id') === false) {
            return null;
        }

        $relationName = substr($attribute, 0, strpos($attribute, '_id'));
        if (!method_exists(self::getNovableModelClassObject(), $relationName)) {
            return null;
        }

        /** @var Relation|null $relation */
        $relation = self::getNovableModelClassObject()->{$relationName}();
        if (empty($relation)) {
            return null;
        }

        $relatedClass = get_class($relation->getModel());
        $resourceClassName = self::findRelatedResourceClassName($relatedClass);
        if ($resourceClassName === null) {
            return null;
        }

        return self::getNovableFieldRelationResource($attribute, $relationName, $relation, $resourceClassName);
    }

    private static function getNovableModelClassObject(): Model
    {
        $className = static::$model;

        /** @var Model $model */
        $model = new $className();

        return $model;
    }

    private static function getNovableModelTranslationPrefix(): string
    {
        $className = static::$model;

        $prefix = str_replace(static::$appNameSpace, '', $className);
        $prefix = strtolower($prefix);
        $prefix = str_replace('\\', '/', $prefix);

        return $prefix . '.';
    }

    private static function findRelatedResourceClassName(string $relatedClassName)
    {
        foreach (Nova::$resources as $resourceClassName) {
            if (strpos($resourceClassName, self::$novaNameSpace) === false) {
                continue;
            }

            /** @var Resource $resourceClass */
            $resourceModel = new $resourceClassName(app());
            if ($resourceModel::$model === $relatedClassName) {
                return $resourceClassName;
            }
        }

        return null;
    }

    private static function getNovableFieldRelationResource(string $attribute, string $relationName, Relation $relation, string $resourceClassName): ?Field
    {
        if ($relation instanceof EloquentBelongsTo) {
            $label = __(self::getNovableModelTranslationPrefix() . $attribute);
            return NovaBelongsTo::make($label, $relationName, $resourceClassName);
        }

        return null;
    }

    private static function isNovableModelHasAttribute($attribute): bool
    {
        return self::getNovableModelClassObject()->hasCast($attribute);
    }

}
