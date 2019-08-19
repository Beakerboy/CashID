<?php

namespace CashID\Services;

use CashID\Exceptions\CashIDException;
use Psr\SimpleCache\CacheInterface;

abstract class CashIDService
{
    protected $service_domain;
    protected $service_path;
    protected $cache;
    protected $defaultDependencies;

    /**
     * Class Constructor
     *
     * @param string $domain
     *   The domain of the response handler
     * @param string $path
     *   The path to the response handler
     * @param mixed ...$dependencies
     *   0 or more objects defined in the children classes
     */
    public function __construct(string $domain, string $path, CacheInterface $cache, ...$dependencies)
    {
        $this->cache          = $cache;
        $this->service_domain = $domain;
        $this->service_path   = $path;
        $this->setDependencies($dependencies);
    }

    /**
     * Set the class dependencies
     *
     * This function allows the user to specify object dependencies in any order
     * when constructing the object. It also allows users to create custom children
     * with any number of dependencies, which can be injected upon creation.
     *
     * @param array $dependencies
     *   an array of objects
     */
    private function setDependencies(array $dependencies)
    {
        // Assign the provided dependency to the property
        foreach ($dependencies as $object) {
            // If the parameter is not an object, throw an exception
            if (!is_object($object)) {
                throw new CashIDException("Dependencies must be objects.");
            }
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

        // Assign any remaining default dependencies.
        foreach ($this->defaultDependencies as $dependency) {
            $name = $dependency['name'];
            $class = $dependency['class'];
            $this->$name = new $class();
        }
    }
}
