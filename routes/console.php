<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('emcarga:notificar-choferes')->dailyAt('00:30');