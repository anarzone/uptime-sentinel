<?php
require __DIR__ . '/vendor/autoload.php';
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__ . '/.env');
$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

$locator = $container->get('container.env_var_processors_locator');
// The locator is a ServiceLocator, we can't easily list it unless we introspect the class or it's public.
// Usually, we can use reflection.
$ref = new ReflectionClass($locator);
$prop = $ref->getProperty('factories');
$prop->setAccessible(true);
$factories = $prop->getValue($locator);

echo "Registered processors:\n";
foreach (array_keys($factories) as $key) {
    echo "- " . $key . "\n";
}
