<?php

namespace Thomisticus\Generator\Generators\API;

use Thomisticus\Generator\Utils\CommandData;
use Thomisticus\Generator\Generators\BaseGenerator;
use Thomisticus\Generator\Utils\FileUtil;

class ControllerGenerator extends BaseGenerator
{
    /**
     * @var CommandData
     */
    private $commandData;

    /**
     * File path
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $fileName;

    /**
     * ControllerGenerator constructor.
     * @param \Thomisticus\Generator\Utils\CommandData $commandData
     */
    public function __construct(CommandData $commandData)
    {
        $this->commandData = $commandData;
        $this->path = $commandData->config->paths['api_controller'];
        $this->fileName = $this->commandData->modelName . 'APIController.php';
    }

    /**
     * Generates the API Controller
     */
    public function generate()
    {
        $templateName = $this->commandData->getOption('repositoryPattern') ? 'api_controller' : 'model_api_controller';
        $templateData = get_template("api.controller.$templateName", 'app-generator');

//        $paginate = $this->commandData->getOption('paginate');
//
//        if ($paginate) {
//            $templateData = str_replace('$RENDER_TYPE$', 'paginate(' . $paginate . ')', $templateData);
//        } else {
//            $templateData = str_replace('$RENDER_TYPE$', 'all()', $templateData);
//        }

        $templateData = fill_template($this->commandData->dynamicVars, $templateData);
        $templateData = $this->fillDocs($templateData);

        FileUtil::createFile($this->path, $this->fileName, $templateData);

        $this->commandData->commandObj->comment("\nAPI Controller created: ");
        $this->commandData->commandObj->info($this->fileName);
    }

    /**
     * Returns the file content after adding the PHPDoc blocks
     *
     * @param string $templateData
     * @return string
     */
    private function fillDocs($templateData)
    {
        $methods = ['controller', 'index', 'store', 'show', 'update', 'destroy'];

        $templatePrefix = 'api.docs.controller';
        $templateType = 'app-generator';

        foreach ($methods as $method) {
            $key = '$DOC_' . strtoupper($method) . '$';
            $docTemplate = get_template($templatePrefix . '.' . $method, $templateType);
            $docTemplate = fill_template($this->commandData->dynamicVars, $docTemplate);
            $templateData = str_replace($key, $docTemplate, $templateData);
        }

        return $templateData;
    }

    /**
     * Rollback file creation
     */
    public function rollback()
    {
        if ($this->rollbackFile($this->path, $this->fileName)) {
            $this->commandData->commandObj->comment('API Controller file deleted: ' . $this->fileName);
        }
    }
}