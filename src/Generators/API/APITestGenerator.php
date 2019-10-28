<?php

namespace Thomisticus\Generator\Generators\API;

use Thomisticus\Generator\Common\CommandData;
use Thomisticus\Generator\Generators\BaseGenerator;
use Thomisticus\Generator\Utils\FileUtil;

class APITestGenerator extends BaseGenerator
{
    /** @var CommandData */
    private $commandData;

    /** @var string */
    private $path;

    /** @var string */
    private $fileName;

    public function __construct(CommandData $commandData)
    {
        $this->commandData = $commandData;
        $this->path = $commandData->config->paths['api_tests'];
        $this->fileName = $this->commandData->modelName . 'ApiTest.php';
    }

    public function generate()
    {
        $templateData = get_template('api.test.api_test', 'app-generator');

        $templateData = fill_template($this->commandData->dynamicVars, $templateData);

        FileUtil::createFile($this->path, $this->fileName, $templateData);

        $this->commandData->commandObj->comment("\nApiTest created: ");
        $this->commandData->commandObj->info($this->fileName);
    }

    public function rollback()
    {
        if ($this->rollbackFile($this->path, $this->fileName)) {
            $this->commandData->commandComment('API Test file deleted: ' . $this->fileName);
        }
    }
}
