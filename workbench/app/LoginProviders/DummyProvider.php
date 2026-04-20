<?php

namespace Workbench\App\LoginProviders;

use SchenkeIo\LaravelAuthRouter\LoginProviders\UnknownBaseProvider;

class DummyProvider extends UnknownBaseProvider
{
    public function __construct()
    {
        parent::__construct();
        $this->addError('Error 1');
        $this->addError('Error 2');
        $this->addError('Error 3');
        $this->addError('Error 4');
    }
}
