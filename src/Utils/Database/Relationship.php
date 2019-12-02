<?php

namespace Thomisticus\Generator\Utils\Database;

//use ICanBoogie\Inflector;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Thomisticus\Generator\Utils\CommandData;
use Thomisticus\Generator\Utils\GeneratorConfig;

class Relationship
{
    /**
     * Relationship type: '1t1' (One to One), '1tm' (One to Many), 'mt1' (Many to One), 'mtm' (Many to Many)
     * @var string
     */
    public $type;

    /**
     * Relationship inputs
     * @var array
     */
    public $inputs;

    /**
     * Custom relationship method name
     * @var string
     */
    public $relationName;

    /**
     * @var array
     */
    public $additionalParams;

    /**
     * Set and used  when getRelationFunctionText is called from ModelGenerator
     * @var CommandData
     */
    public $commandData;

    /**
     * Parse and returns the database relationships of a field
     *
     *
     * @param string $relationInput Relation input comma separated.
     *                              One to One (Eg: 1t1,Phone,user_id,id)
     *                              One to Many (Eg: 1tm,Comment,post_id,id)
     *                              Many to One (Eg: mt1,Post,post_id)
     *                              Many to Many (Eg: mtm,Role,user_roles,user_id,role_id)
     * @param array $additionalParams Array with params like 'foreignKey', 'ownerKey','localKey',
     *                                'foreignPivotKey','relatedPivotKey'
     * @return Relationship
     */
    public static function parseRelation($relationInput, $additionalParams = [])
    {
        $inputs = explode(',', $relationInput);

        $relation = new self();
        $relation->type = array_shift($inputs);
        $modelWithRelation = explode(':', array_shift($inputs)); //e.g ModelName:relationName
        if (count($modelWithRelation) == 2) {
            $relation->relationName = $modelWithRelation[1];
            unset($modelWithRelation[1]);
        }
        $relation->inputs = array_merge($modelWithRelation, $inputs);
        $relation->additionalParams = $additionalParams;

        return $relation;
    }

    /**
     * Treat the relationship field text considering the pivot table name and custom foreign key names
     * before generating the relation.
     * This method is useful to avoid weird method names like: "item1s()" and make it more readable.
     *
     * @param Relationship $relationship
     * @return mixed|string|null
     */
    public function treatRelationshipFieldText(Relationship $relationship)
    {
        $field = (isset($relationship->inputs[0])) ? $relationship->inputs[0] : null;

        $searchModelNames = $this->getModelNamesForRelationshipFunctionTreatment($field);

        // If contains pivot table. Usually will enter here only for many to many relationships
        if (!empty($relationship->inputs[1])) {
            $field = str_replace($searchModelNames['localModelNames'], '', $relationship->inputs[1]);
            return model_name_from_table_name($field);
        }

        $searchModelNames = Arr::flatten($searchModelNames);
        $relationFk = $relationship->additionalParams['foreignKey'] ?? null;
        $relationOk = $relationship->additionalParams['ownerKey'] ?? null;

        // If relationship is made with a custom column name other than eg: 'tablename_id'
        if ($relationFk && !Str::contains($relationFk, $searchModelNames)) {
            $relationFkText = collect(explode('_', $relationFk))->filter(function ($word) use ($relationOk) {
                return strtolower($word) != strtolower($relationOk);
            })->implode('_');

            $renamedField = model_name_from_table_name($relationFkText);

            // In case the model already have a property/column with the same name of the created method
            // It will append $field into method's name.
            if (in_array(strtolower($renamedField), array_column($this->commandData->fields, 'name'))) {
                $renamedField = $renamedField . $field;
            }

            $field = $renamedField;
        }

        return $field;
    }

