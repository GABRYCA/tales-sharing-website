<?php
// This is a config php file that contains some global variables
// that are used in the project.

class VariablesConfig implements JsonSerializable
{
    public static $domain = "https://tales.anonymousgca.eu/";
    public static $emailNoreply = "noreply@anonymousgca.eu";
    public static $profileImage = "profile.webp";
    public static $profileCover = "cover.webp";
    public static $urlCoverGallery = "common/assets/cover.webp";
    public static $websiteName = "TalesGCA";

    // Function to get the domain
    static public function getDomain(): string
    {
        return self::$domain;
    }

    // Function to get the noreply email
    static public function getEmailNoreply(): string
    {
        return self::$emailNoreply;
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

    // Function to get the url cover gallery
    static public function getUrlCoverGallery(): string
    {
        return self::$urlCoverGallery;
    }

    // Function to get the website name
    static public function getWebsiteName(): string
    {
        return self::$websiteName;
    }

    public function jsonSerialize(): mixed
    {
        return get_object_vars($this);
    }
}
?>

