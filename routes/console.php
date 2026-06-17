<?php

declare(strict_types=1);

use App\Console\Commands\AggregateHealthDailyCommand;
use App\Console\Commands\ExpireStaleAgentApprovalsCommand;
use App\Console\Commands\ProcessGlucoseNotificationsCommand;
use App\Console\Commands\PurgeDeletedUserDataCommand;
use Illuminate\Support\Facades\Schedule;

Schedule::command('model:prune')->daily();

Schedule::command('backup:clean')->daily()->at('01:00');
Schedule::command('backup:run')->daily()->at('01:30');
Schedule::command('backup:monitor')->daily()->at('02:30');

Schedule::command(ExpireStaleAgentApprovalsCommand::class)->hourly();

Schedule::command(ProcessGlucoseNotificationsCommand::class)->dailyAt('08:00');

Schedule::command(PurgeDeletedUserDataCommand::class)->daily();

Schedule::command(AggregateHealthDailyCommand::class)->dailyAt('02:00');
