<?php

namespace App\Print;

use Illuminate\Support\Facades\Log;

class ZplPrinter
{

    /**
     * socket
     *
     * @var mixed
     */
    protected $socket;

    /**
     * constructor
     *
     * @param  mixed $host
     * @param  mixed $port
     * @return void
     */
    public function __construct(string $host, int $port = 9100)
    {
        $this->connect($host, $port);
    }

    /**
     * destructor
     *
     * @return void
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * create an instance to manipulate printer
     *
     * @param  mixed $host
     * @param  mixed $port
     * @return self
     */
    public static function printer(string $host, int $port = 9100): self
    {
        return new static($host, $port);
    }

    /**
     * connect to the printer
     *
     * @param  mixed $host
     * @param  mixed $port
     * @return void
     */
    protected function connect(string $host, int $port): void
    {
        $this->socket = @fsockopen($host, $port, $errno, $errstr, 5);
        if (!$this->socket) {
            Log::error("Printer connection failed: {$errstr} (code: {$errno})");
        }
    }

    /**
     * disconnect to the printer
     *
     * @return void
     */
    protected function disconnect(): void
    {
        if ($this->socket) {
            @fclose($this->socket);
        }
    }

    /**
     * send ZPL data to printer.
     *
     * @param  mixed $zpl
     * @return void
     */
    public function send(string $zpl): void
    {
        if ($this->socket && !@fwrite($this->socket, $zpl)) {
            Log::error('Failed to write to printer socket');
        }
    }

}
