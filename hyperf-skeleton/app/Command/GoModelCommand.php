<?php

declare(strict_types=1);

namespace App\Command;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Psr\Container\ContainerInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\DbConnection\Db;
use Hyperf\Utils\Str;

#[Command]
class GoModelCommand extends HyperfCommand
{
    #[Inject]
    protected \Hyperf\Contract\ConfigInterface $config;

    // 构造函数
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('gen:go-models');
    }

    // 配置
    public function configure()
    {
        parent::configure();
        $this->setDescription('生成 go 的 model 文件');
    }

    // 创建 go  model
    private function createGoModel(string $tableName) {
        $fileContent = "package models\ntype " . Str::studly(Str::singular($tableName)) . " struct {}\n";
        // 保存文件
        $savingPath = $this->config->get('go_model_path');
        if (!is_dir($savingPath)) { 
            mkdir($savingPath);
        }

        $structName = Str::studly(Str::singular($tableName));
        $fileName = $savingPath . '/' . $structName . '.go';
        echo 'Creating file name: ', $fileName . "\n";
        file_put_contents($fileName, $fileContent);
        echo 'Formatting file name: ', $fileName . "\n";
        exec('go fmt ' . $fileName);
    }

    // 执行
    public function handle()
    {
        $rows = Db::table('all_tables')->selectRaw('DISTINCT(table_name) AS table_name')->get();
        foreach ($rows as $r) {
            $this->createGoModel($r->table_name);
        }
        // $this->line('Hello Hyperf!', 'info');
    }
}
