<?php
// This is a config php file that contains some global variables
// that are used in the project.

class VariablesConfig implements JsonSerializable
{
    static public $domain = "https://tales.anonymousgca.eu/";

    // Function to get the domain
    static public function getDomain(): string
    {
        return self::$domain;
    }

    public function jsonSerialize(): mixed
    {
        return get_object_vars($this);
    }
}
?>

