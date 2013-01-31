# ResqueBundle [![Build Status](https://travis-ci.org/ShonM/ResqueBundle.png?branch=master)](https://travis-ci.org/ShonM/ResqueBundle)

**Create a Job**

```php
    // src/Acme/ResqueBundle/Jobs/EmptyJob.php
    namespace Acme\ResqueBundle\Jobs;

    class HelloWorldJob
    {
        public function perform ()
        {
            fwrite(STDOUT, "Hello " . $this->args['hello'] . "!\n");
        }
    }
```

**Post your Job**

```php
    $container->get('resque')->add('Acme\ResqueBundle\Jobs\HelloWorldJob', 'queuename', array('hello' => 'world'));
```

**Hire a Worker**

```app/console resque:worker queuename```

**Party!**

---

# Best Practises

 1. Jobs should be small and simple
 2. Workers should be idempotent and transactional
 3. Design for concurrency - use connection pooling

---

# Development & Testing

```
$ composer install --dev
$ bin/phpunit
```
