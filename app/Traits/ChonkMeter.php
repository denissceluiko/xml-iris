<?php

namespace App\Traits;

use Illuminate\Support\Facades\Log;

trait ChonkMeter
{
    /**
     * Gets peak memory usage of a process in KiB from /proc.../status.
     *
     * @return int|bool VmPeak, value in KiB. False if data could not be found.
     */
    protected function getPeakMemory()
    {
        $status = file_get_contents('/proc/' . getmypid() . '/status');
        $matches = array();
        preg_match_all('/^(VmPeak):\s*([0-9]+).*$/im', $status, $matches);
        return !isset($matches[2][0]) ? false : intval($matches[2][0]);
    }

    protected function getMemory()
    {
        return round(memory_get_usage() / 1024);
    }

    protected function logChonk($channel = 'import')
    {
        if (config('app.env') == 'local') {
            Log::channel($channel)->info("Memory usage: {$this->getMemory()} / {$this->getPeakMemory()} KiB");
        }
    }

}
