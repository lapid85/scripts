<?php

declare(strict_types=1);

namespace App\Command;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Psr\Container\ContainerInterface;
use Hyperf\DbConnection\Db;


const DATABASE = 'integrated_platforms_v5';

#[Command]
class GoTableCommand extends HyperfCommand
{

    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('gen:go-tables');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('生成 go 的 table 文件');
    }

    public function handle()
    {
        $rows = Db::table('all_tables')->get();
        foreach ($rows as $r) {
            print_r($r);
        }
        // $this->line('Hello Hyperf!', 'info');
    }
}