    /**
     * Retrieves an array of model names that will be useful to verify the necessity of additional params in the
     * relationship method or not.
     *
     * @param string $relatedModel The name of the related model
     * @return array
     */
    private function getModelNamesForRelationshipFunctionTreatment($relatedModel)
    {
        $modelNameTypes = ['snake_plural', 'snake_singular', 'snake'];
        $localModelNames = array_reverse(Arr::only($this->commandData->config->modelNames, $modelNameTypes));

        $relatedModelNames = GeneratorConfig::prepareModelNames($relatedModel);
        $relatedModelNames = array_reverse(Arr::only($relatedModelNames, $modelNameTypes));

        return [
            'localModelNames' => array_values($localModelNames),
            'relatedModelNames' => array_values($relatedModelNames)
        ];
    }

    /**
     * Retrieves the relationship function text
     *
     * @param string|null $relationText Relationship's custom name
     * @return mixed|string
     */
    public function getRelationFunctionText($relationText = null)
    {
        $relationAttr = $this->getRelationAttributes($relationText, $this->commandData->config->modelName);

        if (!empty($relationAttr['functionName']) && !empty($relationAttr['relation'])) {
            return $this->generateRelation(
                $relationAttr['functionName'],
                $relationAttr['relation'],
                $relationAttr['relationClass']
            );
        }

        return '';
    }

    /**
     * Treats the name of the relationship method, removing the name of the Model whom owns the method.
     * It's useful to create relationship names more similar to a real case.
     *
     * @param string $relationText
     * @param string $modelOwnerName Model name of the class that owns the relationship method
     * @param bool $plural If it's to generate a plural method name or not
     * @return string
     */
    public function treatRelationFunctionName($relationText, $modelOwnerName, $plural = false)
    {
        if (!empty($this->relationName)) {
            return $this->relationName;
        }

        if ($modelOwnerName && Str::contains($relationText, $modelOwnerName)) {
            $relationText = str_replace([Str::plural($modelOwnerName), $modelOwnerName], '', $relationText);
        }

        // $inflector = Inflector::get('pt');
        // $relationText = $plural ? $inflector->pluralize($relationText) : $relationText;
        $relationText = $plural ? Str::plural($relationText) : $relationText;

        return Str::camel($relationText);
    }

    /**
     * Retrieves the relations attributes to fill the relationship method text
     * (function, functionName and relationClass)
     *
     * @param string|null $relationText
     * @param string|null $modelOwnerName Model name of the class that owns the relationship method
     * @return array
     */
    public function getRelationAttributes($relationText = null, $modelOwnerName = null)
    {
        $singularRelation = $this->treatRelationFunctionName($relationText, $modelOwnerName);
        $pluralRelation = $this->treatRelationFunctionName($relationText, $modelOwnerName, true);

        $relationTypeFunctions = [
            '1t1' => [$singularRelation, 'hasOne'],
            '1tm' => [$pluralRelation, 'hasMany'],
            'mt1' => [$singularRelation, 'belongsTo'],
            'mtm' => [$pluralRelation, 'belongsToMany'],
            'hmt' => [$pluralRelation, 'hasManyThrough'],
        ];

        if ($this->type == 'mt1') {
            if (!empty($this->relationName)) {
                $relationTypeFunctions['mt1'][0] = $this->relationName;
            } elseif (isset($this->inputs[1])) {
                $relationTypeFunctions['mt1'][0] = Str::camel(str_replace('_id', '', strtolower($this->inputs[1])));
            }
        }

        $isValidRelation = !empty($relationTypeFunctions[$this->type]);

        return [
            'functionName' => $isValidRelation ? $relationTypeFunctions[$this->type][0] : '',
            'relation' => $isValidRelation ? $relationTypeFunctions[$this->type][1] : '',
            'relationClass' => $isValidRelation ? ucfirst($relationTypeFunctions[$this->type][1]) : ''
        ];
    }

