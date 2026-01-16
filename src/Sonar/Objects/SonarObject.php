<?php

namespace Kengineering\Sonar\Objects;

use Kengineering\Sonar\Graphql\Query as GraphqlQuery;
use Kengineering\Sonar\Graphql\Request;
use Kengineering\Sonar\Operations\Mutation;
use Kengineering\Sonar\Operations\Query;
use Exception;
use GuzzleHttp\Client;

/**
 * @property int $id
 * @property int $sonar_unique_id
 * @property string $created_at
 * @property string $updated_at
 * @property string $_version
 */
class SonarObject
{
    const PROPERTIES = [];

    const CREATE_PROPERTIES = [];

    const UPDATE_PROPERTIES = [];

    const OWNED_ACCESS_NAME = 'fake_value_to_shut_up_intelephense';

    const RELATED_OBJECTS = [];

    const OBJECT_SINGLE_NAME = null;

    const OBJECT_MULTIPLE_NAME = null;

    private ?Client $client;
    protected bool $exists_in_sonar = false;

    /** @var array<string|int|mixed> */
    protected $property_values = [];

    /**
     * @param  array<array<array<mixed>>>  $object_info
     */
    public function __construct(array $object_info, bool $exists_in_sonar = false, ?Client $client = null)
    {
        $this->exists_in_sonar = $exists_in_sonar;
        $this->client = $client;

        $namespace = "Kengineering\Sonar\Objects\\";

        foreach (static::PROPERTIES as $property) {
            $this->property_values[$property] = $object_info[$property] ?? null;
        }

        foreach (static::RELATED_OBJECTS as $object_name => $related_object) {

            $relationship = $related_object;

            /**
             * @var class-string<self> $object_location
             */
            $object_location = $namespace . str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $object_name)));

            if ($relationship === 'many') {
                $access_name = $object_location::OBJECT_MULTIPLE_NAME ?? "{$object_name}s";
                if (isset($object_info[$access_name])) {
                    $object_list = [];
                    foreach ($object_info[$access_name]['entities'] as $object) {
                        $object_list[] = new $object_location($object, true);
                    }
                    $this->$access_name = $object_list;
                }
            } elseif ($relationship === 'one') {
                $access_name = $object_name;
                if (isset($object_info[$access_name])) {
                    $this->$access_name = new $object_location($object_info[$access_name], true);
                }
            } elseif ($relationship === 'owned_by') {
                if (
                    defined(static::class . '::OWNED_ACCESS_NAME') &&
                    isset($object_info[static::OWNED_ACCESS_NAME])
                ) {
                    $access_name = static::OWNED_ACCESS_NAME;
                    /** @var array{__typename: string, ...} $owned_data */
                    $owned_data = $object_info[$access_name];
                    if (class_exists($namespace . $owned_data['__typename'])) {
                        /** @var string $sub_object_info */
                        $sub_object_info = $owned_data['__typename'];
                        $access_name = lcfirst($sub_object_info);
                        $object_class = $namespace . $sub_object_info;
                        $this->$access_name = new $object_class($owned_data, true);
                    }
                }
            }
        }
    }

    public function __get(string $property): mixed
    {
        return $this->property_values[$property] ?? null;
    }

    public function __set(string $property, mixed $value)
    {
        $this->property_values[$property] = $value;
    }

    /** @return array<mixed> */
    public function toArray()
    {
        return $this->property_values;
    }

    public static function query(?Client $client = null): Query
    {
        return new Query(static::class, $client);
    }

    /**
     * @return array<\Kengineering\Sonar\Objects\SonarObject>
     */
    public static function get(?Client $client = null): array
    {
        return self::query($client)->get();
    }

    public static function first(?Client $client = null): ?self
    {
        $objects = self::get($client);
        if (!isset($objects[0])) {
            return null;
        }

        return $objects[0];
    }

    public static function firstOrFail(?Client $client = null): ?self
    {
        $first = self::first($client);
        if (!isset($first)) {
            throw new Exception('Query did not return a first element');
        }

        return $first;
    }
    /**
     * @param  ?array<mixed>  $delete_input
     */
    public function mutation(string $type, $delete_input = null): Mutation
    {
        return new Mutation(object: $this, type: $type, client: $this->client, delete_input: $delete_input);
    }

    public function batch(): Mutation
    {
        return $this->mutation($this->exists_in_sonar ? 'update' : 'create');
    }

    private function create(bool $batch_request = false): Mutation|static
    {
        $mutation = $this->mutation('create');

        if ($batch_request) {
            return $mutation;
        }

        $result = $mutation->execute();

        /**
         * @var int $resulting_id
         */
        $resulting_id = $result['id'];

        $this->id = $resulting_id;

        $this->exists_in_sonar = true;

        return $this;
    }

    protected function update(bool $batch_request = false): Mutation|static
    {
        $mutation = $this->mutation('update');

        if ($batch_request) {
            return $mutation;
        }

        $mutation->execute();

        return $this;
    }
    /**
     * @param  ?array<mixed>  $input
     */
    public function delete(bool $batch_request = false, array $input = null): ?Mutation
    {
        $mutation = $this->mutation('delete', $input);

        if ($batch_request) {
            return $mutation;
        }
        $mutation->execute();

        return null;
    }

    public function save(bool $batch_request = false): Mutation|static
    {
        if ($this->exists_in_sonar) {
            return $this->update($batch_request);
        } else {
            return $this->create($batch_request);
        }

    }

    public function exists(): bool
    {
        return $this->exists_in_sonar;
    }

    /**
     * @return string
     */
    public static function object_relationship(string $object)
    {
        /** @phpstan-ignore-next-line */
        $object_name = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', basename(str_replace('\\', '/', $object))));
        if (!isset(static::RELATED_OBJECTS[$object_name])) {
            throw new Exception('this class does not have a relation to the parent class');
        }

        return static::RELATED_OBJECTS[$object_name];
    }

    /**
     * Summary of refresh_object
     *
     * @param  array<string>  $additional_info
     * @return static
     *
     * @throws \Exception
     */
    public function refresh_object(array $additional_info = [])
    {
        if (!$this->exists()) {
            throw new Exception('cannot refresh object that does not exist in sonar');
        }

        /**
         * @var Query $query
         */
        $query = static::query($this->client)->search->intSearch('id', (int) $this->id, '==');

        foreach ($additional_info as $object) {
            $query->add($object);
        }

        $result = $query->first();

        if ($result instanceof static) {
            return $result;
        } else {
            throw new Exception('was unable to refresh object from sonar');
        }

    }

    public function get_mutation_name(string $type): string
    {
        if (method_exists($this, 'mutate_function_name')) {
            return $this->mutate_function_name($type);
        }

        return $type . basename(str_replace('\\', '/', $this::class));
    }

    public function get_mutation_input_name(string $type): string
    {
        if (method_exists($this, 'mutate_function_input_name')) {
            return $this->mutate_function_input_name($type);
        }

        return ucfirst($type) . basename(str_replace('\\', '/', $this::class)) . 'MutationInput';
    }

    /**
     * @return array<string>
     */
    public function get_mutate_properties(string $type)
    {

        $property_name = strtoupper($type) . '_PROPERTIES';

        return array_flip(constant($this::class . '::' . $property_name));
    }

    //######
    //
    //
    //  some functions to reduce duplication of code accross object helper methods
    // not meant to be used outside of the sonar objects
    //

    protected function batchMutation(GraphqlQuery $query, bool $batch_request = false): Mutation|static
    {

        if ($batch_request) {
            $mutation = new Mutation(raw_query: $query);

            return $mutation;
        } else {
            $request = new Request('mutation', [$query], $this->client);
            $request->sendRequest();

            return $this;
        }
    }

    protected function check_existance_return_object_value(int|self $object): int
    {
        if (!$this->exists() || ($object instanceof self && !$object->exists())) {
            throw new Exception('this object does not exist in sonar and cannot be used');
        }
        if ($object instanceof self) {
            $object = $object->id;
        }

        return $object;
    }

    /**
     * @throws \Exception
     * @return void
     */
    public function existOrFail() {
        if (! $this->exists()) {
            throw new Exception('this object does not exist in sonar');
        }
    }
}
