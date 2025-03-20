<?php

namespace Nasus\Webman\Commands;

use Nasus\Webman\Utils\Db;
use Nasus\Webman\Utils\Helper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class MakeDaoCommand extends Command
{
    protected static $defaultName = 'make:dao';
    protected static $defaultDescription = 'make dao';

    /**
     * 模型表名
     * @var string
     */
    protected string $tableName;

    /**
     * 表字段
     * @var array
     */
    protected array $tableColumns;

    /**
     * 模块名称
     * @var string
     */
    protected string $module;

    /**
     * @return void
     */
    protected function configure()
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'model name');
        $this->addArgument('table', InputArgument::OPTIONAL, 'table name');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->tableName = $input->getArgument('table') ?? Helper::humpToUL(basename($input->getArgument('name')));

        $tableExists = Db::select("SHOW TABLES LIKE '%" . $this->tableName . "'");
        if (empty($tableExists)) {
            $output->write(sprintf('<error>The %s table does not exist!</error>', $this->tableName));
            return self::INVALID;
        }

        $this->tableColumns = Db::select('SHOW FULL COLUMNS FROM ' . $this->tableName);
        $this->module = strtolower(dirname($input->getArgument('name')));

        $this->buildModel($input, $output);
        $this->buildModelDo($input, $output);
        return self::SUCCESS;
    }

    /**
     * 构建模型
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    private function buildModel(InputInterface $input, OutputInterface $output): void
    {
        $modelPath = base_path(sprintf('plugin/%s/app/model', $this->module));
        if (!is_dir($modelPath)) {
            $output->write(sprintf('<error>%s, The model path does not exist!</error>', $modelPath));
            return;
        }

        $fieldDoc = '';
        foreach ($this->tableColumns as $column) {
            $fieldDoc .= sprintf(" * @property %s $%s %s\r", Helper::dbTypeConversion($column->Type), $column->Field, $column->Comment);
        }

        $model = ucfirst(basename($input->getArgument('name')));
        if (file_exists($modelPath . '/' . $model)) {
            $output->write(sprintf('<error>%s, The model exist!</error>', $modelPath));
            return;
        }

        $namespace = sprintf('plugin\%s\app\model', $this->module);
        $stub = file_get_contents(sprintf('%s/stubs/model.stub', dirname(__FILE__)));

        $stub = str_replace('{namespace}', $namespace, $stub);
        $stub = str_replace('{fieldDoc}', $fieldDoc, $stub);
        $stub = str_replace('{modelName}', $model, $stub);
        $stub = str_replace('{tableName}', $this->tableName, $stub);

        $modelFilePath = $modelPath . '/' . $model . '.php';
        file_put_contents($modelFilePath, $stub);

        $output->write('<info>Successfully created the ' . $model . ' model</info>');
    }

    private function buildModelDo(InputInterface $input, OutputInterface $output): void
    {
        $modelDoPath = base_path(sprintf('plugin/%s/app/model/do', $this->module));
        if (!is_dir($modelDoPath)) {
            if (!mkdir($modelDoPath, 0755)) {
                $output->write(sprintf('<error>%s, Failed to create the model do directory !</error>', $modelDoPath));
                return;
            }
        }

        $fieldConst = '';
        $fieldArr = [];
        $fieldCommentArr = [];
        foreach ($this->tableColumns as $column) {
            $fieldConst .= sprintf("    /**\r     * %s\r     * @var %s\r     */\r", $column->Comment, Helper::dbTypeConversion($column->Type));
            $fieldConst .= sprintf("    const %s = '%s';\r\r", Helper::SnakeToCamel($column->Field), $column->Field);
            $fieldArr[] = sprintf("'%s'", $column->Field);

            $comment = explode(':', $column->Comment);
            $fieldCommentArr[] = sprintf("'%s' => '%s'", $column->Field, empty($comment[0]) ? '' : $comment[0]);
        }

        // 添加字段数组
        $fieldConst .= "    /**\r     * 字段数组\r     * @var array\r     */\r";
        $fieldConst .= sprintf("    const FieldsArray = [%s];\r\r", implode(', ', $fieldArr));

        // 添加字段注释数组
        $fieldConst .= "    /**\r     * 字段描述数组\r     * @var array\r     */\r";
        $fieldConst .= sprintf("    const FieldsCommentsArray = [\r        %s\r    ];\r", implode(",\r        ", $fieldCommentArr));

        $modelDo = sprintf('%sDo', ucfirst(basename($input->getArgument('name'))));
        if (file_exists($modelDoPath . '/' . $modelDo)) {
            $output->write(sprintf('<error>%s, The model do exist!</error>', $modelDo));
            return;
        }

        $namespace = sprintf('plugin\%s\app\model\do', $this->module);
        $stub = file_get_contents(sprintf('%s/stubs/modelDo.stub', dirname(__FILE__)));

        $stub = str_replace('{namespace}', $namespace, $stub);
        $stub = str_replace('{fieldConst}', $fieldConst, $stub);
        $stub = str_replace('{modelName}', $modelDo, $stub);
        $stub = str_replace('{tableName}', $this->tableName, $stub);

        $modelFilePath = $modelDoPath . '/' . $modelDo . '.php';
        file_put_contents($modelFilePath, $stub);

        $output->write('<info>Successfully created the ' . $modelDo . ' model</info>');
    }

}
