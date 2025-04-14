<?php

namespace Nasus\Webman\Commands;

use Nasus\Webman\Request\DeleteIdRequest;
use Nasus\Webman\Utils\Db;
use Nasus\Webman\Utils\Helper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeCtrlCommand extends Command
{
    protected static $defaultName = 'make:ctrl';
    protected static $defaultDescription = 'make ctrl';

    const METHOD_NAME = [
        'list' => '查看',
        'create' => '添加',
        'update' => '修改',
        'delete' => '删除',
        'detial' => '详情'
    ];

    /**
     * @var string
     */
    protected string $name;

    /**
     * @var string
     */
    protected string $module;

    /**
     * @var string
     */
    protected string $appNamespace;

    /**
     * @return void
     */
    protected function configure()
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'controller name');
        $this->addArgument('methods', InputArgument::IS_ARRAY, 'controller methods');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->module = strtolower(dirname($input->getArgument('name')));
        $this->name = ucfirst(basename($input->getArgument('name')));
        $this->appNamespace = sprintf('plugin\%s\app', lcfirst($this->module));

        if (stripos($this->name, 'controller') === false) {
            $this->name .= 'Controller';
        }

        $methods = $input->getArgument('methods');
        $onlyName = ucfirst(str_replace('Controller', '', $this->name));
        $methodArr = [];
        $methodStub = file_get_contents(sprintf('%s/stubs/method.stub', dirname(__FILE__)));
        foreach ($methods as $method) {
            $requestClass = sprintf('%s%sRequest', $onlyName, ucfirst($method));
            if ($method == 'list') {
                $requestClass = 'PagingRequest';
            }

            if ($method == 'delete') {
                $requestClass = 'DeleteIdRequest';
            }

            if ($method == 'detail') {
                $requestClass = 'DetailIdRequest';
            }

            $func = sprintf($methodStub, self::METHOD_NAME[$method] ?? '', lcfirst($method), $requestClass);
            $methodArr[] = $func;
        }

        $this->build($output, implode(PHP_EOL, $methodArr));
        return self::SUCCESS;
    }

    /**
     * @param $output
     * @return int|void
     */
    private function build($output, $methodStr)
    {
        $controllerPath = base_path(sprintf('plugin/%s/app/controller', $this->module));
        if (!is_dir($controllerPath)) {
            $output->write(sprintf('<error>%s, The controller path does not exist!</error>', $controllerPath));
            return;
        }

        $namespace = $this->appNamespace . '\controller';
        $stub = file_get_contents(sprintf('%s/stubs/controller.stub', dirname(__FILE__)));

        $stub = str_replace('{namespace}', $namespace, $stub);
        $stub = str_replace('{router}', Helper::humpToCL(str_replace('Controller', '', $this->name)), $stub);
        $stub = str_replace('{methods}', $methodStr, $stub);
        $stub = str_replace('{controllerName}', $this->name, $stub);

        $modelFilePath = $controllerPath . '/' . $this->name . '.php';
        file_put_contents($modelFilePath, $stub);

        $output->write('<info>Successfully created the ' . $this->name . '</info>');
        return self::SUCCESS;
    }
}
