<?php

namespace PatternLab;

use PatternLab\Pimple\ServiceProvider;
use Pimple\ServiceProviderInterface;

class Container extends \Pimple\Container
{
    protected $providers = array();

    public function __construct(array $values)
    {
        parent::__construct($values);
        $this->register(new ServiceProvider());
    }

    public function register(ServiceProviderInterface $provider, array $values = array()) {
        $this->providers[] = $provider;
        return parent::register($provider, $values);
    }
}