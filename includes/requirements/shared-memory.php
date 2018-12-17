<?php

class SharedMemory
{
    public $shmId;
    public $shmSize;

    public $key;
    public $size;
    public $flags;
    public $mode;
    public $deleteAtEnd;

    // Opens a shared memory
    public function __construct(int $key, int $size, string $flags = "c", int $mode = 0644,
        bool $deleteAtEnd = true)
    {
        $id = shmop_open($key, $flags, $mode, $size);

        if (!$id)
            throw new Exception("Cannot create shared memory");

        // Created successfully
        $this->shmId = $id;
        $this->shmSize = shmop_size($id);

        // Save the current arguments for clear() method
        $this->key = $key;
        $this->size = $size;
        $this->flags = $flags;
        $this->mode = $mode;
        $this->deleteAtEnd = $deleteAtEnd;
    }
    
    // Read from the shared memory
    public function read(int $readSize = 0, int $start = 0): string
    {
        if ($readSize === 0 && $start !== 0)
            throw new Exception("Reading size is greater than shared memory size");

        // Read from it, and if count is 0, read the whole shared memory
        $data = @shmop_read($this->shmId, $start, $readSize === 0 ? $this->shmSize : $readSize);

        if ($data === false)
            throw new Exception("Cannot read from shared memory");
            
        return $data;
    }

    // Write to the shared memory
    public function write(string $data, int $offset = 0): bool
    {
        // Write data
        $bytesWritten = @shmop_write($this->shmId, $data, $offset);

        if ($bytesWritten < 0)
            throw new Exception("Cannot write to shared memory");

        // If the written data was not written completely, return false
        if ($bytesWritten !== strlen($data))
            return false;
        return true;
    }

    // Deletes a shared memory and create it again
    public function clear()
    {
        $this->__destruct();
        $this->__construct($this->key, $this->size, $this->flags, $this->mode,
            $this->deleteAtEnd);
    }

    // Delete shared memory at destruction (if user wanted) and close shared memory
    public function __destruct()
    {
        if ($this->deleteAtEnd)
            shmop_delete($this->shmId);
        shmop_close($this->shmId);
    }
}