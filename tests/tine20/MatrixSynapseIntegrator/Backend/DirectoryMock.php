<?php

class MatrixSynapseIntegrator_Backend_DirectoryMock extends MatrixSynapseIntegrator_Backend_DirectoryPostgresql
{
    public array $directory = [];

    public function updateDirectory(array $directory)
    {
        $this->directory = $directory;
    }
}
