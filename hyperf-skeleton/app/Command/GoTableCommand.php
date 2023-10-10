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
                return 'int64';
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
                return 'string';
            default:
                if (Str::contains($fileType, 'bigint')) {
                    return 'int64';
                } else if (Str::contains($fileType, 'float') || Str::contains($fileType, 'double') || Str::contains($fileType, 'decimal')) {
                    return 'float64';
                } else if (Str::contains($fileType, 'char') || Str::contains($fileType, 'varchar')) {
                    return 'string';
                } else if (Str::contains($fileType, 'text')) {
                    return 'string';
                } else if (Str::contains($fileType, 'date')) {
                    return 'string';
                } else if (Str::contains($fileType, 'time')) {
                    return 'string';
                } else if (Str::contains($fileType, 'enum')) {
                    return 'string';
                } else if (Str::contains($fileType, 'set')) {
                    return 'string';
                } else if (Str::contains($fileType, 'int')) {
                    return 'int';
                }
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
        return str_replace("\n", ' ', trim(str_replace(' ', '', $fieldComment)));
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
        /********************************************************************************************************
         * [数据库表结构] 实现表单 Config 增删改查等基本操作
        ********************************************************************************************************/
        $fileContent = '/********************************************************************************************************'."\n".
            " * [文档说明] 实现 {$structName} 增删改查等基本操作"."\n".
            " * [生成时间] ". date('Y-m-d H:i:s') ."+08:00\n".
            " * [最后修改] ". date('Y-m-d H:i:s') ."+08:00 (警告: 本代码为框架自动生成, 每次生成会自动覆盖, 请不要有任何修改操作)\n".
            '********************************************************************************************************/'."\n\n";
        $fileContent .= "package tables\n\n";
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
        $infoArr = [];


        foreach ($rows as $row) {
            $goField = $this->goField($row->field_name);
            $goType = $this->goType($row->field_type);
            $goColumn = $this->goColumn($row->field_name); 
            $goComment = $this->goComment($row->field_name, $row->field_comment);
            
            $fileContent .= "\t{$goField} {$goType} `gorm:\"{$goColumn}\" json:\"{$row->field_name}\"` \t// {$goComment} {$row->field_type}\n";

            $fields[] = '"'. $row->field_name. '"';
            if ($row->field_name == 'created') {
                $hasCreated = true;
            }
            if ($row->field_name == 'updated') {
                $hasUpdated = true;
            }

            $infoArr[] = (object) [
                'field_name' => $row->field_name,
                'field_type' => $row->field_type,
                'field_comment' => $row->field_comment,
                'go_field' => $goField,
                'go_type' => $goType,
                'go_column' => $goColumn,
                'go_comment' => $goComment,
            ];
        }

        $fileContent .= "\t TranslateFields []string `gorm:\"-\" json:\"-\"`\n";

        //$fileContent .= "\tgorm.Model `json:\"-\"`\n";
        $fileContent .= "}\n\n";

        $varName = Str::studly(Str::camel($tableName));
        $fileContent .= "// {$varName}Ins Instance\n";
        $fileContent .= "var {$varName}Ins = {$structName}{}\n\n";
        // TableName
        $fileContent .= "// TableName 获取表名\n";
        $fileContent .= "func (ths {$structName}) TableName() string {\n".
            "\t return \"{$tableName}\"\n".
            "}\n\n";
        // Fields
        $fileContent .= "// Fields 获取所有字段\n";
        $fileContent .= "func (ths {$structName}) Fields() []string {\n".
            "\t return []string{\n\t\t". implode(",\n\t\t", $fields) . ",\n\t}\n".
            "}\n\n";
        // FieldTypes
        $fileContent .= "// FieldTypes 获取所有字段类型\n";
        $fileContent .= "func (ths {$structName}) FieldTypes() map[string]string {\n".
            "\t return map[string]string{\n\t\t";
        foreach ($rows as $row) {
            $fileContent .= '"'. $row->field_name. '": "'. $row->field_type. "\",\n\t\t";
        }
        $fileContent .= "}\n".
            "}\n\n";
        // FieldTranslates
        $fileContent .= "// FieldTranslates 需要翻译的字段\n";
        $fileContent .= "func (ths {$structName}) FieldTranslates() []string {\n".
            "\t return ths.TranslateFields \n".
            "}\n\n";
        // Translate
        $fileContent .= "// Translate 翻译字段\n";
        $fileContent .= "func (ths {$structName}) Translate() *{$structName} {\n".
            "// 先将结构体转换为map[string] -> 再获取需要翻译的字段 -> 读取相关文件翻译字段 -> 返回翻译过的结构体 \n".
            "\t return &ths\n".
            "}\n\n";
        // HasCreated
        $fileContent .= "// HasCreated 是否包含创建时间\n";
        $fileContent .= "func (ths {$structName}) HasCreated() bool {\n".
            "\t return ". ($hasCreated ? 'true' : 'false') . "\n".
            "}\n\n";
        // HasUpdated
        $fileContent .= "// HasUpdated 是否包含更新时间\n";
        $fileContent .= "func (ths {$structName}) HasUpdated() bool {\n".
            "\t return ". ($hasUpdated ? 'true' : 'false') . "\n".
            "}\n\n";
        // GetAll
        $fileContent .= "// GetAll 获取所有记录 参数: 连接对象/条件:map[string]interface{}/限制: [10:limit, 2:page]/排序:'id desc'\n";
        $fileContent .= "func (ths {$structName}) GetAll(db *gorm.DB, args ...interface{}) (interface{}, int64, error) {\n".
            "\t var rows []{$structName}\n".
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
        // GetAllObjects
        $fileContent .= "// GetAllObjects 获取所有记录 参数: 连接对象/条件:map[string]interface{}/限制: [10:limit, 2:page]/排序:'id desc'\n";
        $fileContent .= "func (ths {$structName}) GetAllObjects(db *gorm.DB, args ...interface{}) ([]{$structName}, int64, error) {\n".
            "\t var rows []{$structName}\n".
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
        // GetAllByxxx
        foreach ($infoArr as $info) {
            $method = 'GetAllBy' . $info->go_field;
            $fileContent .= "// $method 依据条件获取所有记录 参数: 连接对象/条件: {$info->go_type} /限制: [10:limit, 2:page]/排序:'id desc'\n";
            $fileContent .= "func (ths {$structName}) $method(db *gorm.DB, val {$info->go_type}, args ...interface{}) ([]{$structName}, int64, error) {\n";
            $fileContent .= "\t var rows []{$structName}\n".
                "\t // var count int64 = 0\n".
                "// 判断是否有限制 如:[limit, page] \n".
                "\t if len(args) > 0 {\n".
                "\t\t limiter := args[0].([]int)\n".
                "\t\t if len(limiter) > 0 {\n".
                "\t\t\t db = db.Limit(limiter[0])\n".
                "\t\t\t if len(limiter) > 1 {\n".
                "\t\t\t\t db = db.Offset(limiter[0] * (limiter[1] - 1))\n".
                "\t\t\t }\n".
                "\t\t }\n".
                "\t }\n".
                "// 判断是否有排序 如: sort DESC\n".
                "\t if len(args) > 1 {\n".
                "\t\t db = db.Order(args[1].(string))\n".
                "\t } else {\n".
                "\t\t db = db.Order(\"id DESC\")\n".
                "\t }\n".
                "\t result := db.Where(\"{$info->field_name} = ?\", val).Find(&rows)\n".
                "\t if result.Error != nil {\n".
                "\t\t return nil, 0, result.Error\n".
                "\t }\n".
                "\t\t return rows, result.RowsAffected, nil\n".
                "}\n\n";
         }
        // Get
        $fileContent .= "// Get 获取单条记录 参数: 连接对象/条件:map[string]interface{}\n";
        $fileContent .= "func (ths {$structName}) Get(db *gorm.DB, args ...interface{}) (interface{}, error) {\n".
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
        // GetByxxx
        foreach ($infoArr as $info) {
            $method = 'GetBy' . $info->go_field;
            $fileContent .= "// $method 依据条件获取单条记录 参数: 连接对象/条件: {$info->go_type}\n";
            $fileContent .= "func (ths {$structName}) $method(db *gorm.DB, val {$info->go_type}, args ...interface{}) (*{$structName}, error) {\n";
            $fileContent .= "\t var row = {$structName}{}\n".
                "// 判断是否有排序 如: sort DESC\n".
                "\t if len(args) > 0 {\n".
                "\t\t db = db.Order(args[0].(string))\n".
                "\t } \n".
                "\t result := db.Where(\"{$info->field_name} = ?\", val).First(&row)\n".
                "\t if result.Error != nil {\n".
                "\t\t return nil, result.Error\n".
                "\t }\n".
                "\t\t return &row, nil\n".
                "}\n\n";
        }
        // Create
        $fileContent .= "// Create 创建记录 参数: 连接对象/数据:map[string]interface{}\n";
        $fileContent .= "func (ths {$structName}) Create(db *gorm.DB, data map[string]interface{}) (interface{}, error) {\n".
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
        $fileContent .= "func (ths {$structName}) Update(db *gorm.DB, data map[string]interface{}, cond map[string]interface{}) (interface{}, error) {\n".
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
        foreach ($infoArr as $info) {
            $method = 'UpdateBy' . $info->go_field;
            $fileContent .= "// $method 依据条件更新记录 参数: 连接对象/条件: {$info->go_type}/数据:map[string]interface{}\n";
            $fileContent .= "func (ths {$structName}) $method(db *gorm.DB, val {$info->go_type}, data map[string]interface{}) (*{$structName}, error) {\n".
                "\t var row = {$structName}{}\n".
                "if ths.HasUpdated() {\n".
                "\t data[\"updated\"] = utils.NowMicro()\n".
                "}\n".
                "\t result := db.Model(&{$structName}{}).Where(\"{$info->field_name} = ?\", val).Updates(data)\n".
                "\t if result.Error != nil {\n".
                "\t\t return nil, result.Error\n".
                "\t }\n".
                "\t\t return &row, nil\n".
                "}\n\n";
        }
        // Delete
        $fileContent .= "// Delete 删除记录 参数: 连接对象/条件:map[string]interface{}\n";
        $fileContent .= "func (ths {$structName}) Delete(db *gorm.DB, cond map[string]interface{}) (interface{}, error) {\n".
            "\t var row = {$structName}{}\n".
            "\t result := db.Model(&{$structName}{}).Where(cond).Delete(&row)\n".
            "\t if result.Error != nil {\n".
            "\t\t return nil, result.Error\n".
            "\t }\n".
            "\t\t return row, nil\n".
            "}\n\n";
        foreach ($infoArr as $info) {
            $method = 'DeleteBy' . $info->go_field;
            $fileContent .= "// $method 依据条件删除记录 参数: 连接对象/条件: {$info->go_type}\n";
            $fileContent .= "func (ths {$structName}) $method(db *gorm.DB, val {$info->go_type}) (*{$structName}, error) {\n".
                "\t var row = {$structName}{}\n".
                "\t result := db.Model(&{$structName}{}).Where(\"{$info->field_name} = ?\", val).Delete(&row)\n".
                "\t if result.Error != nil {\n".
                "\t\t return nil, result.Error\n".
                "\t }\n".
                "\t\t return &row, nil\n".
                "}\n\n";
        }
        // Count
        $fileContent .= "// Count 统计记录数 参数: 连接对象/条件:map[string]interface{}\n";
        $fileContent .= "func (ths {$structName}) CountAll(db *gorm.DB, cond map[string]interface{}) (int64, error) {\n".
            "\t var count int64 = 0\n".
            "\t result := db.Model(&{$structName}{}).Where(cond).Count(&count)\n".
            "\t if result.Error != nil {\n".
            "\t\t return 0, result.Error\n".
            "\t }\n".
            "\t\t return count, nil\n".
            "}\n\n";
        foreach ($infoArr as $info) {
            $method = 'CountBy' . $info->go_field;
            $fileContent .= "// $method 依据条件统计记录数 参数: 连接对象/条件: {$info->go_type}\n";
            $fileContent .= "func (ths {$structName}) $method(db *gorm.DB, val {$info->go_type}) (int64, error) {\n".
                "\t var count int64 = 0\n".
                "\t result := db.Model(&{$structName}{}).Where(\"{$info->field_name} = ?\", val).Count(&count)\n".
                "\t if result.Error != nil {\n".
                "\t\t return 0, result.Error\n".
                "\t }\n".
                "\t\t return count, nil\n".
                "}\n\n";
        }

        // 保存文件
        $savingPath = $this->config->get('go_table_path');
        if (!is_dir($savingPath)) { 
            mkdir($savingPath);
        }

        $fileName = $savingPath . '/' . $varName . '.go';
        echo 'Creating file name: ', $varName . "\n";
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

        // 生成 Base.go 文件
        $fileContent .= "package tables\n\n";
        $fileContent .= "// ITable 数据表接口\n";
        $fileContent .= "type ITable interface { \n".
            "\tTableName() string\n".
            "\tFields() []string\n".
            "\tFieldTypes() map[string]string\n".
            "\tFieldTranslates() []string\n".
            // "\tTranslate() *ITable\n".
            "\tHasCreated() bool\n".
            "\tHasUpdated() bool\n".
            "}\n";
        $fileContent .= "// TableCount 数据表数量\n".
            "const TableCount = ". count($rows) . "\n\n";
        $fileContent .= "// Tables 数据表列表\n";
        $fileContent .= "var Tables = map[string]ITable{}\n";

        $fileContent .= "// init 初始化\n";
        $fileContent .= "func init() {\n";
        $fileContent .= "Tables = map[string]ITable{\n";
        foreach ($rows as $r)  {
            $varName = Str::studly(Str::camel($r->table_name));
            $fileContent .= "\t\"{$r->table_name}\": {$varName}Ins,\n";
        }
        $fileContent .= "}\n";
        $fileContent .= "}\n";

        // 保存文件
        $savingPath = $this->config->get('go_table_path');
        if (!is_dir($savingPath)) { 
            mkdir($savingPath);
        }
        $fileName = $savingPath . '/Base.go';
        echo 'Creating file name: ', $fileName . "\n";
        file_put_contents($fileName, $fileContent);
        echo 'Formatting file name: ', $fileName . "\n";
        exec('go fmt ' . $fileName);

        // $this->line('Hello Hyperf!', 'info');
    }
}
