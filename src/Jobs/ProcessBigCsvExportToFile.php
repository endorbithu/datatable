<?php

namespace Endorbit\Datatable\Jobs;

use Endorbit\Datatable\Models\DatatableUser;
use Endorbit\Datatable\Services\Datatable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessBigCsvExportToFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $class = null;
    protected $method = null;
    protected $requestData = [];
    public $timeout = 3600;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($class, $method, $requestData)
    {
        $this->class = $class;
        $this->method = $method;
        $this->requestData = $requestData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $class = $this->class;
        $controller = new $class();

        $method = $this->method;

        $this->requestData['from_queue'] = true;
        $request = new Request($this->requestData);

        $controller->$method($request);
    }
}
