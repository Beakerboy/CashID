<?php

namespace CashID\Services;

use CashID\Exceptions\CashIDException;

abstract class CashIDService
{
    protected $service_domain;
    protected $service_path;
    protected $cache;
    protected $defaultDependencies;

    public function __construct(string $domain, string $path, ...$dependencies)
    {
        $this->service_domain = $domain;
        $this->service_path = $path;
        $this->setDependencies($dependencies);
    }

    /**
     * Set the class dependencies
     *
     * @param array $dependencies
     *   an array of objects
     */
    private function setDependencies(array $dependencies)
    {
        // Assign the provided dependency to the property
        foreach ($dependencies as $object) {
            $interface_names = class_implements($object);
            $used = false;
            // Check the list of interfaces to ensure it implements one we need
            foreach ($interface_names as $interface_name) {
                // If it does, save it to the appropriate class parameter
                if (isset($this->defaultDependencies[$interface_name])) {
                    $name = $this->defaultDependencies[$interface_name]['name'];
                    $this->$name = $object;
                    unset($this->defaultDependencies[$interface_name]);
                    $used = true;
                }
            }
            // If it does not, throw an exception
            if (!$used) {
                $class_name = get_class($object);
                throw new CashIDException("Class '{$class_name}' cannot be used as a dependency.");
            }
        }
        foreach ($this->defaultDependencies as $dependency) {
            $name = $dependency['name'];
            $class = $dependency['class'];
            $this->$name = new $class;
        }
    }
}
