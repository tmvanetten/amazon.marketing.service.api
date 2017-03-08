<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class BaseCommand extends Command {
	public $testMode = false;
    public function logs($message) {
        $this->comment(PHP_EOL.$message.PHP_EOL);
    }
}
