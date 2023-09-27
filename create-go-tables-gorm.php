<?php

const DB_HOST = 'im_platform';
const DB_PORT = '3306';
const DB_DATABASE = 'im_platform';
const DB_USERNAME = 'admin';
const DB_PASSWORD = 'qwe123QWE';

// 创建 go 文件
function createGoFile($row) { 

}

// 列出所有表
function createTables() { 
    // 连接到数据库
    $pdo = new PDO('mysql:host='. DB_HOST. ';dbname='. DB_DATABASE, DB_USERNAME, DB_PASSWORD);
    // 设置 PDO 错误模式为异常
    // $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 准备 SQL 查询
    $sql = "SELECT DISTINCT(table_name) FROM all_tables";

    // 执行查询
    $stmt = $pdo->query($sql);

    // 检查是否有结果
    if ($stmt->rowCount() > 0) {
        // 循环遍历结果集
        while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
            // 输出表名称
            echo "Table Name: " . $row->table_name . "<br>";
        }
    } else {
        echo "没有找到记录";
    }

    // 关闭数据库连接
    $pdo = null;
}