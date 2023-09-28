<?php

declare(strict_types=1);

namespace App\Command;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Di\Annotation\Inject;
use Psr\Container\ContainerInterface;
use Hyperf\DbConnection\Db;
use Hyperf\Utils\Str;

// 执行命令: php bin/hyperf.php gen:go-tables
#[Command]
class GoTableCommand extends HyperfCommand
{

    #[Inject]
    protected \Hyperf\Contract\ConfigInterface $config;

    // 构造函数
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('gen:go-tables');
    }

    // 配置
    public function configure()
    {
        parent::configure();
        $this->setDescription('生成 go 的 table 文件');
    }

    // 转换为 go 的类型
    private function goType(string $fileType): string {
        switch ($fileType) {
            case 'int':
            case 'tinyint':
            case 'smallint':
            case 'bigint':
                return 'int';
            case 'float':
            case 'double':
            case 'decimal':
                return 'float64';
            case 'varchar':
            case 'char':
            case 'text':
            case 'enum':
            case 'set':
                return 'string';
            case 'date':
            case 'datetime':
            case 'timestamp':
                return 'time.Time';
            default:
                return 'string';
        }
    }

    // 转换为 go 的字段名
    private function goField(string $fieldName): string {
        if ($fieldName == 'id') {
            return 'ID';
        }
        if (Str::contains($fieldName, '_id')) {
            return ucfirst(Str::camel(Str::before($fieldName, '_id'))) . 'ID';
        }
        return ucfirst(Str::camel($fieldName));
    }

    // 转换为 go 的注释
    private function goComment(string $fieldName, string $fieldComment): string {
        if ($fieldName == 'id') {
            return 'ID';
        }
        return $fieldComment;
    }

    // 转换为 go 的 column
    private function goColumn(string $fieldName): string {
        if ($fieldName == 'id') {
            return 'column:id;primaryKey;autoIncrement';
        }
        return "column:{$fieldName}";
    }

    // 创建 go 的 table 文件
    private function createGoTable(string $tableName)  { 
        $rows = Db::table('all_tables')->selectRaw('field_name, field_type, field_comment')->where('table_name', '=', $tableName)->get();
        $structName = Str::studly(Str::singular($tableName));
        $fileContent = "package tables\n\n";
        $fileContent .= "import (\n".
            "\t\"gorm.io/gorm\"\n".
            "\t\"gorm.io/driver/mysql\"\n". 
            ")\n\n";
        $fileContent .= "// {$structName} 数据表名: {$tableName} \n";
        $fileContent .= "type {$structName} struct {\n";

        foreach ($rows as $row) {
            $fileContent .= "\t{$this->goField($row->field_name)} ".
            "{$this->goType($row->field_type)} ".
            "`gorm:\"{$this->goColumn($row->field_name)}\"`". 
            "\t// {$this->goComment($row->field_name, $row->field_comment)}\n";
        }

        $fileContent .= "\tgorm.Model\n";
        $fileContent .= "}\n\n";

        // 保存文件
        $savingPath = $this->config->get('go_table_path');
        if (!is_dir($savingPath)) { 
            mkdir($savingPath);
        }

        $fileName = $savingPath . '/' . $structName . '.go';
        echo 'Creating file name: ', $fileName . "\n";
        file_put_contents($fileName, $fileContent);
        echo 'Formatting file name: ', $fileName . "\n";
        exec('go fmt ' . $fileName);
    }

    // 处理
    public function handle()
    {
        $rows = Db::table('all_tables')->selectRaw('DISTINCT(table_name) AS table_name')->get();
        foreach ($rows as $r) {
            $this->createGoTable($r->table_name);
        }
        // $this->line('Hello Hyperf!', 'info');
    }
}
