<?php

declare(strict_types=1);

namespace App\Command;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Psr\Container\ContainerInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\DbConnection\Db;
use Hyperf\Utils\Str;

// 执行命令: php bin/hyperf.php gen:go-models
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
        $tableStructName = Str::singular($tableName);
        $structName =  Str::studly(Str::singular($tableName));
        $varName = Str::studly(Str::camel($tableName));

        $fileContent = "package models\n ".
            "import tables \"integrated-tables\" \n\n ".
            "// ${structName} 表: $tableName -- 严禁直接使用 tables.xxx 方法, 请一律通过 models 来调用 \n".
            "type $structName struct {\n ".
            "*tables.{$structName} \n ".
            "} \n\n".
            "// $varName 对应 {$structName}{}/tables.{$structName}{} - 单例模式/当作静态变量使用 \n".
            "var {$varName} = &{$structName}{} \n\n";
        // 保存文件
        $savingPath = $this->config->get('go_model_path');
        if (!is_dir($savingPath)) { 
            mkdir($savingPath);
        }

        // $structName = Str::studly(Str::singular($tableName));
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
