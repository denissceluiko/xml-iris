<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ConfigureMysql extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'iris-xml:binlogs
                            {action : show, set or purge}
                            {--expire_binlogs_seconds= : Time in seconds to expire logs after}
                            {--expire_logs_days= : Time in days to expire logs after}
                            {--purge_logs_seconds= : Logs older than seconds will be deleted}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sets up MySQL server binlog configs.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (!in_array($this->argument('action'), ['show', 'set', 'purge'])) {
            $this->alert('action must me \'show\', \'set\' or \'purge\'');
            return Command::FAILURE;
        }

        switch ($this->argument('action')) {
            case 'show':
                return $this->show();
                break;
            case 'set':
                return $this->set();
                break;
            case 'purge':
                return $this->purge();
                break;
        }

        return Command::SUCCESS;
    }

    public function show()
    {
        $results = DB::connection('mysql-root')->select("show variables like '%expire_logs%'");
        $results = json_decode(json_encode($results), true);
        $this->table(['Variable_name', 'Value'], $results);

        $results = DB::connection('mysql-root')->select("SHOW BINARY LOGS");
        $results = json_decode(json_encode($results), true);
        $this->table(['Log_name', 'File_size', 'Encrypted'], $results);
    }

    public function set()
    {
        if ($this->hasOption('expire_binlogs_seconds')) {
            DB::connection('mysql-root')->statement('SET GLOBAL binlog_expire_logs_seconds = ?;', [intval($this->option('expire_binlogs_seconds'))]);
            DB::connection('mysql-root')->statement('SET PERSIST binlog_expire_logs_seconds = ?;', [intval($this->option('expire_binlogs_seconds'))]);
        }

        if ($this->hasOption('expire_logs_days')) {
            DB::connection('mysql-root')->statement('SET GLOBAL expire_logs_days = ?;', [intval($this->option('expire_logs_days'))]);
            DB::connection('mysql-root')->statement('SET PERSIST expire_logs_days = ?;', [intval($this->option('expire_logs_days'))]);
        }
    }

    public function purge()
    {
        $interval = $this->hasOption('purge_logs_seconds') ? $this->option('purge_logs_seconds') : 0;
        DB::connection('mysql-root')->statement("PURGE BINARY LOGS BEFORE now() - interval ? second;", [$interval]);
    }
}
