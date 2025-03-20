<?php

namespace Nasus\Webman\Commands;

use Nasus\Webman\Utils\Db;
use Nasus\Webman\Utils\Helper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class MakeRequestCommand extends Command
{
    protected static $defaultName = 'make:request';
    protected static $defaultDescription = 'make request';

    /**
     * 过滤字段
     * @var string[]
     */
    const FILTER_FIELD = ['id', 'created_at', 'updated_at', 'deleted_at'];

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
     * 校验规则
     * @var array
     */
    protected array $rules = [];

    /**
     * @var array
     */
    protected array $message = [];

    /**
     * @return void
     */
    protected function configure()
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'request name');
        $this->addArgument('tables', InputArgument::IS_ARRAY, 'tables name');
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

        if (stripos($this->name, 'request') === false) {
            $this->name .= 'Request';
        }

        $appentdColumns = [];
        foreach ($input->getArgument('tables') as $table) {
            $table = Helper::humpToUL($table);
            $tableExists = Db::select("SHOW TABLES LIKE '%" . $table . "'");
            if (empty($tableExists)) {
                $output->write(sprintf('<error>The %s table does not exist!</error>', $table));
                return self::INVALID;
            }

            $tableColumns = Db::select('SHOW FULL COLUMNS FROM ' . $table);
            foreach ($tableColumns as $column) {
                if (in_array($column->Field, self::FILTER_FIELD)) continue;

                if (!empty($appentdColumns[$column->Field])) continue;
                $appentdColumns[$column->Field] = 1;

                $ruleItem = sprintf("        '%s' => 'required',", $column->Field);
                $msgItem = sprintf("        '%s.required' => '请填写%s',", $column->Field, $column->Comment);

                $this->rules[] = $ruleItem;
                $this->message[] = $msgItem;
            }
        }

        $this->build($output);
        return self::SUCCESS;
    }

    /**
     * @param $output
     * @return int|void
     */
    private function build($output)
    {
        $requestPath = base_path(sprintf('plugin/%s/app/request', $this->module));
        if (!is_dir($requestPath)) {
            $output->write(sprintf('<error>%s, The request path does not exist!</error>', $requestPath));
            return;
        }

        $namespace = $this->appNamespace . '\request';
        $stub = file_get_contents(sprintf('%s/stubs/request.stub', dirname(__FILE__)));

        $stub = str_replace('{namespace}', $namespace, $stub);
        $stub = str_replace('{rules}', implode(PHP_EOL, $this->rules), $stub);
        $stub = str_replace('{message}', implode(PHP_EOL, $this->message), $stub);
        $stub = str_replace('{requestName}', $this->name, $stub);

        $modelFilePath = $requestPath . '/' . $this->name . '.php';
        file_put_contents($modelFilePath, $stub);

        $output->write('<info>Successfully created the ' . $this->name . '</info>');
        return self::SUCCESS;
    }
}
