<?php
// This is a config php file that contains some global variables
// that are used in the project.

class VariablesConfig implements JsonSerializable
{
    public static $domain = "https://tales.anonymousgca.eu/";
    public static $profileImage = "profile.webp";

    // Function to get the domain
    public static $profileCover = "cover.webp";

    static public function getDomain(): string
    {
        return self::$domain;
    }

    // Function to get the profile image
    static public function getProfileImage(): string
    {
        return self::$profileImage;
    }

    // Function to get the profile cover
    static public function getProfileCover(): string
    {
        return self::$profileCover;
    }

    public function jsonSerialize(): mixed
    {
        return get_object_vars($this);
    }
}
?>