    /**
     * Generates the model relationship text, replacing the variables in the stub file
     *
     * @param string $functionName Relationship's method name
     * @param string $relation Eloquent relationship method to be called
     * @param string $relationClass Eloquent relationship class
     * @return string
     */
    private function generateRelation($functionName, $relation, $relationClass)
    {
        $inputsArray = $this->inputs;
        $modelName = array_shift($inputsArray);

        $inputFields = '';
        if (count($inputsArray) > 0) {
            $inputFields = ", '" . implode("', '", $inputsArray) . "'";
        }

        if (!empty($this->additionalParams) && $this->validateAdditionalParams($functionName, $relation, $modelName)) {
            ksort($this->additionalParams);
            $inputFields .= ", '" . implode("', '", $this->additionalParams) . "'";
        }

        $template = get_template('api.model.relationship', 'app-generator');

        $replacers = [
            '$RELATIONSHIP_CLASS$' => $relationClass,
            '$FUNCTION_NAME$' => $functionName,
            '$RELATION$' => $relation,
            '$RELATION_MODEL_NAME$' => $modelName,
            '$INPUT_FIELDS$' => strtolower($inputFields)
        ];

        return str_replace(array_keys($replacers), $replacers, $template);
    }


    /**
     * Validate the additional parameters that will take place or not in the relationship methods.
     * If the parameters already follow the standard name for each type of relationship, they won't be added, otherwise
     * they will.
     *
     * @param string $functionName relationship function name
     * @param string $relationType relationship type (eg: 'hasMany', 'hasOne', 'belongsTo', 'belongsToMany')
     * @param string $relatedModel related model name
     * @return bool
     */
    private function validateAdditionalParams($functionName, $relationType, $relatedModel)
    {
        if ($relationType === 'hasMany' || $relationType === 'hasOne') {
            $this->validateHasOneOrHasManyParams();
        }

        if ($relationType === 'belongsTo') {
            $this->validateBelongsToParams($functionName);
        }

        if ($relationType === 'belongsToMany') {
            $this->validateBelongsToManyParams($relatedModel);
        }

        $this->additionalParams = array_filter($this->additionalParams);

        return !empty($this->additionalParams);
    }

    /**
     * For hasMany and hasOne, $foreignKey = column of OTHER table that connects to THIS
     * Format: Str::snake(class_basename($this)).'_'.$this->getKeyName()
     * $localKey = primary key of THIS table
     */
    private function validateHasOneOrHasManyParams()
    {
        if (
            ForeignKey::isDefaultForeignKeyName(
                $this->additionalParams['foreignKey'],
                $this->commandData->config->modelName,
                $this->commandData->config->primaryKeyName
            )
        ) {
            $this->additionalParams = [];
        }
        unset($this->additionalParams['localKey']);
    }

    /**
     * For belongsTo, $foreignKey = column of THIS table that connects to the other
     * Format: ("relation function name" + "_" + "OTHER table primary key")
     * $ownerKey = other key (the primary key of the OTHER table)
     *
     * @param string $functionName relationship function name
     */
    private function validateBelongsToParams($functionName)
    {
        if (
            ForeignKey::isDefaultForeignKeyName(
                $this->additionalParams['foreignKey'],
                $functionName,
                $this->additionalParams['ownerKey']
            )
        ) {
            $this->additionalParams = [];
        }

        unset($this->additionalParams['ownerKey']);
    }

    /**
     * For belongsToMany, $foreignPivotKey = column in the PIVOT TABLE table that refers to THIS table
     * Format: ("this_model_name" + "_" + "this_primary_key")
     *         Str::snake(class_basename($this)).'_'.$this->getKeyName()
     *
     * $relatedPivotKey = column in the PIVOT TABLE table that refers to RELATED table
     * Format ("related_model_name" + "_" "related_primary_key")
     *        Str::snake(class_basename($relatedInstance)).'_'.$this->getKeyName()
     *
     * @param string $relatedModel related model name
     */
    private function validateBelongsToManyParams($relatedModel)
    {
        if (
            ForeignKey::isDefaultForeignKeyName(
                $this->additionalParams['foreignPivotKey'],
                $this->commandData->config->modelName,
                $this->commandData->config->primaryKeyName
            ) &&
            ForeignKey::isDefaultForeignKeyName(
                $this->additionalParams['relatedPivotKey'],
                $relatedModel,
                $this->additionalParams['primaryKey']
            )
        ) {
            $this->additionalParams = [];
        }

        unset($this->additionalParams['primaryKey']);
    }
}
