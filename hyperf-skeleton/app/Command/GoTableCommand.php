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
            "\t\"common/utils\"\n".
            ")\n\n";
        //"\t\"gorm.io/driver/mysql\"\n". 
        $fileContent .= "// {$structName} 数据表名: {$tableName} \n";
        $fileContent .= "type {$structName} struct {\n";

        $fields = [];
        $hasCreated = false;
        $hasUpdated = false;

        foreach ($rows as $row) {
            $fileContent .= "\t{$this->goField($row->field_name)} ".
            "{$this->goType($row->field_type)} ".
            "`gorm:\"{$this->goColumn($row->field_name)}\" json:\"{$row->field_name}\"`". 
            "\t// {$this->goComment($row->field_name, $row->field_comment)}\n";

            $fields[] = '"'. $row->field_name. '"';
            if ($row->field_name == 'created') {
                $hasCreated = true;
            }
            if ($row->field_name == 'updated') {
                $hasUpdated = true;
            }
        }

        //$fileContent .= "\tgorm.Model `json:\"-\"`\n";
        $fileContent .= "}\n\n";

        $varName = Str::studly(Str::camel($tableName));
        $fileContent .= "// {$varName} Instance\n";
        $fileContent .= "var {$varName} = {$structName}{}\n\n";
        // TableName
        $fileContent .= "// TableName 获取表名\n";
        $fileContent .= "func (ths *{$structName}) TableName() string {\n".
            "\t return \"{$tableName}\"\n".
            "}\n\n";
        // Fields
        $fileContent .= "// Fields 获取所有字段\n";
        $fileContent .= "func (ths *{$structName}) Fields() []string {\n".
            "\t return []string{\n\t\t". implode(",\n\t\t", $fields) . ",\n\t}\n".
            "}\n\n";
        // HasCreated
        $fileContent .= "// HasCreated 是否包含创建时间\n";
        $fileContent .= "func (ths *{$structName}) HasCreated() bool {\n".
            "\t return ". ($hasCreated ? 'true' : 'false') . "\n".
            "}\n\n";
        // HasUpdated
        $fileContent .= "// HasUpdated 是否包含更新时间\n";
        $fileContent .= "func (ths *{$structName}) HasUpdated() bool {\n".
            "\t return ". ($hasUpdated ? 'true' : 'false') . "\n".
            "}\n\n";
        // GetAll
        $fileContent .= "// GetAll 获取所有记录 参数: 连接对象/条件:map[string]interface{}/限制: [10:limit, 2:page]/排序:'id desc'\n";
        $fileContent .= "func (ths *{$structName}) GetAll(db *gorm.DB, args ...interface{}) (interface{}, int64, error) {\n".
            "\t var rows = []{$structName}{}\n".
            "\t // var count int64 = 0\n".
            "// 判断是否有查询条件 如:map[string]interface{}{\"age\":19}\n".
            "\t if len(args) > 0 {\n".
            "\t\t db = db.Where(args[0])\n".
            "\t }\n".
            "// 判断是否有限制 如:[limit, page] \n".
            "\t if len(args) > 1 {\n".
            "\t\t limiter := args[1].([]int)\n".
            "\t\t if len(limiter) > 0 {\n".
            "\t\t\t db = db.Limit(limiter[0])\n".
            "\t\t\t if len(limiter) > 1 {\n".
            "\t\t\t\t db = db.Offset(limiter[0] * (limiter[1] - 1))\n".
            "\t\t\t }\n".
            "\t\t }\n".
            "\t }\n".
            "// 判断是否有排序 如: sort DESC\n".
            "\t if len(args) > 2 {\n".
            "\t\t db = db.Order(args[2].(string))\n".
            "\t } else {\n".
            "\t\t db = db.Order(\"id DESC\")\n".
            "\t }\n".
            "\t result := db.Find(&rows)\n".
            "\t if result.Error != nil {\n".
            "\t\t return nil, 0, result.Error\n".
            "\t }\n".
            "\t\t return rows, result.RowsAffected, nil\n".
            "}\n\n";
        // Get
        $fileContent .= "// Get 获取单条记录 参数: 连接对象/条件:map[string]interface{}\n";
        $fileContent .= "func (ths *{$structName}) Get(db *gorm.DB, args ...interface{}) (interface{}, error) {\n".
            "\t var row = {$structName}{}\n".
            "\t // var count int64 = 0\n".
            "// 判断是否有查询条件 如:map[string]interface{}{\"age\":19}\n".
            "\t if len(args) > 0 {\n".
            "\t\t db = db.Where(args[0])\n".
            "\t }\n".
            "// 判断是否有排序 如: sort DESC\n".
            "\t if len(args) > 1 {\n".
            "\t\t db = db.Order(args[1].(string))\n".
            "\t } \n".
            "\t result := db.First(&row)\n".
            "\t if result.Error != nil {\n".
            "\t\t return nil, result.Error\n".
            "\t }\n".
            "\t\t return row, nil\n".
            "}\n\n";
        // Create
        $fileContent .= "// Create 创建记录 参数: 连接对象/数据:map[string]interface{}\n";
        $fileContent .= "func (ths *{$structName}) Create(db *gorm.DB, data map[string]interface{}) (interface{}, error) {\n".
            "\t var row = {$structName}{}\n".
            "if ths.HasCreated() {\n".
            "\t data[\"created\"] = utils.NowMicro()\n".
            "}\n".
            "if ths.HasUpdated() {\n".
            "\t data[\"updated\"] = utils.NowMicro()\n".
            "}\n".
            "\t result := db.Model(&{$structName}{}).Create(data)\n".
            "\t if result.Error != nil {\n".
            "\t\t return nil, result.Error\n".
            "\t }\n".
            "\t\t return row, nil\n".
            "}\n\n";
        // Update
        $fileContent .= "// Update 更新记录 参数: 连接对象/条件:map[string]interface{}/数据:map[string]interface{}\n";
        $fileContent .= "func (ths *{$structName}) Update(db *gorm.DB, data map[string]interface{}, cond map[string]interface{}) (interface{}, error) {\n".
            "\t var row = {$structName}{}\n".
            "if ths.HasUpdated() {\n".
            "\t data[\"updated\"] = utils.NowMicro()\n".
            "}\n".
            "\t result := db.Model(&{$structName}{}).Where(cond).Updates(data)\n".
            "\t if result.Error != nil {\n".
            "\t\t return nil, result.Error\n".
            "\t }\n".
            "\t\t return row, nil\n".
            "}\n\n";
        // Delete
        $fileContent .= "// Delete 删除记录 参数: 连接对象/条件:map[string]interface{}\n";
        $fileContent .= "func (ths *{$structName}) Delete(db *gorm.DB, cond map[string]interface{}) (interface{}, error) {\n".
            "\t var row = {$structName}{}\n".
            "\t result := db.Model(&{$structName}{}).Where(cond).Delete(&row)\n".
            "\t if result.Error != nil {\n".
            "\t\t return nil, result.Error\n".
            "\t }\n".
            "\t\t return row, nil\n".
            "}\n\n";

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
