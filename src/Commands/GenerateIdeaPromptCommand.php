<?php

namespace app\command;

use Illuminate\Database\Query\Builder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @method static static whenLike($params, $prefix = null)
 * @method static static whenWhere($params, $prefix = null)
 * @method static static whenOrLike($params, $prefix = null)
 * @method static static whenOrderBy($fieldsMap = [])
 * @method static static whenDate($paramx,  $prefix = null)
 */
class GenerateIdeaPromptCommand extends Command
{
    protected static $defaultName = 'generate:tips';
    protected static $defaultDescription = '为Macro新增IDE提示,仅在测试环境生效';

    const methodDoc = '#IDE-START ' . PHP_EOL . '%s ' . PHP_EOL . ' #IDE-END';

    /**
     * @return string
     */
    private static function getMethodDoc(): string
    {
        $methodRef = new \ReflectionClass(self::class);
        return sprintf(self::methodDoc, $methodRef->getDocComment());
    }

    /**
     * @return void
     */
    public static function extracted(): void
    {
        $builderRef = new \ReflectionClass(Builder::class);
        $builderContent = file_get_contents($builderRef->getFileName());
        $docStr = self::getMethodDoc();

        if ($builderRef->getDocComment()) {
            $builderContent = preg_replace('/#IDE-START[\s\S]*?#IDE-END/', $docStr, $builderContent);
        } else {
            $builderContent = str_replace('class Builder', $docStr . PHP_EOL . 'class Builder', $builderContent);
        }

        file_put_contents($builderRef->getFileName(), $builderContent);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        config('app.debug') && self::extracted();
        return self::SUCCESS;
    }

    /**
     * composer install with script
     */
    public static function installTips()
    {
        self::extracted();
    }
}
